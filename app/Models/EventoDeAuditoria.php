<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoDeAuditoria extends Model
{
    public $timestamps = false;

    protected $table = 'eventos_de_auditoria';

    protected $fillable = [
        'administrador_da_plataforma_id', 'acao', 'nivel', 'servico', 'identificador_requisicao',
        'descricao_redigida', 'metadados', 'created_at',
    ];

    protected function casts(): array
    {
        return ['metadados' => 'array', 'created_at' => 'datetime'];
    }
}
