<?php

namespace App\Services\Beneficios;

use App\Models\BeneficioDoCliente;

class CodigoDeResgateDeBeneficio
{
    public function gerar(BeneficioDoCliente $beneficio): string
    {
        $chave = (string) config('app.key');

        return strtoupper(substr(hash_hmac('sha256', $beneficio->barbearia_id.':'.$beneficio->id, $chave), 0, 20));
    }
}
