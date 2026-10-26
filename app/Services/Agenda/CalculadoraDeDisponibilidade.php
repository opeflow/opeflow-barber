<?php

namespace App\Services\Agenda;

use App\Models\Agendamento;
use App\Models\BloqueioDeAgenda;
use App\Models\HorarioDeTrabalho;
use App\Models\ItemDaFila;
use App\Models\PausaDeTrabalho;
use App\Models\Profissional;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CalculadoraDeDisponibilidade
{
    public function profissionalEstaDisponivel(Profissional $profissional, CarbonInterface $inicioEmUtc, CarbonInterface $fimEmUtc): bool
    {
        if (! $profissional->ativo || $inicioEmUtc->greaterThanOrEqualTo($fimEmUtc)) {
            return false;
        }

        return $this->estaDentroDoHorarioDeTrabalho($profissional, $inicioEmUtc, $fimEmUtc)
            && ! $this->possuiConflitoDeBloqueio($profissional, $inicioEmUtc, $fimEmUtc)
            && ! $this->possuiConflitoDeAgendamento($profissional, $inicioEmUtc, $fimEmUtc)
            && ! $this->possuiConflitoDeAtendimentoPresencial($profissional, $inicioEmUtc, $fimEmUtc);
    }

    private function possuiConflitoDeBloqueio(Profissional $profissional, CarbonInterface $inicioEmUtc, CarbonInterface $fimEmUtc): bool
    {
        return BloqueioDeAgenda::query()
            ->where('barbearia_id', $profissional->barbearia_id)
            ->where('profissional_id', $profissional->id)
            ->where('inicio_em', '<', $fimEmUtc)
            ->where('fim_em', '>', $inicioEmUtc)
            ->exists();
    }

    public function possuiConflitoDeAgendamento(Profissional $profissional, CarbonInterface $inicioEmUtc, CarbonInterface $fimEmUtc): bool
    {
        return Agendamento::query()
            ->where('barbearia_id', $profissional->barbearia_id)
            ->where('profissional_id', $profissional->id)
            ->whereNotIn('status', ['cancelado', 'reagendado', 'nao_compareceu'])
            ->where('inicio_em', '<', $fimEmUtc)
            ->where('fim_em', '>', $inicioEmUtc)
            ->exists();
    }

    private function estaDentroDoHorarioDeTrabalho(Profissional $profissional, CarbonInterface $inicioEmUtc, CarbonInterface $fimEmUtc): bool
    {
        $fusoHorario = $profissional->barbearia->fuso_horario;
        $inicioLocal = $inicioEmUtc->copy()->setTimezone($fusoHorario);
        $fimLocal = $fimEmUtc->copy()->setTimezone($fusoHorario);

        if ($inicioLocal->toDateString() !== $fimLocal->toDateString()) {
            return false;
        }

        return HorarioDeTrabalho::query()
            ->where('barbearia_id', $profissional->barbearia_id)
            ->where('profissional_id', $profissional->id)
            ->where('dia_da_semana', $inicioLocal->dayOfWeek)
            ->where('ativo', true)
            ->where('inicio_as', '<=', $inicioLocal->format('H:i:s'))
            ->where('fim_as', '>=', $fimLocal->format('H:i:s'))
            ->exists()
            && ! PausaDeTrabalho::query()
                ->where('barbearia_id', $profissional->barbearia_id)
                ->whereHas('horarioDeTrabalho', fn ($consulta) => $consulta
                    ->where('profissional_id', $profissional->id)
                    ->where('dia_da_semana', $inicioLocal->dayOfWeek)
                    ->where('ativo', true))
                ->where('inicio_as', '<', $fimLocal->format('H:i:s'))
                ->where('fim_as', '>', $inicioLocal->format('H:i:s'))
                ->exists();
    }

    private function possuiConflitoDeAtendimentoPresencial(Profissional $profissional, CarbonInterface $inicioEmUtc, CarbonInterface $fimEmUtc): bool
    {
        $itensAtivos = ItemDaFila::query()
            ->where('barbearia_id', $profissional->barbearia_id)
            ->where('profissional_id', $profissional->id)
            ->whereIn('status', ['chamado', 'em_atendimento'])
            ->with(['servico:id,duracao_em_minutos', 'atendimento:id,iniciado_em'])
            ->get();

        return $itensAtivos->contains(function (ItemDaFila $item) use ($inicioEmUtc, $fimEmUtc): bool {
            $inicioPresencial = $item->atendimento?->iniciado_em ?? CarbonImmutable::now('UTC');
            $fimPresencial = $inicioPresencial->addMinutes($item->servico->duracao_em_minutos);

            return $inicioPresencial->lessThan($fimEmUtc) && $fimPresencial->greaterThan($inicioEmUtc);
        });
    }
}
