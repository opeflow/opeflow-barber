<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = ['barbearia_id', 'nome', 'telefone', 'email', 'observacoes', 'aceita_lembretes'];

    protected function casts(): array
    {
        return ['aceita_lembretes' => 'boolean'];
    }

    public function barbearia(): BelongsTo
    {
        return $this->belongsTo(Barbearia::class);
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class);
    }

    public function beneficios(): HasMany
    {
        return $this->hasMany(BeneficioDoCliente::class);
    }
}
