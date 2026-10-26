<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('atendimento_id')->constrained('atendimentos')->restrictOnDelete();
            $table->decimal('valor', 10, 2);
            $table->string('forma_pagamento', 20);
            $table->uuid('chave_idempotencia');
            $table->timestampTz('registrado_em');
            $table->timestamps();
            $table->unique(['barbearia_id', 'chave_idempotencia']);
            $table->index(['barbearia_id', 'registrado_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
