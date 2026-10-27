<?php

namespace App\Services\Financeiro;

use App\Models\Barbearia;
use App\Models\Despesa;
use App\Models\EstornoDePagamento;
use App\Models\FechamentoDeCaixa;
use App\Models\Pagamento;
use App\Models\ReaberturaDeCaixa;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FechadorDeCaixa
{
    public function fechar(
        Barbearia $barbearia,
        string $data,
        int $valorConferidoCentavos,
        string $chaveIdempotencia,
        Usuario $usuario,
    ): FechamentoDeCaixa {
        return DB::transaction(function () use ($barbearia, $data, $valorConferidoCentavos, $chaveIdempotencia, $usuario): FechamentoDeCaixa {
            $barbearia = Barbearia::query()->whereKey($barbearia->id)->lockForUpdate()->firstOrFail();
            $existente = FechamentoDeCaixa::query()->where('barbearia_id', $barbearia->id)
                ->where('chave_idempotencia', $chaveIdempotencia)->first();

            if ($existente) {
                abort_unless(
                    $existente->data_operacional->toDateString() === $data
                        && (int) round((float) $existente->valor_conferido * 100) === $valorConferidoCentavos,
                    409,
                    'A chave de idempotência já foi usada em outro fechamento.',
                );

                return $existente;
            }

            $hojeLocal = CarbonImmutable::now($barbearia->fuso_horario)->toDateString();
            if ($data > $hojeLocal) {
                throw ValidationException::withMessages(['data' => 'Não é possível fechar uma data futura.']);
            }

            $ultimo = $this->ultimoFechamento($barbearia->id, $data);
            if ($ultimo && ! $ultimo->reabertura()->exists()) {
                throw ValidationException::withMessages(['data' => 'Este caixa já está fechado. Reabra-o com um motivo antes de registrar nova versão.']);
            }

            [$inicioUtc, $fimUtc] = $this->limitesUtc($data, $barbearia->fuso_horario);
            $pagamentos = Pagamento::query()->where('barbearia_id', $barbearia->id)
                ->where('registrado_em', '>=', $inicioUtc)->where('registrado_em', '<', $fimUtc);
            $recebidoCentavos = (int) round((float) (clone $pagamentos)->sum('valor') * 100);
            $estornadoCentavos = (int) round((float) EstornoDePagamento::query()->where('barbearia_id', $barbearia->id)
                ->where('estornado_em', '>=', $inicioUtc)->where('estornado_em', '<', $fimUtc)->sum('valor') * 100);
            $despesasCentavos = (int) round((float) Despesa::query()->where('barbearia_id', $barbearia->id)
                ->where('movimentada_em', '>=', $inicioUtc)->where('movimentada_em', '<', $fimUtc)->sum('valor') * 100);
            $saldoEsperadoCentavos = $recebidoCentavos - $estornadoCentavos - $despesasCentavos;
            $totaisPorForma = (clone $pagamentos)->select('forma_pagamento', DB::raw('SUM(valor) as total'))
                ->groupBy('forma_pagamento')->orderBy('forma_pagamento')->get()
                ->mapWithKeys(fn ($linha): array => [$linha->forma_pagamento => number_format((float) $linha->total, 2, '.', '')])
                ->all();

            return FechamentoDeCaixa::create([
                'barbearia_id' => $barbearia->id,
                'data_operacional' => $data,
                'versao' => ($ultimo?->versao ?? 0) + 1,
                'fechado_por_usuario_id' => $usuario->id,
                'total_recebido' => number_format($recebidoCentavos / 100, 2, '.', ''),
                'total_estornado' => number_format($estornadoCentavos / 100, 2, '.', ''),
                'total_despesas' => number_format($despesasCentavos / 100, 2, '.', ''),
                'saldo_esperado' => number_format($saldoEsperadoCentavos / 100, 2, '.', ''),
                'valor_conferido' => number_format($valorConferidoCentavos / 100, 2, '.', ''),
                'diferenca' => number_format(($valorConferidoCentavos - $saldoEsperadoCentavos) / 100, 2, '.', ''),
                'totais_por_forma' => $totaisPorForma,
                'chave_idempotencia' => $chaveIdempotencia,
                'fechado_em' => CarbonImmutable::now('UTC'),
            ]);
        });
    }

    public function reabrir(Barbearia $barbearia, FechamentoDeCaixa $fechamento, string $motivo, Usuario $usuario): ReaberturaDeCaixa
    {
        return DB::transaction(function () use ($barbearia, $fechamento, $motivo, $usuario): ReaberturaDeCaixa {
            Barbearia::query()->whereKey($barbearia->id)->lockForUpdate()->firstOrFail();
            abort_unless($fechamento->barbearia_id === $barbearia->id, 404);

            $ultimo = $this->ultimoFechamento($barbearia->id, $fechamento->data_operacional->toDateString());
            abort_unless($ultimo?->id === $fechamento->id, 404);

            return ReaberturaDeCaixa::firstOrCreate(
                ['fechamento_de_caixa_id' => $fechamento->id],
                [
                    'barbearia_id' => $barbearia->id,
                    'reaberto_por_usuario_id' => $usuario->id,
                    'motivo' => $motivo,
                    'reaberto_em' => CarbonImmutable::now('UTC'),
                ],
            );
        });
    }

    public function garantirDiaAberto(int $barbeariaId, string $data): void
    {
        $ultimo = $this->ultimoFechamento($barbeariaId, $data);
        if ($ultimo && ! $ultimo->reabertura()->exists()) {
            throw ValidationException::withMessages(['data' => 'O caixa desta data está fechado. Reabra-o com um motivo antes de lançar movimentações.']);
        }
    }

    public function fechamentoAtual(int $barbeariaId, string $data): ?FechamentoDeCaixa
    {
        return $this->ultimoFechamento($barbeariaId, $data)?->loadMissing('reabertura.reabertoPor:id,nome', 'fechadoPor:id,nome');
    }

    private function ultimoFechamento(int $barbeariaId, string $data): ?FechamentoDeCaixa
    {
        return FechamentoDeCaixa::query()->where('barbearia_id', $barbeariaId)->whereDate('data_operacional', $data)
            ->orderByDesc('versao')->first();
    }

    private function limitesUtc(string $data, string $fuso): array
    {
        $inicio = CarbonImmutable::parse($data, $fuso)->startOfDay()->utc();

        return [$inicio, $inicio->addDay()];
    }
}
