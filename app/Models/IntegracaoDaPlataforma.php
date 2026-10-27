<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegracaoDaPlataforma extends Model
{
    protected $table = 'integracoes_da_plataforma';

    protected $fillable = ['provedor', 'rotulo', 'credencial_cifrada', 'ativa', 'atualizada_por_administrador_id'];

    protected function casts(): array
    {
        return ['credencial_cifrada' => 'encrypted', 'ativa' => 'boolean'];
    }

    public function credencialMascarada(): ?string
    {
        if (! $this->credencial_cifrada) {
            return null;
        }

        $segredo = $this->credencial_cifrada;

        return '••••••••'.substr($segredo, -4);
    }
}
