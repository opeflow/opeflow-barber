<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ContaDeCliente extends Authenticatable implements MustVerifyEmail
{
    use MustVerifyEmailTrait, Notifiable;

    protected $table = 'contas_de_clientes';

    protected $fillable = ['barbearia_id', 'cliente_id', 'nome', 'email', 'senha', 'ativo', 'email_verified_at'];

    protected $hidden = ['senha', 'remember_token'];

    protected function casts(): array
    {
        return ['senha' => 'hashed', 'ativo' => 'boolean', 'email_verified_at' => 'datetime'];
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function barbearia(): BelongsTo
    {
        return $this->belongsTo(Barbearia::class);
    }
}
