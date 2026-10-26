<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PausaDeTrabalho extends Model
{
    use PertenceABarbearia;

    protected $table = 'pausas_de_trabalho';

    protected $fillable = ['barbearia_id', 'horario_de_trabalho_id', 'inicio_as', 'fim_as'];

    public function horarioDeTrabalho(): BelongsTo
    {
        return $this->belongsTo(HorarioDeTrabalho::class);
    }
}
