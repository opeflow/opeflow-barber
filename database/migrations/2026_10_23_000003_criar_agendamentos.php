<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('profissional_id')->constrained('profissionais')->restrictOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->timestampTz('inicio_em');
            $table->timestampTz('fim_em');
            $table->string('status')->default('aguardando_confirmacao');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index(['barbearia_id', 'profissional_id', 'inicio_em']);
        });
    }

    public function down(): void { Schema::dropIfExists('agendamentos'); }
};
