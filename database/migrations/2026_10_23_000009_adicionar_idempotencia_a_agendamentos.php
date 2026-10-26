<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->uuid('chave_idempotencia_publica')->nullable();
            $table->unique(['barbearia_id', 'chave_idempotencia_publica'], 'agendamentos_chave_publica_unica');
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropUnique('agendamentos_chave_publica_unica');
            $table->dropColumn('chave_idempotencia_publica');
        });
    }
};
