<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pagamento extends Model
{
    use PertenceABarbearia;

    protected $table = 'pagamentos';

    protected $fillable = ['barbearia_id', 'atendimento_id', 'valor', 'forma_pagamento', 'chave_idempotencia', 'registrado_em'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'registrado_em' => 'immutable_datetime'];
    }

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class);
    }

    public function estornos(): HasMany
    {
        return $this->hasMany(EstornoDePagamento::class);
    }
}
