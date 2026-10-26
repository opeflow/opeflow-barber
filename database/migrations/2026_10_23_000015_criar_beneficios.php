<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regras_de_beneficio', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete()->unique();
            $table->foreignId('servico_originador_id')->constrained('servicos')->restrictOnDelete();
            $table->foreignId('servico_beneficiado_id')->constrained('servicos')->restrictOnDelete();
            $table->unsignedSmallInteger('disponivel_apos_dias')->default(0);
            $table->unsignedSmallInteger('expira_apos_dias')->default(14);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('beneficios_dos_clientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('regra_de_beneficio_id')->constrained('regras_de_beneficio')->restrictOnDelete();
            $table->foreignId('atendimento_origem_id')->constrained('atendimentos')->restrictOnDelete();
            $table->timestampTz('disponivel_em');
            $table->timestampTz('expira_em');
            $table->string('status')->default('disponivel');
            $table->timestampTz('utilizado_em')->nullable();
            $table->timestamps();
            $table->unique(['barbearia_id', 'atendimento_origem_id', 'regra_de_beneficio_id'], 'beneficios_origem_regra_unica');
            $table->index(['barbearia_id', 'cliente_id', 'status', 'expira_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficios_dos_clientes');
        Schema::dropIfExists('regras_de_beneficio');
    }
};
