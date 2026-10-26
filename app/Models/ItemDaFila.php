<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemDaFila extends Model
{
    use PertenceABarbearia;

    protected $table = 'itens_da_fila';

    protected $fillable = [
        'barbearia_id', 'fila_de_espera_id', 'cliente_id', 'servico_id',
        'profissional_preferido_id', 'profissional_id', 'atendimento_id',
        'chegou_em', 'previsao_atendimento_em', 'posicao', 'prioridade', 'status', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'chegou_em' => 'immutable_datetime',
            'previsao_atendimento_em' => 'immutable_datetime',
            'prioridade' => 'integer',
        ];
    }

    public function fila(): BelongsTo
    {
        return $this->belongsTo(FilaDeEspera::class, 'fila_de_espera_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function profissionalPreferido(): BelongsTo
    {
        return $this->belongsTo(Profissional::class, 'profissional_preferido_id');
    }

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class);
    }
}
