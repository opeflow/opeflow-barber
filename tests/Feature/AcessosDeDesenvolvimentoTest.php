<?php

use App\Models\AdministradorDaPlataforma;
use App\Models\Agendamento;
use App\Models\Barbearia;
use App\Models\Cliente;
use App\Models\ContaDeCliente;
use App\Models\Usuario;
use Database\Seeders\DesenvolvimentoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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

it('mantém o administrador em guarda global isolada sem acesso à operação da barbearia', function (): void {
    $this->post('/admin/entrar', ['email' => 'admin@opeflow.local', 'senha' => 'OpeflowDev2026!'])->assertRedirect('/admin');
    $this->get('/admin')->assertOk()->assertInertia(fn ($pagina) => $pagina->component('Administracao/Inicio')->where('nome', 'Administrador Demonstração'));
    $this->get('/financeiro')->assertRedirect('/entrar');

    expect(AdministradorDaPlataforma::query()->firstOrFail()->getAttribute('barbearia_id'))->toBeNull()
        ->and(Barbearia::query()->count())->toBe(1);
});

it('não executa seed de demonstração fora de local e testing', function (): void {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => app(DesenvolvimentoSeeder::class)->run())->toThrow(RuntimeException::class);
    $this->get('/cliente/entrar')->assertNotFound();
    $this->get('/admin/entrar')->assertNotFound();
});
