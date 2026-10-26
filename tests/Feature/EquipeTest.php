<?php

use App\Enums\PapelDeUsuario;
use App\Models\Barbearia;
use App\Models\Profissional;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->barbearia = Barbearia::create(['nome' => 'Barbearia da Equipe', 'slug' => 'equipe-teste', 'fuso_horario' => 'UTC', 'ativa' => true]);
    $this->proprietario = Usuario::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Proprietário', 'email' => 'dono@equipe.test', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true]);
    $this->profissional = Profissional::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Barbeiro', 'ativo' => true]);
});

it('cria convite de uso único e ativa conta com senha definida pela pessoa convidada', function (): void {
    $this->actingAs($this->proprietario)->post('/configuracoes/equipe/convites', [
        'nome' => 'Barbeiro Convidado',
        'email' => 'BARBEIRO@EQUIPE.TEST',
        'papel' => 'profissional',
        'profissional_id' => $this->profissional->id,
    ])->assertRedirect()->assertSessionHas('conviteEquipe');

    $usuario = Usuario::query()->where('email', 'barbeiro@equipe.test')->firstOrFail();
    $convite = session('conviteEquipe');
    preg_match('~/convites/equipe#([A-Za-z0-9]+)$~', $convite['url'], $partes);
    $token = $partes[1] ?? '';

    expect($convite['url'])->toContain('#'.$token)
        ->and(substr($convite['url'], 0, strpos($convite['url'], '#')))->not->toContain($token);
    expect($usuario->ativo)->toBeFalse()
        ->and($usuario->profissional_id)->toBe($this->profissional->id)
        ->and($usuario->convite_token_hash)->toBe(hash('sha256', $token))
        ->and($usuario->convite_token_hash)->not->toBe($token);

    $this->get('/convites/equipe')->assertOk()->assertInertia(fn ($pagina) => $pagina->component('Autenticacao/AceitarConvite'));
    $this->post('/convites/equipe/validar', ['token' => $token])->assertRedirect('/convites/equipe')->assertSessionHas('conviteAceite');
    $this->post('/convites/equipe/ativar', ['token' => $token, 'senha' => 'uma-senha-segura-123', 'senha_confirmation' => 'uma-senha-segura-123'])->assertRedirect('/');
    $this->assertAuthenticatedAs($usuario->fresh());
    expect($usuario->fresh()->ativo)->toBeTrue()
        ->and($usuario->fresh()->convite_token_hash)->toBeNull()
        ->and(Hash::check('uma-senha-segura-123', $usuario->fresh()->senha))->toBeTrue();
    $this->post('/convites/equipe/ativar', ['token' => $token, 'senha' => 'outra-senha-segura-123', 'senha_confirmation' => 'outra-senha-segura-123'])->assertSessionHasErrors('convite');
});

it('não permite que profissional acesse financeiro ou gerencie a equipe', function (): void {
    $usuario = Usuario::create(['barbearia_id' => $this->barbearia->id, 'profissional_id' => $this->profissional->id, 'nome' => 'Barbeiro', 'email' => 'barbeiro@equipe.test', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Profissional, 'ativo' => true]);
    $this->actingAs($usuario)->get('/financeiro')->assertForbidden();
    $this->get('/configuracoes/equipe')->assertForbidden();
});

it('não permite ao gerente convidar para outra barbearia nem alterar conta de outra empresa', function (): void {
    $gerente = Usuario::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Gerente', 'email' => 'gerente@equipe.test', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Gerente, 'ativo' => true]);
    $outra = Barbearia::create(['nome' => 'Outra', 'slug' => 'outra-equipe', 'fuso_horario' => 'UTC', 'ativa' => true]);
    $profissionalAlheio = Profissional::create(['barbearia_id' => $outra->id, 'nome' => 'Barbeiro alheio', 'ativo' => true]);
    $usuarioAlheio = Usuario::create(['barbearia_id' => $outra->id, 'nome' => 'Recepção alheia', 'email' => 'recepcao@outra.test', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Recepcao, 'ativo' => true]);

    $this->actingAs($gerente)->from('/configuracoes/equipe')->post('/configuracoes/equipe/convites', ['nome' => 'Invitado', 'email' => 'novo@equipe.test', 'papel' => 'profissional', 'profissional_id' => $profissionalAlheio->id])->assertSessionHasErrors('profissional_id');
    $this->patch('/configuracoes/equipe/'.$usuarioAlheio->id.'/acesso')->assertNotFound();
});
