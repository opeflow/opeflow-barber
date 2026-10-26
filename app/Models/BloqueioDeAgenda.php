<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloqueioDeAgenda extends Model
{
    use PertenceABarbearia;

    protected $table = 'bloqueios_de_agenda';

    protected $fillable = ['barbearia_id', 'profissional_id', 'criado_por_usuario_id', 'inicio_em', 'fim_em', 'motivo'];

    protected function casts(): array
    {
        return ['inicio_em' => 'immutable_datetime', 'fim_em' => 'immutable_datetime'];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por_usuario_id');
    }
}
