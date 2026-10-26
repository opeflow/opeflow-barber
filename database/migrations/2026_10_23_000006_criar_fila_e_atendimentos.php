<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filas_de_espera', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->date('data_operacional');
            $table->timestamps();
            $table->unique(['barbearia_id', 'data_operacional']);
        });

        Schema::create('atendimentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('profissional_id')->constrained('profissionais')->restrictOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->foreignId('item_da_fila_id')->nullable()->unique();
            $table->timestampTz('iniciado_em');
            $table->timestampTz('concluido_em')->nullable();
            $table->decimal('valor_total', 10, 2);
            $table->string('status')->default('em_andamento');
            $table->timestamps();
        });

        Schema::create('itens_da_fila', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('fila_de_espera_id')->constrained('filas_de_espera')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->restrictOnDelete();
            $table->foreignId('profissional_preferido_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->foreignId('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->foreignId('atendimento_id')->nullable()->constrained('atendimentos')->nullOnDelete();
            $table->timestampTz('chegou_em');
            $table->timestampTz('previsao_atendimento_em')->nullable();
            $table->unsignedInteger('posicao');
            $table->unsignedTinyInteger('prioridade')->default(0);
            $table->string('status')->default('aguardando');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->index(['barbearia_id', 'fila_de_espera_id', 'status', 'chegou_em']);
        });

        Schema::table('atendimentos', function (Blueprint $table): void {
            $table->foreign('item_da_fila_id')->references('id')->on('itens_da_fila')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('atendimentos', function (Blueprint $table): void {
            $table->dropForeign(['item_da_fila_id']);
        });
        Schema::dropIfExists('itens_da_fila');
        Schema::dropIfExists('atendimentos');
        Schema::dropIfExists('filas_de_espera');
    }
};
