<?php

namespace App\Models\Concerns;

use App\Models\Barbearia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait PertenceABarbearia
{
    public function barbearia(): BelongsTo
    {
        return $this->belongsTo(Barbearia::class);
    }
}
