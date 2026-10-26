<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HorarioDeTrabalho extends Model
{
    use PertenceABarbearia;

    protected $table = 'horarios_de_trabalho';

    protected $fillable = [
        'barbearia_id', 'profissional_id', 'dia_da_semana', 'inicio_as', 'fim_as', 'ativo',
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function pausas(): HasMany
    {
        return $this->hasMany(PausaDeTrabalho::class, 'horario_de_trabalho_id');
    }
}
