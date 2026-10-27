<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FechamentoDeCaixa extends Model
{
    use PertenceABarbearia;

    protected $table = 'fechamentos_de_caixa';

    protected $fillable = [
        'barbearia_id', 'data_operacional', 'versao', 'fechado_por_usuario_id',
        'total_recebido', 'total_estornado', 'total_despesas', 'saldo_esperado',
        'valor_conferido', 'diferenca', 'totais_por_forma', 'chave_idempotencia', 'fechado_em',
    ];

    protected function casts(): array
    {
        return [
            'data_operacional' => 'immutable_date',
            'totais_por_forma' => 'array',
            'fechado_em' => 'immutable_datetime',
            'total_recebido' => 'decimal:2',
            'total_estornado' => 'decimal:2',
            'total_despesas' => 'decimal:2',
            'saldo_esperado' => 'decimal:2',
            'valor_conferido' => 'decimal:2',
            'diferenca' => 'decimal:2',
        ];
    }

    public function fechadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'fechado_por_usuario_id');
    }

    public function reabertura(): HasOne
    {
        return $this->hasOne(ReaberturaDeCaixa::class, 'fechamento_de_caixa_id');
    }
}
