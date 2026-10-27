<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estornos_de_pagamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('pagamento_id')->constrained('pagamentos')->restrictOnDelete();
            $table->foreignId('registrado_por_usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->decimal('valor', 10, 2);
            $table->string('motivo', 240);
            $table->uuid('chave_idempotencia');
            $table->timestampTz('estornado_em');
            $table->timestamps();
            $table->unique(['barbearia_id', 'chave_idempotencia'], 'estornos_pagamento_idempotencia_unica');
            $table->index(['barbearia_id', 'estornado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estornos_de_pagamentos');
    }
};
