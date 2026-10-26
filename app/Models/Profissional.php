<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profissional extends Model
{
    protected $table = 'profissionais';

    protected $fillable = ['barbearia_id', 'nome', 'telefone', 'ativo'];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }

    public function barbearia(): BelongsTo
    {
        return $this->belongsTo(Barbearia::class);
    }

    public function servicos(): BelongsToMany
    {
        return $this->belongsToMany(Servico::class, 'profissional_servico');
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function horariosDeTrabalho(): HasMany
    {
        return $this->hasMany(HorarioDeTrabalho::class);
    }

    public function bloqueiosDeAgenda(): HasMany
    {
        return $this->hasMany(BloqueioDeAgenda::class);
    }
}
