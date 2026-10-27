<?php

use App\Models\AdministradorDaPlataforma;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\ContaDeCliente;
use App\Models\Lembrete;
use App\Models\RegraDeBeneficio;
use App\Models\Usuario;
use App\Notifications\RedefinirSenhaDaContaDeCliente;
use App\Notifications\VerificarEmailDaContaDeCliente;
use Database\Seeders\DesenvolvimentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DesenvolvimentoSeeder::class);
});

it('seeda as quatro contas demo com senhas armazenadas em hash e sem duplicar registros', function (): void {
    $this->seed(DesenvolvimentoSeeder::class);
    expect(Usuario::query()->whereIn('email', ['dono@opeflow.local', 'barbeiro@opeflow.local'])->count())->toBe(2)
        ->and(ContaDeCliente::query()->where('email', 'cliente@opeflow.local')->count())->toBe(1)
        ->and(AdministradorDaPlataforma::query()->where('email', 'admin@opeflow.local')->count())->toBe(1)
        ->and(Hash::check('OpeflowDev2026!', Usuario::query()->where('email', 'dono@opeflow.local')->firstOrFail()->senha))->toBeTrue();
});

it('permite autenticar proprietário e profissional pelo acesso da equipe', function (): void {
    $this->post('/entrar', ['email' => 'dono@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/');
    $this->get('/agenda')->assertOk();
    $this->post('/sair')->assertRedirect('/entrar');

    $this->post('/entrar', ['email' => 'barbeiro@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/');
    $this->get('/agenda')->assertOk();
});

it('permite ao cliente consultar somente os agendamentos da própria conta', function (): void {
    $agendamento = Agendamento::query()->firstOrFail();
    $clienteAlheio = Cliente::create(['barbearia_id' => $agendamento->barbearia_id, 'nome' => 'Outro cliente', 'telefone' => '63999990004']);
    Agendamento::create([
        'barbearia_id' => $agendamento->barbearia_id,
        'cliente_id' => $clienteAlheio->id,
        'profissional_id' => $agendamento->profissional_id,
        'servico_id' => $agendamento->servico_id,
        'inicio_em' => now('UTC')->addDays(4),
        'fim_em' => now('UTC')->addDays(4)->addMinutes(30),
        'status' => 'confirmado',
    ]);

    $this->post('/cliente/entrar', ['email' => 'cliente@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/cliente');
    $this->get('/cliente')->assertOk()->assertInertia(fn ($pagina) => $pagina
        ->component('Cliente/Inicio')
        ->where('agendamentos.0.id', $agendamento->id)
        ->has('agendamentos', 1));

    $this->get('/agenda')->assertRedirect('/entrar');
});

it('permite ao cliente cancelar apenas o próprio agendamento futuro', function (): void {
    $agendamento = Agendamento::query()->firstOrFail();
    $outroCliente = Cliente::create([
        'barbearia_id' => $agendamento->barbearia_id,
        'nome' => 'Cliente alheio',
        'telefone' => '63999990005',
    ]);
    $agendamentoAlheio = Agendamento::create([
        'barbearia_id' => $agendamento->barbearia_id,
        'cliente_id' => $outroCliente->id,
        'profissional_id' => $agendamento->profissional_id,
        'servico_id' => $agendamento->servico_id,
        'inicio_em' => now('UTC')->addDays(3),
        'fim_em' => now('UTC')->addDays(3)->addMinutes(30),
        'status' => 'confirmado',
    ]);

    $this->post('/cliente/entrar', ['email' => 'cliente@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/cliente');
    $this->delete("/cliente/agendamentos/{$agendamentoAlheio->id}")->assertNotFound();
    $this->delete("/cliente/agendamentos/{$agendamento->id}")->assertRedirect('/cliente');

    expect($agendamento->fresh()->status)->toBe('cancelado')
        ->and($agendamentoAlheio->fresh()->status)->toBe('confirmado');
});

it('permite reagendar o próprio horário mantendo o anterior no histórico', function (): void {
    $anterior = Agendamento::query()->firstOrFail();
    $this->post('/cliente/entrar', ['email' => 'cliente@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/cliente');

    $this->post("/cliente/agendamentos/{$anterior->id}/reagendar", [
        'data' => now('America/Araguaina')->addDays(3)->toDateString(),
        'hora' => '10:00',
    ])->assertRedirect('/cliente')->assertSessionHas('sucesso');

    $novo = Agendamento::query()->where('agendamento_origem_id', $anterior->id)->firstOrFail();
    expect($anterior->fresh()->status)->toBe('reagendado')
        ->and($novo->status)->toBe('confirmado')
        ->and($novo->cliente_id)->toBe($anterior->cliente_id)
        ->and($novo->profissional_id)->toBe($anterior->profissional_id)
        ->and($novo->servico_id)->toBe($anterior->servico_id);
});

it('rejeita horário ocupado sem alterar a reserva original', function (): void {
    $anterior = Agendamento::query()->firstOrFail();
    $conflitante = Agendamento::create([
        'barbearia_id' => $anterior->barbearia_id,
        'cliente_id' => $anterior->cliente_id,
        'profissional_id' => $anterior->profissional_id,
        'servico_id' => $anterior->servico_id,
        'inicio_em' => now('America/Araguaina')->addDays(3)->setTime(10, 0)->utc(),
        'fim_em' => now('America/Araguaina')->addDays(3)->setTime(10, 30)->utc(),
        'status' => 'confirmado',
    ]);
    $this->post('/cliente/entrar', ['email' => 'cliente@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/cliente');

    $this->post("/cliente/agendamentos/{$anterior->id}/reagendar", [
        'data' => now('America/Araguaina')->addDays(3)->toDateString(), 'hora' => '10:00',
    ])->assertSessionHasErrors('hora');

    expect($anterior->fresh()->status)->toBe('confirmado')
        ->and(Agendamento::query()->where('agendamento_origem_id', $anterior->id)->exists())->toBeFalse()
        ->and($conflitante->fresh()->status)->toBe('confirmado');
});

it('não permite ao cliente cancelar agendamento já iniciado ou concluído', function (): void {
    $agendamento = Agendamento::query()->firstOrFail();
    $agendamento->update(['inicio_em' => now('UTC')->subHour(), 'status' => 'confirmado']);
    $this->post('/cliente/entrar', ['email' => 'cliente@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/cliente');
    $this->delete("/cliente/agendamentos/{$agendamento->id}")->assertUnprocessable();

    expect($agendamento->fresh()->status)->toBe('confirmado');
});

it('libera benefício válido e cancela lembretes ao cancelar pelo portal', function (): void {
    $agendamento = Agendamento::query()->firstOrFail();
    $cliente = Cliente::query()->findOrFail($agendamento->cliente_id);
    $regra = RegraDeBeneficio::create([
        'barbearia_id' => $agendamento->barbearia_id,
        'servico_originador_id' => $agendamento->servico_id,
        'servico_beneficiado_id' => $agendamento->servico_id,
        'disponivel_apos_dias' => 0,
        'expira_apos_dias' => 14,
        'ativo' => true,
    ]);
    $atendimento = Atendimento::create([
        'barbearia_id' => $agendamento->barbearia_id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $agendamento->profissional_id,
        'servico_id' => $agendamento->servico_id,
        'iniciado_em' => now('UTC')->subDays(3),
        'concluido_em' => now('UTC')->subDays(3)->addMinutes(30),
        'valor_total' => 45,
        'status' => 'concluido',
    ]);
    $beneficio = BeneficioDoCliente::create([
        'barbearia_id' => $agendamento->barbearia_id,
        'cliente_id' => $cliente->id,
        'regra_de_beneficio_id' => $regra->id,
        'atendimento_origem_id' => $atendimento->id,
        'disponivel_em' => now('UTC')->subDay(),
        'expira_em' => now('UTC')->addDays(5),
        'status' => 'reservado',
    ]);
    $agendamento->update(['beneficio_do_cliente_id' => $beneficio->id]);
    $lembrete = Lembrete::create([
        'barbearia_id' => $agendamento->barbearia_id,
        'cliente_id' => $cliente->id,
        'agendamento_id' => $agendamento->id,
        'tipo' => 'agendamento_24_horas',
        'agendado_para' => now('UTC')->addDay(),
        'status' => 'pendente',
        'chave_idempotencia' => 'portal-cancelamento:'.$agendamento->id,
    ]);

    $this->post('/cliente/entrar', ['email' => 'cliente@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/cliente');
    $this->delete("/cliente/agendamentos/{$agendamento->id}")->assertRedirect('/cliente');

    expect($beneficio->fresh()->status)->toBe('disponivel')
        ->and($lembrete->fresh()->status)->toBe('cancelado');
});

it('mantém o administrador em guarda global isolada sem acesso à operação da barbearia', function (): void {
    $this->post('/admin/entrar', ['email' => 'admin@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/admin');
    $this->get('/admin')->assertOk()->assertInertia(fn ($pagina) => $pagina->component('Administracao/Inicio')->where('nome', 'Administrador Demonstração'));
    $this->get('/admin')->assertInertia(fn ($pagina) => $pagina->has('indicadores.barbearias')->has('saude.banco')->has('barbeariasRecentes'));
    $this->get('/financeiro')->assertRedirect('/entrar');

    expect(AdministradorDaPlataforma::query()->firstOrFail()->getAttribute('barbearia_id'))->toBeNull()
        ->and(Barbearia::query()->count())->toBe(1);
});

it('não executa seed de demonstração fora de local e testing', function (): void {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(DesenvolvimentoSeeder::class)->run())->toThrow(RuntimeException::class);
    $this->get('/cliente/entrar')->assertOk()->assertInertia(fn ($pagina) => $pagina->where('mostrarCredenciaisDeDesenvolvimento', false));
    $this->get('/admin/entrar')->assertNotFound();
});

it('cria portal apenas para e-mail cadastrado e exige confirmação antes da autenticação', function (): void {
    Notification::fake();
    $barbearia = Barbearia::query()->where('slug', 'opeflow-demo')->firstOrFail();
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Cliente com e-mail', 'telefone' => '63999990099', 'email' => 'portal@cliente.local']);

    $this->post('/cliente/cadastro', ['barbearia' => $barbearia->slug, 'email' => 'portal@cliente.local', 'senha' => 'SenhaForte12345', 'senha_confirmation' => 'SenhaForte12345'])
        ->assertSessionHas('sucesso');
    $conta = ContaDeCliente::query()->where('cliente_id', $cliente->id)->firstOrFail();
    expect($conta->email_verified_at)->toBeNull();
    Notification::assertSentTo($conta, VerificarEmailDaContaDeCliente::class);

    $this->post('/cliente/entrar', ['email' => $conta->email, 'senha' => 'SenhaForte12345'])->assertSessionHasErrors('email');
    $url = URL::temporarySignedRoute('cliente.verificar-email', now()->addMinutes(10), ['conta' => $conta->id, 'hash' => sha1($conta->email)]);
    $this->get($url)->assertRedirect('/cliente/entrar');
    expect($conta->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->post('/cliente/entrar', ['email' => $conta->email, 'senha' => 'SenhaForte12345'])->assertRedirect('/cliente');
});

it('redefine a senha por token de uso único sem expor o segredo na URL enviada', function (): void {
    Notification::fake();
    $conta = ContaDeCliente::query()->where('email', 'cliente@opeflow.local')->firstOrFail();

    $this->get('/cliente/esqueci-senha')->assertOk()->assertInertia(fn ($pagina) => $pagina->component('Cliente/EsqueciSenha'));
    $this->post('/cliente/esqueci-senha', ['email' => $conta->email])->assertSessionHas('sucesso');
    Notification::assertSentTo($conta, RedefinirSenhaDaContaDeCliente::class);

    $notificacao = Notification::sent($conta, RedefinirSenhaDaContaDeCliente::class)->first();
    $url = $notificacao->toMail($conta)->actionUrl;
    expect(parse_url($url, PHP_URL_QUERY))->toBeNull();
    parse_str(parse_url($url, PHP_URL_FRAGMENT), $fragmento);
    $token = $fragmento['token'];
    expect(strlen($token))->toBe(64);

    $registroToken = DB::table('tokens_de_redefinicao_de_senha_de_clientes')->where('conta_de_cliente_id', $conta->id)->first();
    expect($registroToken->token_hash)->toBe(hash('sha256', $token))->and($registroToken->token_hash)->not->toBe($token);

    $this->post('/cliente/redefinir-senha', ['token' => $token, 'senha' => 'OutraSenhaForte123', 'senha_confirmation' => 'OutraSenhaForte123'])
        ->assertRedirect('/cliente/entrar');
    $this->post('/cliente/entrar', ['email' => $conta->email, 'senha' => 'OutraSenhaForte123'])->assertRedirect('/cliente');
    $this->post('/cliente/redefinir-senha', ['token' => $token, 'senha' => 'MaisUmaSenha12345', 'senha_confirmation' => 'MaisUmaSenha12345'])
        ->assertSessionHasErrors('token');
});

it('responde de forma genérica quando o e-mail não possui conta de cliente', function (): void {
    Notification::fake();

    $this->post('/cliente/esqueci-senha', ['email' => 'nao-existe@cliente.local'])
        ->assertSessionHas('sucesso', 'Se houver uma conta verificada para esse e-mail, enviaremos instruções de redefinição.');
    Notification::assertNothingSent();
});

it('rejeita token de redefinição expirado', function (): void {
    Notification::fake();
    $conta = ContaDeCliente::query()->where('email', 'cliente@opeflow.local')->firstOrFail();
    $this->post('/cliente/esqueci-senha', ['email' => $conta->email]);
    $notificacao = Notification::sent($conta, RedefinirSenhaDaContaDeCliente::class)->first();
    parse_str(parse_url($notificacao->toMail($conta)->actionUrl, PHP_URL_FRAGMENT), $fragmento);
    DB::table('tokens_de_redefinicao_de_senha_de_clientes')->where('conta_de_cliente_id', $conta->id)->update(['expira_em' => now()->subSecond()]);

    $this->post('/cliente/redefinir-senha', ['token' => $fragmento['token'], 'senha' => 'OutraSenhaForte123', 'senha_confirmation' => 'OutraSenhaForte123'])
        ->assertSessionHasErrors('token');
    expect(Hash::check('OpeflowDev2026!', $conta->fresh()->senha))->toBeTrue();
});
