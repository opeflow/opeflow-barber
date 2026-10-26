<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdministradorDaPlataforma extends Authenticatable
{
    use Notifiable;

    protected $table = 'administradores_da_plataforma';

    protected $fillable = ['nome', 'email', 'senha', 'ativo'];

    protected $hidden = ['senha', 'remember_token'];

    protected function casts(): array
    {
        return ['senha' => 'hashed', 'ativo' => 'boolean'];
    }

    public function getAuthPasswordName(): string
    {
        return 'senha';
    }
}
