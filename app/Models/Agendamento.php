<?php

namespace App\Models;

use App\Models\Concerns\PertenceABarbearia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agendamento extends Model
{
    use PertenceABarbearia;

    protected $table = 'agendamentos';

    protected $fillable = [
        'barbearia_id', 'cliente_id', 'profissional_id', 'servico_id',
        'inicio_em', 'fim_em', 'status', 'observacoes',
        'chave_idempotencia_publica',
        'beneficio_do_cliente_id',
    ];

    protected function casts(): array
    {
        return ['inicio_em' => 'datetime', 'fim_em' => 'datetime'];
    }

    public function confirmarAgendamento(): void
    {
        $this->update(['status' => 'confirmado']);
    }

    public function cancelarAgendamento(): void
    {
        $this->update(['status' => 'cancelado']);
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

    public function beneficio(): BelongsTo
    {
        return $this->belongsTo(BeneficioDoCliente::class, 'beneficio_do_cliente_id');
    }
}
