<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FilaDeEspera extends Model
{
    protected $table = 'filas_de_espera';

    protected $fillable = ['barbearia_id', 'data_operacional'];

    protected function casts(): array
    {
        return ['data_operacional' => 'date'];
    }

    public function barbearia(): BelongsTo
    {
        return $this->belongsTo(Barbearia::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemDaFila::class);
    }
}
