<?php

namespace App\Services\Lembretes;

use App\Models\Agendamento;
use App\Models\BeneficioDoCliente;
use App\Models\Lembrete;
use Carbon\CarbonImmutable;

class AgendadorDeLembretes
{
    public function agendarParaAgendamento(Agendamento $agendamento): void
    {
        $agendamento->loadMissing('cliente');
        if (! $agendamento->cliente->aceita_lembretes) {
            return;
        }

        foreach ([24 => 'agendamento_24_horas', 2 => 'agendamento_2_horas'] as $horas => $tipo) {
            $horario = $agendamento->inicio_em->subHours($horas);
            if ($horario->isFuture()) {
                Lembrete::firstOrCreate(['chave_idempotencia' => "agendamento:{$agendamento->id}:{$horas}h"], [
                    'barbearia_id' => $agendamento->barbearia_id, 'cliente_id' => $agendamento->cliente_id,
                    'agendamento_id' => $agendamento->id, 'tipo' => $tipo, 'agendado_para' => $horario, 'status' => 'pendente',
                ]);
            }
        }
    }

    public function agendarParaBeneficio(BeneficioDoCliente $beneficio): void
    {
        $beneficio->loadMissing('cliente');
        if (! $beneficio->cliente->aceita_lembretes) {
            return;
        }

        $avisos = [
            'beneficio_disponivel' => $beneficio->disponivel_em,
            'beneficio_expirando' => $beneficio->expira_em->subDay(),
        ];
        $agora = CarbonImmutable::now('UTC');
        foreach ($avisos as $tipo => $horario) {
            $horarioDoLembrete = $tipo === 'beneficio_disponivel' && $horario->lessThanOrEqualTo($agora)
                ? $agora
                : $horario;
            $horarioValido = $horarioDoLembrete->isFuture() || ($tipo === 'beneficio_disponivel' && $horario->lessThanOrEqualTo($agora));
            if ($beneficio->expira_em->greaterThan($agora) && $horarioValido && $horarioDoLembrete->lessThan($beneficio->expira_em)) {
                Lembrete::firstOrCreate(['chave_idempotencia' => "beneficio:{$beneficio->id}:{$tipo}"], [
                    'barbearia_id' => $beneficio->barbearia_id, 'cliente_id' => $beneficio->cliente_id,
                    'beneficio_do_cliente_id' => $beneficio->id, 'tipo' => $tipo, 'agendado_para' => $horarioDoLembrete, 'status' => 'pendente',
                ]);
            }
        }
    }

    public function cancelarPendentesDoAgendamento(Agendamento $agendamento): void
    {
        Lembrete::query()->where('barbearia_id', $agendamento->barbearia_id)->where('agendamento_id', $agendamento->id)
            ->where('status', 'pendente')->update(['status' => 'cancelado', 'updated_at' => CarbonImmutable::now('UTC')]);
    }
}
