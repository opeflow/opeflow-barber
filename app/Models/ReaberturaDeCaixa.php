<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReaberturaDeCaixa extends Model
{
    use PertenceABarbearia;

    protected $table = 'reaberturas_de_caixa';

    protected $fillable = ['barbearia_id', 'fechamento_de_caixa_id', 'reaberto_por_usuario_id', 'motivo', 'reaberto_em'];

    protected function casts(): array
    {
        return ['reaberto_em' => 'immutable_datetime'];
    }

    public function fechamento(): BelongsTo
    {
        return $this->belongsTo(FechamentoDeCaixa::class, 'fechamento_de_caixa_id');
    }

    public function reabertoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'reaberto_por_usuario_id');
    }
}
