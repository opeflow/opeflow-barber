<?php

use App\Models\AdministradorDaPlataforma;
use App\Models\EventoDeAuditoria;
use App\Models\IntegracaoDaPlataforma;
use App\Services\Administracao\AutenticadorTotp;
use App\Services\Administracao\RegistradorDeAuditoria;
use Database\Seeders\DesenvolvimentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DesenvolvimentoSeeder::class);
});

it('aplica TOTP compatível com o vetor oficial RFC 6238 e tolera apenas uma janela adjacente', function (): void {
    $totp = app(AutenticadorTotp::class);
    $segredo = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    expect($totp->gerarCodigo($segredo, 59))->toBe('287082')
        ->and($totp->validarCodigo($segredo, '287082', 59))->toBeTrue()
        ->and($totp->validarCodigo($segredo, '287082', 180))->toBeFalse();
});

it('configura MFA com segredo cifrado e códigos de recuperação armazenados como hash', function (): void {
    $this->post('/admin/entrar', ['email' => 'admin@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/admin');
    $this->get('/admin/mfa/configurar')->assertOk()->assertInertia(fn ($pagina) => $pagina->component('Administracao/ConfigurarMfa'));
    $segredo = Crypt::decryptString(session('admin_mfa_segredo_pendente'));
    $codigo = app(AutenticadorTotp::class)->gerarCodigo($segredo);
    $this->post('/admin/mfa/configurar', ['codigo' => $codigo])->assertRedirect('/admin/mfa/codigos-recuperacao');

    $administrador = AdministradorDaPlataforma::query()->where('email', 'admin@opeflow.local')->firstOrFail();
    $segredoEmRepouso = DB::table('administradores_da_plataforma')->where('id', $administrador->id)->value('segredo_mfa');
    expect($administrador->mfa_confirmado_em)->not->toBeNull()
        ->and($segredoEmRepouso)->not->toBe($administrador->segredo_mfa)
        ->and(DB::table('codigos_de_recuperacao_administrativa')->where('administrador_da_plataforma_id', $administrador->id)->count())->toBe(10)
        ->and(EventoDeAuditoria::query()->where('acao', 'mfa.configurado')->exists())->toBeTrue();

    $codigos = json_decode(Crypt::decryptString(session('admin_codigos_recuperacao')), true, flags: JSON_THROW_ON_ERROR);
    $codigoDeRecuperacao = $codigos[0];
    $this->get('/admin/mfa/codigos-recuperacao')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertInertia(fn ($pagina) => $pagina->component('Administracao/CodigosDeRecuperacao')->has('codigos', 10));
    $this->get('/admin/mfa/codigos-recuperacao')->assertNotFound();
    $this->post('/admin/mfa/verificar', ['codigo' => $codigoDeRecuperacao])->assertRedirect('/admin');
    $this->post('/admin/mfa/verificar', ['codigo' => $codigoDeRecuperacao])->assertSessionHasErrors('codigo');
});

it('cifra credenciais de integração, mascara o retorno e registra alteração sem segredo', function (): void {
    $segredo = 'chave-secreta-provedor-123456';
    $this->post('/admin/entrar', ['email' => 'admin@opeflow.local', 'senha' => 'OpeflowDev2026!']);
    $this->post('/admin/integracoes', ['provedor' => 'armazenamento_s3', 'rotulo' => 'Bucket principal', 'credencial' => $segredo])
        ->assertRedirect('/admin/integracoes');

    $integracao = IntegracaoDaPlataforma::query()->firstOrFail();
    $segredoEmRepouso = DB::table('integracoes_da_plataforma')->where('id', $integracao->id)->value('credencial_cifrada');
    expect($segredoEmRepouso)->not->toBe($segredo)
        ->and($integracao->credencialMascarada())->toEndWith('3456')
        ->and(EventoDeAuditoria::query()->where('acao', 'integracao.criada')->value('metadados'))->not->toContain($segredo);

    $this->get('/admin/integracoes')->assertOk()->assertInertia(fn ($pagina) => $pagina
        ->component('Administracao/Integracoes')->where('integracoes.0.credencialMascarada', '••••••••3456')
        ->missing('integracoes.0.credencial_cifrada')->missing('integracoes.0.credencial'))
        ->assertDontSee($segredo);
    $novaCredencial = 'credencial-rotacionada-abcdef';
    $this->put("/admin/integracoes/{$integracao->id}", [
        'provedor' => $integracao->provedor,
        'rotulo' => $integracao->rotulo,
        'credencial' => $novaCredencial,
    ])->assertRedirect('/admin/integracoes');
    expect($integracao->fresh()->credencialMascarada())->toEndWith('cdef')
        ->and(DB::table('integracoes_da_plataforma')->where('id', $integracao->id)->value('credencial_cifrada'))->not->toBe($novaCredencial);
    $this->post("/admin/integracoes/{$integracao->id}/revogar")->assertRedirect('/admin/integracoes');
    expect($integracao->fresh()->ativa)->toBeFalse();
});

it('filtra auditoria paginada sem registrar e-mail nem credenciais nos eventos', function (): void {
    $this->post('/admin/entrar', ['email' => 'admin@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/admin');
    $this->post('/admin/integracoes', ['provedor' => 'pagamentos', 'rotulo' => 'Gateway', 'credencial' => 'segredo-de-integracao-abc123']);

    $this->get('/admin/auditoria?nivel=info&servico=painel_administrativo')->assertOk()->assertInertia(fn ($pagina) => $pagina
        ->component('Administracao/Auditoria')->where('eventos.data.0.acao', 'integracao.criada')
        ->missing('eventos.data.0.email')->missing('eventos.data.0.credencial'));
    expect(EventoDeAuditoria::query()->firstOrFail()->descricao_redigida)->not->toContain('admin@opeflow.local');
});

it('redige e-mail e tokens caso apareçam acidentalmente em um evento estruturado', function (): void {
    $evento = app(RegistradorDeAuditoria::class)->registrar(
        'teste.redacao',
        'Solicitação para cliente@privado.example com Bearer abc123 e token=segredo-falso',
        metadados: ['resultado' => 'api_key=segredo-falso'],
    );

    expect($evento->descricao_redigida)->not->toContain('cliente@privado.example')
        ->and($evento->descricao_redigida)->not->toContain('abc123')
        ->and($evento->descricao_redigida)->not->toContain('segredo-falso')
        ->and($evento->metadados['resultado'])->not->toContain('segredo-falso');
});

it('mantém administração global desabilitada em produção sem habilitação explícita', function (): void {
    config(['administracao.habilitada' => false]);
    app()->detectEnvironment(fn (): string => 'production');

    $this->get('/admin/entrar')->assertNotFound();
    $token = str_repeat('a', 40);
    $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token)
        ->post('/admin/entrar', ['email' => 'admin@opeflow.local', 'senha' => 'OpeflowDev2026!'])
        ->assertNotFound();
});

it('exige configuração e verificação TOTP antes de liberar o painel em produção', function (): void {
    config(['administracao.habilitada' => true]);
    app()->detectEnvironment(fn (): string => 'production');
    $token = str_repeat('b', 40);

    $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token)
        ->post('/admin/entrar', ['email' => 'admin@opeflow.local', 'senha' => 'OpeflowDev2026!'])
        ->assertRedirect('/admin/mfa/configurar');
    $this->get('/admin')->assertRedirect('/admin/mfa/configurar');
    $this->get('/admin/mfa/configurar')->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    $segredo = Crypt::decryptString(session('admin_mfa_segredo_pendente'));
    $codigo = app(AutenticadorTotp::class)->gerarCodigo($segredo);
    $token = session('_token');
    $this->withHeader('X-CSRF-TOKEN', $token)->post('/admin/mfa/configurar', ['_token' => $token, 'codigo' => $codigo])
        ->assertRedirect('/admin/mfa/codigos-recuperacao');
    $this->get('/admin/mfa/codigos-recuperacao')->assertOk();
    $this->get('/admin')->assertOk()->assertInertia(fn ($pagina) => $pagina->component('Administracao/Inicio'));
});

it('provisiona administrador global pelo comando sem registrar a senha em auditoria', function (): void {
    $this->artisan('administracao:criar-administrador', [
        '--nome' => 'Administrador Operacional',
        '--email' => 'operacao@opeflow.local',
    ])
        ->expectsQuestion('Senha (mínimo 16 caracteres)', 'SenhaAdministrativaSegura123!')
        ->expectsQuestion('Confirme a senha', 'SenhaAdministrativaSegura123!')
        ->expectsOutputToContain('Administrador provisionado')
        ->assertExitCode(0);

    $administrador = AdministradorDaPlataforma::query()->where('email', 'operacao@opeflow.local')->firstOrFail();
    expect(password_verify('SenhaAdministrativaSegura123!', $administrador->senha))->toBeTrue()
        ->and($administrador->mfa_confirmado_em)->toBeNull()
        ->and(EventoDeAuditoria::query()->where('acao', 'administrador.provisionado')->exists())->toBeTrue()
        ->and(EventoDeAuditoria::query()->where('acao', 'administrador.provisionado')->value('metadados'))->not->toContain('SenhaAdministrativaSegura123!');
});
