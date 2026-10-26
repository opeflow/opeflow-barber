<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despesas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('registrada_por_usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('descricao', 160);
            $table->string('categoria', 60);
            $table->decimal('valor', 10, 2);
            $table->timestampTz('movimentada_em');
            $table->uuid('chave_idempotencia');
            $table->timestamps();
            $table->unique(['barbearia_id', 'chave_idempotencia'], 'despesas_idempotencia_unica');
            $table->index(['barbearia_id', 'movimentada_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despesas');
    }
};
