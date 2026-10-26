<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegraDeBeneficio extends Model
{
    use PertenceABarbearia;

    protected $table = 'regras_de_beneficio';

    protected $fillable = ['barbearia_id', 'servico_originador_id', 'servico_beneficiado_id', 'disponivel_apos_dias', 'expira_apos_dias', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean', 'disponivel_apos_dias' => 'integer', 'expira_apos_dias' => 'integer'];
    }

    public function servicoOriginador(): BelongsTo
    {
        return $this->belongsTo(Servico::class, 'servico_originador_id');
    }

    public function servicoBeneficiado(): BelongsTo
    {
        return $this->belongsTo(Servico::class, 'servico_beneficiado_id');
    }
}
