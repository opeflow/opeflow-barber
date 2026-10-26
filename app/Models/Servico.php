<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Servico extends Model
{
    protected $table = 'servicos';

    protected $fillable = [
        'barbearia_id', 'nome', 'descricao', 'duracao_em_minutos', 'preco', 'ativo',
    ];

    protected function casts(): array
    {
        return ['preco' => 'decimal:2', 'ativo' => 'boolean'];
    }

    public function barbearia(): BelongsTo
    {
        return $this->belongsTo(Barbearia::class);
    }

    public function profissionais(): BelongsToMany
    {
        return $this->belongsToMany(Profissional::class, 'profissional_servico');
    }
}
