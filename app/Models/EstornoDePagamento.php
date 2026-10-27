<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstornoDePagamento extends Model
{
    use PertenceABarbearia;

    protected $table = 'estornos_de_pagamentos';

    protected $fillable = [
        'barbearia_id', 'pagamento_id', 'registrado_por_usuario_id', 'valor',
        'motivo', 'chave_idempotencia', 'estornado_em',
    ];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'estornado_em' => 'immutable_datetime'];
    }

    public function pagamento(): BelongsTo
    {
        return $this->belongsTo(Pagamento::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por_usuario_id');
    }
}
