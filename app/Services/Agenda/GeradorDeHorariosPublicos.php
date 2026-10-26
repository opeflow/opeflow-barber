<?php

namespace App\Services\Agenda;

use App\Models\Barbearia;
use App\Models\Profissional;
use App\Models\Servico;
use Carbon\CarbonImmutable;

class GeradorDeHorariosPublicos
{
    public function listarHorarios(Barbearia $barbearia, Servico $servico, string $data, ?int $profissionalId, CalculadoraDeDisponibilidade $calculadora): array
    {
        $profissionais = Profissional::query()
            ->where('barbearia_id', $barbearia->id)
            ->where('ativo', true)
            ->whereHas('servicos', fn ($consulta) => $consulta->whereKey($servico->id))
            ->when($profissionalId, fn ($consulta) => $consulta->whereKey($profissionalId))
            ->with(['horariosDeTrabalho' => fn ($consulta) => $consulta->where('dia_da_semana', CarbonImmutable::parse($data, $barbearia->fuso_horario)->dayOfWeek)->where('ativo', true)])
            ->orderBy('nome')
            ->get();

        $fusoHorario = $barbearia->fuso_horario;
        $agoraUtc = CarbonImmutable::now('UTC');
        $horarios = [];

        foreach ($profissionais as $profissional) {
            foreach ($profissional->horariosDeTrabalho as $jornada) {
                $inicioJornada = CarbonImmutable::parse($data.' '.$jornada->inicio_as, $fusoHorario);
                $fimJornada = CarbonImmutable::parse($data.' '.$jornada->fim_as, $fusoHorario);

                for ($inicio = $inicioJornada; $inicio->addMinutes($servico->duracao_em_minutos)->lessThanOrEqualTo($fimJornada); $inicio = $inicio->addMinutes(15)) {
                    $fim = $inicio->addMinutes($servico->duracao_em_minutos);
                    if ($inicio->lessThan($agoraUtc->setTimezone($fusoHorario))) {
                        continue;
                    }

                    $inicioUtc = $inicio->utc();
                    $fimUtc = $fim->utc();
                    if (! $calculadora->profissionalEstaDisponivel($profissional, $inicioUtc, $fimUtc)) {
                        continue;
                    }

                    $horarios[] = [
                        'hora' => $inicio->format('H:i'),
                        'profissionalId' => $profissional->id,
                        'profissional' => $profissional->nome,
                    ];
                }
            }
        }

        usort($horarios, fn (array $a, array $b): int => [$a['hora'], $a['profissional']] <=> [$b['hora'], $b['profissional']]);

        return $horarios;
    }
}
