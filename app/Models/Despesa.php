<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Despesa extends Model
{
    use PertenceABarbearia;

    protected $table = 'despesas';

    protected $fillable = ['barbearia_id', 'registrada_por_usuario_id', 'descricao', 'categoria', 'valor', 'movimentada_em', 'chave_idempotencia'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'movimentada_em' => 'immutable_datetime'];
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrada_por_usuario_id');
    }
}
