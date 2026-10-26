<?php

use App\Enums\PapelDeUsuario;
use App\Models\Agendamento;
use App\Models\Barbearia;
use App\Models\BloqueioDeAgenda;
use App\Models\Cliente;
use App\Models\HorarioDeTrabalho;
use App\Models\PausaDeTrabalho;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Usuario;
use App\Services\Agenda\CalculadoraDeDisponibilidade;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepararBloqueioDeAgenda(string $slug = 'navalha-agenda'): array
{
    $barbearia = Barbearia::create(['nome' => 'Navalha', 'slug' => $slug, 'fuso_horario' => 'America/Araguaina', 'ativa' => true]);
    $profissional = Profissional::create(['barbearia_id' => $barbearia->id, 'nome' => 'Ana', 'ativo' => true]);
    $servico = Servico::create(['barbearia_id' => $barbearia->id, 'nome' => 'Corte', 'duracao_em_minutos' => 30, 'preco' => 40, 'ativo' => true]);
    $profissional->servicos()->attach($servico->id);
    foreach (range(0, 6) as $dia) {
        HorarioDeTrabalho::create(['barbearia_id' => $barbearia->id, 'profissional_id' => $profissional->id, 'dia_da_semana' => $dia, 'inicio_as' => '00:00', 'fim_as' => '23:59', 'ativo' => true]);
    }
    $proprietario = Usuario::create(['barbearia_id' => $barbearia->id, 'nome' => 'Dono', 'email' => $slug.'@teste.local', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true]);

    return [$barbearia, $profissional, $servico, $proprietario];
}

it('cria bloqueio no fuso local e armazena o intervalo em UTC', function (): void {
    [$barbearia, $profissional, , $proprietario] = prepararBloqueioDeAgenda();

    $this->actingAs($proprietario)->post("/configuracoes/profissionais/{$profissional->id}/bloqueios", [
        'inicio_em' => '2026-10-26T10:00',
        'fim_em' => '2026-10-26T11:30',
        'motivo' => 'Folga',
    ])->assertRedirect()->assertSessionHas('sucesso');

    $bloqueio = BloqueioDeAgenda::firstOrFail();
    expect($bloqueio->barbearia_id)->toBe($barbearia->id)
        ->and($bloqueio->inicio_em->toIso8601String())->toBe('2026-10-26T13:00:00+00:00')
        ->and($bloqueio->fim_em->toIso8601String())->toBe('2026-10-26T14:30:00+00:00');
});

it('remove da disponibilidade o intervalo bloqueado', function (): void {
    [, $profissional] = prepararBloqueioDeAgenda();
    BloqueioDeAgenda::create([
        'barbearia_id' => $profissional->barbearia_id,
        'profissional_id' => $profissional->id,
        'inicio_em' => CarbonImmutable::parse('2026-10-26 13:00:00', 'UTC'),
        'fim_em' => CarbonImmutable::parse('2026-10-26 14:30:00', 'UTC'),
    ]);
    $calculadora = app(CalculadoraDeDisponibilidade::class);

    expect($calculadora->profissionalEstaDisponivel($profissional, CarbonImmutable::parse('2026-10-26 13:30:00', 'UTC'), CarbonImmutable::parse('2026-10-26 14:00:00', 'UTC')))->toBeFalse()
        ->and($calculadora->profissionalEstaDisponivel($profissional, CarbonImmutable::parse('2026-10-26 14:30:00', 'UTC'), CarbonImmutable::parse('2026-10-26 15:00:00', 'UTC')))->toBeTrue();
});

it('impede bloqueio que cruza reserva existente e não permite remover bloqueio de outra empresa', function (): void {
    [$barbearia, $profissional, $servico, $proprietario] = prepararBloqueioDeAgenda();
    [, $profissionalAlheio] = prepararBloqueioDeAgenda('outra-navalha');
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Cliente', 'telefone' => '63999990000']);
    Agendamento::create([
        'barbearia_id' => $barbearia->id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $profissional->id,
        'servico_id' => $servico->id,
        'inicio_em' => '2026-10-26 13:00:00+00:00',
        'fim_em' => '2026-10-26 13:30:00+00:00',
        'status' => 'confirmado',
    ]);

    $this->actingAs($proprietario)->post("/configuracoes/profissionais/{$profissional->id}/bloqueios", [
        'inicio_em' => '2026-10-26T10:00',
        'fim_em' => '2026-10-26T11:30',
    ])->assertRedirect()->assertSessionHasErrors('inicio_em');
    expect(BloqueioDeAgenda::query()->count())->toBe(0);

    $bloqueioAlheio = BloqueioDeAgenda::create([
        'barbearia_id' => $profissionalAlheio->barbearia_id,
        'profissional_id' => $profissionalAlheio->id,
        'inicio_em' => '2026-10-26 13:00:00+00:00',
        'fim_em' => '2026-10-26 14:00:00+00:00',
    ]);
    $this->delete("/configuracoes/profissionais/{$profissional->id}/bloqueios/{$bloqueioAlheio->id}")->assertNotFound();
    expect(BloqueioDeAgenda::query()->find($bloqueioAlheio->id))->not->toBeNull();
});

it('aplica a pausa semanal na disponibilidade todas as semanas e permite removê-la', function (): void {
    [, $profissional] = prepararBloqueioDeAgenda();
    $horario = HorarioDeTrabalho::query()->where('profissional_id', $profissional->id)->where('dia_da_semana', 1)->firstOrFail();
    $pausa = PausaDeTrabalho::create([
        'barbearia_id' => $profissional->barbearia_id,
        'horario_de_trabalho_id' => $horario->id,
        'inicio_as' => '12:00',
        'fim_as' => '13:00',
    ]);
    $calculadora = app(CalculadoraDeDisponibilidade::class);

    expect($calculadora->profissionalEstaDisponivel($profissional, CarbonImmutable::parse('2026-10-26 15:00:00', 'UTC'), CarbonImmutable::parse('2026-10-26 15:30:00', 'UTC')))->toBeFalse()
        ->and($calculadora->profissionalEstaDisponivel($profissional, CarbonImmutable::parse('2026-11-02 15:00:00', 'UTC'), CarbonImmutable::parse('2026-11-02 15:30:00', 'UTC')))->toBeFalse()
        ->and($calculadora->profissionalEstaDisponivel($profissional, CarbonImmutable::parse('2026-10-26 14:30:00', 'UTC'), CarbonImmutable::parse('2026-10-26 15:00:00', 'UTC')))->toBeTrue();

    $this->actingAs(Usuario::query()->where('barbearia_id', $profissional->barbearia_id)->firstOrFail())
        ->delete("/configuracoes/profissionais/{$profissional->id}/horarios/{$horario->id}/pausas/{$pausa->id}")
        ->assertRedirect();
    expect(PausaDeTrabalho::query()->find($pausa->id))->toBeNull();
});

it('rejeita pausa fora do turno ou sobreposta a outra pausa', function (): void {
    [, $profissional, , $proprietario] = prepararBloqueioDeAgenda();
    $horario = HorarioDeTrabalho::query()->where('profissional_id', $profissional->id)->where('dia_da_semana', 1)->firstOrFail();
    $horario->update(['inicio_as' => '08:00', 'fim_as' => '18:00']);

    $url = "/configuracoes/profissionais/{$profissional->id}/horarios/{$horario->id}/pausas";
    $this->actingAs($proprietario)->post($url, ['inicio_as' => '07:00', 'fim_as' => '07:30'])->assertSessionHasErrors('inicio_as');
    $this->post($url, ['inicio_as' => '12:00', 'fim_as' => '13:00'])->assertRedirect()->assertSessionHas('sucesso');
    $this->post($url, ['inicio_as' => '12:30', 'fim_as' => '13:30'])->assertSessionHasErrors('inicio_as');
    expect(PausaDeTrabalho::query()->count())->toBe(1);
});
