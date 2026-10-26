<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficioDoCliente extends Model
{
    use PertenceABarbearia;

    protected $table = 'beneficios_dos_clientes';

    protected $fillable = ['barbearia_id', 'cliente_id', 'regra_de_beneficio_id', 'atendimento_origem_id', 'disponivel_em', 'expira_em', 'status', 'utilizado_em'];

    protected function casts(): array
    {
        return ['disponivel_em' => 'immutable_datetime', 'expira_em' => 'immutable_datetime', 'utilizado_em' => 'immutable_datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function regra(): BelongsTo
    {
        return $this->belongsTo(RegraDeBeneficio::class, 'regra_de_beneficio_id');
    }
}
