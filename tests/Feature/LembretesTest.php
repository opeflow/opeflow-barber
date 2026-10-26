<?php

use App\Enums\PapelDeUsuario;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\Lembrete;
use App\Models\Profissional;
use App\Models\RegraDeBeneficio;
use App\Models\Servico;
use App\Models\Usuario;
use App\Services\Lembretes\AgendadorDeLembretes;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepararLembretes(string $slug): array
{
    $barbearia = Barbearia::create(['nome' => 'Barbearia Lembretes', 'slug' => $slug, 'fuso_horario' => 'UTC', 'ativa' => true]);
    $servico = Servico::create(['barbearia_id' => $barbearia->id, 'nome' => 'Corte', 'duracao_em_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $profissional = Profissional::create(['barbearia_id' => $barbearia->id, 'nome' => 'Profissional', 'ativo' => true]);
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Cliente Lembrete', 'telefone' => '63999991234', 'aceita_lembretes' => true]);
    $agendamento = Agendamento::create([
        'barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'profissional_id' => $profissional->id,
        'servico_id' => $servico->id, 'inicio_em' => now('UTC')->addDays(3), 'fim_em' => now('UTC')->addDays(3)->addMinutes(30), 'status' => 'confirmado',
    ]);

    return [$barbearia, $cliente, $agendamento];
}

it('programa lembretes de 24 e 2 horas somente com consentimento', function (): void {
    [$barbearia, $cliente, $agendamento] = prepararLembretes('barbearia-lembretes');
    app(AgendadorDeLembretes::class)->agendarParaAgendamento($agendamento);
    app(AgendadorDeLembretes::class)->agendarParaAgendamento($agendamento);

    expect(Lembrete::query()->where('barbearia_id', $barbearia->id)->count())->toBe(2);
    $cliente->update(['aceita_lembretes' => false]);
    $outro = Agendamento::create([
        'barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'profissional_id' => $agendamento->profissional_id,
        'servico_id' => $agendamento->servico_id, 'inicio_em' => now('UTC')->addDays(5), 'fim_em' => now('UTC')->addDays(5)->addMinutes(30), 'status' => 'confirmado',
    ]);
    app(AgendadorDeLembretes::class)->agendarParaAgendamento($outro);
    expect(Lembrete::query()->where('barbearia_id', $barbearia->id)->count())->toBe(2);
});

it('abre lembretes isolados por barbearia e registra apenas confirmação manual', function (): void {
    [$barbearia, , $agendamento] = prepararLembretes('barbearia-lembretes-a');
    [$outra] = prepararLembretes('barbearia-lembretes-b');
    app(AgendadorDeLembretes::class)->agendarParaAgendamento($agendamento);
    $lembrete = Lembrete::query()->where('barbearia_id', $barbearia->id)->firstOrFail();
    $dono = Usuario::create(['barbearia_id' => $barbearia->id, 'nome' => 'Dono', 'email' => 'lembrete-dono@teste.local', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true]);
    $donoAlheio = Usuario::create(['barbearia_id' => $outra->id, 'nome' => 'Outra', 'email' => 'lembrete-outro@teste.local', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true]);

    $this->actingAs($dono)->get('/lembretes')->assertOk()->assertInertia(fn ($pagina) => $pagina->component('Lembretes/Index')->has('lembretes', 2)->where('lembretes.0.linkWhatsApp', fn ($link) => str_starts_with($link, 'https://wa.me/')));
    $this->actingAs($donoAlheio)->post('/lembretes/'.$lembrete->id.'/marcar-enviado')->assertNotFound();
    expect($lembrete->fresh()->status)->toBe('pendente');

    $this->actingAs($dono)->post('/lembretes/'.$lembrete->id.'/marcar-enviado')->assertRedirect();
    expect($lembrete->fresh()->status)->toBe('enviado_manualmente')
        ->and($lembrete->fresh()->enviado_por_usuario_id)->toBe($dono->id)
        ->and($lembrete->fresh()->enviado_manualmente_em)->not->toBeNull();
});

it('permite retirar consentimento e cancela lembretes pendentes do cliente', function (): void {
    [$barbearia, $cliente, $agendamento] = prepararLembretes('barbearia-retirar-consentimento');
    app(AgendadorDeLembretes::class)->agendarParaAgendamento($agendamento);
    $dono = Usuario::create(['barbearia_id' => $barbearia->id, 'nome' => 'Dono', 'email' => 'retirar-consentimento@teste.local', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true]);

    $this->actingAs($dono)->post('/clientes/'.$cliente->id.'/retirar-consentimento')->assertRedirect();
    expect($cliente->fresh()->aceita_lembretes)->toBeFalse()
        ->and(Lembrete::query()->where('cliente_id', $cliente->id)->where('status', 'pendente')->count())->toBe(0)
        ->and(Lembrete::query()->where('cliente_id', $cliente->id)->where('status', 'cancelado')->count())->toBe(2);
});

it('programa o aviso imediato de benefício disponível e o aviso de expiração', function (): void {
    [$barbearia, $cliente, $agendamento] = prepararLembretes('barbearia-beneficio-lembrete');
    $regra = RegraDeBeneficio::create([
        'barbearia_id' => $barbearia->id, 'servico_originador_id' => $agendamento->servico_id,
        'servico_beneficiado_id' => $agendamento->servico_id, 'disponivel_apos_dias' => 0,
        'expira_apos_dias' => 10, 'ativo' => true,
    ]);
    $atendimento = Atendimento::create([
        'barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'profissional_id' => $agendamento->profissional_id,
        'servico_id' => $agendamento->servico_id, 'iniciado_em' => now('UTC')->subMinutes(30),
        'concluido_em' => now('UTC'), 'valor_total' => 50, 'status' => 'concluido',
    ]);
    $beneficio = BeneficioDoCliente::create([
        'barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'regra_de_beneficio_id' => $regra->id,
        'atendimento_origem_id' => $atendimento->id, 'disponivel_em' => now('UTC')->subMinute(),
        'expira_em' => now('UTC')->addDays(10), 'status' => 'disponivel',
    ]);

    app(AgendadorDeLembretes::class)->agendarParaBeneficio($beneficio);
    expect(Lembrete::query()->where('beneficio_do_cliente_id', $beneficio->id)->count())->toBe(2)
        ->and(Lembrete::query()->where('beneficio_do_cliente_id', $beneficio->id)->where('tipo', 'beneficio_disponivel')->value('agendado_para'))->not->toBeNull();
});
