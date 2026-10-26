<?php

use App\Enums\PapelDeUsuario;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\FilaDeEspera;
use App\Models\HorarioDeTrabalho;
use App\Models\ItemDaFila;
use App\Models\Profissional;
use App\Models\RegraDeBeneficio;
use App\Models\Servico;
use App\Models\Usuario;
use App\Services\Beneficios\CodigoDeResgateDeBeneficio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function prepararBarbeariaPublica(string $slug = 'barbearia-publica'): array
{
    $barbearia = Barbearia::create(['nome' => 'Barbearia Pública', 'slug' => $slug, 'fuso_horario' => 'UTC', 'ativa' => true]);
    $servico = Servico::create(['barbearia_id' => $barbearia->id, 'nome' => 'Corte', 'duracao_em_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $profissional = Profissional::create(['barbearia_id' => $barbearia->id, 'nome' => 'João', 'ativo' => true]);
    $profissional->servicos()->attach($servico->id);
    foreach (range(0, 6) as $dia) {
        HorarioDeTrabalho::create(['barbearia_id' => $barbearia->id, 'profissional_id' => $profissional->id, 'dia_da_semana' => $dia, 'inicio_as' => '00:00', 'fim_as' => '23:59', 'ativo' => true]);
    }

    return [$barbearia, $servico, $profissional];
}

it('exibe o fluxo de agendamento sem exigir autenticação', function (): void {
    [$barbearia, $servico] = prepararBarbeariaPublica();
    $this->get('/agendar/'.$barbearia->slug)->assertOk()->assertInertia(fn ($pagina) => $pagina->component('AgendamentoPublico/Index')->where('barbearia.nome', $barbearia->nome)->has('servicos', 1));
});

it('cria cliente e agendamento e processa reenvio idempotente', function (): void {
    [$barbearia, $servico, $profissional] = prepararBarbeariaPublica();
    $dados = ['servico_id' => $servico->id, 'profissional_id' => $profissional->id, 'data' => now('UTC')->addDays(2)->toDateString(), 'hora' => '12:00', 'nome' => 'Cliente Online', 'telefone' => '(63) 99999-1234', 'email' => null, 'aceita_lembretes' => true, 'chave_idempotencia' => '1ba93093-32c8-4e67-b48d-6a2584427519'];

    $this->post('/agendar/'.$barbearia->slug, $dados)->assertRedirect('/agendar/'.$barbearia->slug)->assertSessionHas('reserva_publica');
    $this->post('/agendar/'.$barbearia->slug, $dados)->assertRedirect('/agendar/'.$barbearia->slug);

    expect(Agendamento::query()->count())->toBe(1)->and(Cliente::query()->count())->toBe(1);
    expect(Cliente::query()->firstOrFail()->aceita_lembretes)->toBeTrue();
    $this->assertDatabaseCount('lembretes', 2);
    $this->assertDatabaseHas('agendamentos', ['barbearia_id' => $barbearia->id, 'status' => 'confirmado', 'chave_idempotencia_publica' => $dados['chave_idempotencia']]);
});

it('rejeita serviço ou profissional pertencente a outra barbearia', function (): void {
    [$barbearia] = prepararBarbeariaPublica();
    [, $servicoAlheio, $profissionalAlheio] = prepararBarbeariaPublica('outra-barbearia-publica');
    $this->from('/agendar/'.$barbearia->slug)->post('/agendar/'.$barbearia->slug, [
        'servico_id' => $servicoAlheio->id,
        'profissional_id' => $profissionalAlheio->id,
        'data' => now('UTC')->addDays(2)->toDateString(),
        'hora' => '12:00',
        'nome' => 'Cliente Online',
        'telefone' => '63999991234',
        'chave_idempotencia' => '2ba93093-32c8-4e67-b48d-6a2584427520',
    ])->assertSessionHasErrors(['servico_id', 'profissional_id']);

    expect(Agendamento::query()->count())->toBe(0);
});

it('não oferece horário ocupado por atendimento presencial ativo', function (): void {
    [$barbearia, $servico, $profissional] = prepararBarbeariaPublica();
    Carbon::setTestNow('2026-10-23 10:00:00');
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Na fila', 'telefone' => '63999998888']);
    $fila = FilaDeEspera::create(['barbearia_id' => $barbearia->id, 'data_operacional' => now('UTC')->toDateString()]);
    ItemDaFila::create(['barbearia_id' => $barbearia->id, 'fila_de_espera_id' => $fila->id, 'cliente_id' => $cliente->id, 'servico_id' => $servico->id, 'profissional_id' => $profissional->id, 'chegou_em' => now('UTC'), 'posicao' => 1, 'status' => 'chamado']);

    $data = now('UTC')->toDateString();
    $this->get('/agendar/'.$barbearia->slug.'?'.http_build_query(['servico_id' => $servico->id, 'data' => $data]))
        ->assertInertia(fn ($pagina) => $pagina->where('horarios.0.hora', '10:30'));
    Carbon::setTestNow();
});

it('reserva o benefício correto no agendamento público e o libera ao cancelar', function (): void {
    [$barbearia, $servicoOriginador, $profissional] = prepararBarbeariaPublica('barbearia-beneficio');
    $servicoPezinho = Servico::create(['barbearia_id' => $barbearia->id, 'nome' => 'Pezinho', 'duracao_em_minutos' => 15, 'preco' => 20, 'ativo' => true]);
    $profissional->servicos()->attach($servicoPezinho->id);
    $regra = RegraDeBeneficio::create(['barbearia_id' => $barbearia->id, 'servico_originador_id' => $servicoOriginador->id, 'servico_beneficiado_id' => $servicoPezinho->id, 'disponivel_apos_dias' => 0, 'expira_apos_dias' => 14, 'ativo' => true]);
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Cliente Fidelizado', 'telefone' => '63999991234']);
    $atendimento = Atendimento::create(['barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'profissional_id' => $profissional->id, 'servico_id' => $servicoOriginador->id, 'iniciado_em' => now('UTC')->subDays(2), 'concluido_em' => now('UTC')->subDay(), 'valor_total' => 50, 'status' => 'concluido']);
    $beneficio = BeneficioDoCliente::create(['barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'regra_de_beneficio_id' => $regra->id, 'atendimento_origem_id' => $atendimento->id, 'disponivel_em' => now('UTC')->subHour(), 'expira_em' => now('UTC')->addDays(10), 'status' => 'disponivel']);
    $codigo = app(CodigoDeResgateDeBeneficio::class)->gerar($beneficio);
    $inicioLocal = now('UTC')->addDays(3)->setTimezone($barbearia->fuso_horario)->setTime(12, 0);
    $dados = ['servico_id' => $servicoPezinho->id, 'profissional_id' => $profissional->id, 'data' => $inicioLocal->toDateString(), 'hora' => '12:00', 'nome' => $cliente->nome, 'telefone' => $cliente->telefone, 'codigo_beneficio' => $codigo, 'chave_idempotencia' => '8ba93093-32c8-4e67-b48d-6a2584427501'];

    $this->post('/agendar/'.$barbearia->slug, $dados)->assertRedirect('/agendar/'.$barbearia->slug)->assertSessionHas('reserva_publica');
    $agendamento = Agendamento::query()->whereNotNull('beneficio_do_cliente_id')->firstOrFail();
    expect($agendamento->beneficio_do_cliente_id)->toBe($beneficio->id)->and($beneficio->fresh()->status)->toBe('reservado');

    $proprietario = Usuario::create(['barbearia_id' => $barbearia->id, 'nome' => 'Dono', 'email' => 'dono-beneficio@teste.local', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true]);
    $this->actingAs($proprietario)->delete('/agenda/'.$agendamento->id)->assertRedirect();
    expect($beneficio->fresh()->status)->toBe('disponivel');

    $dados['chave_idempotencia'] = '8ba93093-32c8-4e67-b48d-6a2584427504';
    $this->post('/agendar/'.$barbearia->slug, $dados)->assertRedirect();
    $agendamento = Agendamento::query()->whereNotNull('beneficio_do_cliente_id')->where('status', 'confirmado')->firstOrFail();
    $agendamento->update(['inicio_em' => now('UTC')->subMinutes(40), 'fim_em' => now('UTC')->subMinutes(20)]);
    $this->post('/agenda/'.$agendamento->id.'/atendido')->assertRedirect();
    expect($beneficio->fresh()->status)->toBe('utilizado')
        ->and($agendamento->fresh()->status)->toBe('atendido');
});

it('não permite resgatar benefício de outra pessoa ou para outro serviço', function (): void {
    [$barbearia, $servico, $profissional] = prepararBarbeariaPublica('barbearia-beneficio-negado');
    $servicoPezinho = Servico::create(['barbearia_id' => $barbearia->id, 'nome' => 'Pezinho', 'duracao_em_minutos' => 15, 'preco' => 20, 'ativo' => true]);
    $profissional->servicos()->attach($servicoPezinho->id);
    $regra = RegraDeBeneficio::create(['barbearia_id' => $barbearia->id, 'servico_originador_id' => $servico->id, 'servico_beneficiado_id' => $servicoPezinho->id, 'disponivel_apos_dias' => 0, 'expira_apos_dias' => 14, 'ativo' => true]);
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Cliente', 'telefone' => '63999991234']);
    $atendimento = Atendimento::create(['barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'profissional_id' => $profissional->id, 'servico_id' => $servico->id, 'iniciado_em' => now('UTC')->subDay(), 'concluido_em' => now('UTC')->subHour(), 'valor_total' => 50, 'status' => 'concluido']);
    $beneficio = BeneficioDoCliente::create(['barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'regra_de_beneficio_id' => $regra->id, 'atendimento_origem_id' => $atendimento->id, 'disponivel_em' => now('UTC')->subHour(), 'expira_em' => now('UTC')->addDays(2), 'status' => 'disponivel']);
    $codigo = app(CodigoDeResgateDeBeneficio::class)->gerar($beneficio);
    $data = now('UTC')->addDays(3)->setTimezone('UTC')->toDateString();

    $this->from('/agendar/'.$barbearia->slug)->post('/agendar/'.$barbearia->slug, ['servico_id' => $servicoPezinho->id, 'profissional_id' => $profissional->id, 'data' => $data, 'hora' => '12:00', 'nome' => 'Outra pessoa', 'telefone' => '63999990000', 'codigo_beneficio' => $codigo, 'chave_idempotencia' => '8ba93093-32c8-4e67-b48d-6a2584427502'])->assertSessionHasErrors('codigo_beneficio');
    $this->from('/agendar/'.$barbearia->slug)->post('/agendar/'.$barbearia->slug, ['servico_id' => $servico->id, 'profissional_id' => $profissional->id, 'data' => $data, 'hora' => '12:00', 'nome' => $cliente->nome, 'telefone' => $cliente->telefone, 'codigo_beneficio' => $codigo, 'chave_idempotencia' => '8ba93093-32c8-4e67-b48d-6a2584427503'])->assertSessionHasErrors('codigo_beneficio');
    expect(Agendamento::query()->count())->toBe(0)->and($beneficio->fresh()->status)->toBe('disponivel');
});
