<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fechamentos_de_caixa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->date('data_operacional');
            $table->unsignedInteger('versao');
            $table->foreignId('fechado_por_usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->decimal('total_recebido', 12, 2);
            $table->decimal('total_estornado', 12, 2);
            $table->decimal('total_despesas', 12, 2);
            $table->decimal('saldo_esperado', 12, 2);
            $table->decimal('valor_conferido', 12, 2);
            $table->decimal('diferenca', 12, 2);
            $table->json('totais_por_forma');
            $table->uuid('chave_idempotencia');
            $table->timestampTz('fechado_em');
            $table->timestamps();
            $table->unique(['barbearia_id', 'data_operacional', 'versao'], 'fechamento_caixa_versao_unica');
            $table->unique(['barbearia_id', 'chave_idempotencia'], 'fechamento_caixa_idempotencia_unica');
            $table->index(['barbearia_id', 'data_operacional']);
        });

        Schema::create('reaberturas_de_caixa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('fechamento_de_caixa_id')->constrained('fechamentos_de_caixa')->restrictOnDelete()->unique();
            $table->foreignId('reaberto_por_usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('motivo', 240);
            $table->timestampTz('reaberto_em');
            $table->timestamps();
            $table->index(['barbearia_id', 'reaberto_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reaberturas_de_caixa');
        Schema::dropIfExists('fechamentos_de_caixa');
    }
};
