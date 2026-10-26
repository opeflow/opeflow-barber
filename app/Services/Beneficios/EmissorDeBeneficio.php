<?php

namespace App\Services\Beneficios;

use App\Models\Atendimento;
use App\Models\BeneficioDoCliente;
use App\Models\RegraDeBeneficio;
use App\Services\Lembretes\AgendadorDeLembretes;
use Carbon\CarbonImmutable;

class EmissorDeBeneficio
{
    public function emitirParaAtendimentoConcluido(Atendimento $atendimento): ?BeneficioDoCliente
    {
        $regra = RegraDeBeneficio::query()
            ->where('barbearia_id', $atendimento->barbearia_id)
            ->where('servico_originador_id', $atendimento->servico_id)
            ->where('ativo', true)
            ->first();

        if (! $regra) {
            return null;
        }

        $concluidoEm = $atendimento->concluido_em ?? CarbonImmutable::now('UTC');
        $disponivelEm = $concluidoEm->addDays($regra->disponivel_apos_dias);

        $beneficio = BeneficioDoCliente::firstOrCreate(
            [
                'barbearia_id' => $atendimento->barbearia_id,
                'atendimento_origem_id' => $atendimento->id,
                'regra_de_beneficio_id' => $regra->id,
            ],
            [
                'cliente_id' => $atendimento->cliente_id,
                'disponivel_em' => $disponivelEm,
                'expira_em' => $concluidoEm->addDays($regra->expira_apos_dias),
                'status' => 'disponivel',
            ],
        );
        app(AgendadorDeLembretes::class)->agendarParaBeneficio($beneficio);

        return $beneficio;
    }
}
