<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens_de_redefinicao_de_senha_de_clientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('conta_de_cliente_id')->constrained('contas_de_clientes')->cascadeOnDelete()->unique();
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expira_em');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_de_redefinicao_de_senha_de_clientes');
    }
};
