<?php

namespace App\Console\Commands;

use App\Models\BeneficioDoCliente;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ExpirarBeneficiosVencidos extends Command
{
    protected $signature = 'beneficios:expirar';

    protected $description = 'Marca como expirados os benefícios disponíveis que venceram';

    public function handle(): int
    {
        $agora = CarbonImmutable::now('UTC');
        $quantidade = BeneficioDoCliente::query()
            ->where('status', 'disponivel')
            ->where('expira_em', '<=', $agora)
            ->update(['status' => 'expirado', 'updated_at' => $agora]);

        $this->info("{$quantidade} benefício(s) expirado(s).");

        return self::SUCCESS;
    }
}
