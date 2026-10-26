<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lembrete extends Model
{
    use PertenceABarbearia;

    protected $table = 'lembretes';

    protected $fillable = ['barbearia_id', 'cliente_id', 'agendamento_id', 'beneficio_do_cliente_id', 'tipo', 'agendado_para', 'status', 'enviado_manualmente_em', 'enviado_por_usuario_id', 'chave_idempotencia'];

    protected function casts(): array
    {
        return ['agendado_para' => 'immutable_datetime', 'enviado_manualmente_em' => 'immutable_datetime'];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function agendamento(): BelongsTo
    {
        return $this->belongsTo(Agendamento::class);
    }

    public function beneficio(): BelongsTo
    {
        return $this->belongsTo(BeneficioDoCliente::class, 'beneficio_do_cliente_id');
    }
}
