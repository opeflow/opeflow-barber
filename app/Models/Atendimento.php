<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atendimento extends Model
{
    use PertenceABarbearia;

    protected $table = 'atendimentos';

    protected $fillable = [
        'barbearia_id', 'cliente_id', 'profissional_id', 'servico_id',
        'item_da_fila_id', 'iniciado_em', 'concluido_em', 'valor_total', 'status',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_em' => 'immutable_datetime',
            'concluido_em' => 'immutable_datetime',
            'valor_total' => 'decimal:2',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function itemDaFila(): BelongsTo
    {
        return $this->belongsTo(ItemDaFila::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }
}
