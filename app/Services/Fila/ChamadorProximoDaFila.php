<?php

namespace App\Services\Fila;

use App\Models\FilaDeEspera;
use App\Models\ItemDaFila;
use App\Models\Profissional;
use App\Services\Agenda\CalculadoraDeDisponibilidade;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChamadorProximoDaFila
{
    public function __construct(private readonly CalculadoraDeDisponibilidade $disponibilidade) {}

    public function chamarProximoCliente(FilaDeEspera $fila): ItemDaFila
    {
        return DB::transaction(function () use ($fila): ItemDaFila {
            $filaBloqueada = FilaDeEspera::query()
                ->where('barbearia_id', $fila->barbearia_id)
                ->whereKey($fila->id)
                ->lockForUpdate()
                ->firstOrFail();

            $itens = $filaBloqueada->itens()
                ->where('status', 'aguardando')
                ->with(['servico', 'profissionalPreferido'])
                ->orderBy('chegou_em')
                ->orderByDesc('prioridade')
                ->get();

            foreach ($itens as $item) {
                $profissionais = Profissional::query()
                    ->where('barbearia_id', $filaBloqueada->barbearia_id)
                    ->where('ativo', true)
                    ->whereHas('servicos', fn ($consulta) => $consulta->whereKey($item->servico_id))
                    ->when($item->profissional_preferido_id, function ($consulta) use ($item): void {
                        $consulta->orderByRaw('id = ? DESC', [$item->profissional_preferido_id]);
                    })
                    ->get();

                foreach ($profissionais as $profissional) {
                    $profissional = Profissional::query()
                        ->where('barbearia_id', $filaBloqueada->barbearia_id)
                        ->whereKey($profissional->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $jaAtendendoNaFila = ItemDaFila::query()
                        ->where('barbearia_id', $filaBloqueada->barbearia_id)
                        ->where('profissional_id', $profissional->id)
                        ->whereIn('status', ['chamado', 'em_atendimento'])
                        ->exists();

                    $inicio = CarbonImmutable::now('UTC');
                    $fim = $inicio->addMinutes($item->servico->duracao_em_minutos);

                    if ($jaAtendendoNaFila || ! $this->disponibilidade->profissionalEstaDisponivel($profissional, $inicio, $fim)) {
                        continue;
                    }

                    $item->update([
                        'profissional_id' => $profissional->id,
                        'previsao_atendimento_em' => $inicio,
                        'status' => 'chamado',
                    ]);

                    return $item->refresh();
                }
            }

            throw ValidationException::withMessages([
                'fila' => 'Não há profissional disponível para os serviços aguardando na fila neste momento.',
            ]);
        });
    }
}
