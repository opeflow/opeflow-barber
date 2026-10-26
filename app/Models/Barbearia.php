<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barbearia extends Model
{
    protected $table = 'barbearias';

    protected $fillable = [
        'nome', 'slug', 'telefone', 'email', 'fuso_horario', 'ativa', 'logo_path', 'cor_primaria', 'cor_navegacao',
    ];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    public function profissionais(): HasMany
    {
        return $this->hasMany(Profissional::class);
    }

    public function servicos(): HasMany
    {
        return $this->hasMany(Servico::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
