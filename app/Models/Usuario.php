<?php

namespace App\Models;

use App\Enums\PapelDeUsuario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'barbearia_id', 'profissional_id', 'nome', 'email', 'senha', 'papel', 'ativo', 'convite_token_hash', 'convite_expira_em', 'convite_aceito_em',
    ];

    protected $hidden = ['senha', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'senha' => 'hashed',
            'papel' => PapelDeUsuario::class,
            'ativo' => 'boolean',
            'convite_expira_em' => 'immutable_datetime',
            'convite_aceito_em' => 'immutable_datetime',
        ];
    }

    public function barbearia(): BelongsTo
    {
        return $this->belongsTo(Barbearia::class);
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function eDaBarbearia(Barbearia $barbearia): bool
    {
        return $this->barbearia_id === $barbearia->id;
    }

    public function profissional(): BelongsTo
    {
        return $this->belongsTo(Profissional::class);
    }
}
