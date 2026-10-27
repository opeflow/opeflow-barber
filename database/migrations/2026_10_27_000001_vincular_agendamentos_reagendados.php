<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->foreignId('agendamento_origem_id')->nullable()->after('servico_id')->constrained('agendamentos')->nullOnDelete();
            $table->index(['barbearia_id', 'agendamento_origem_id']);
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropIndex(['barbearia_id', 'agendamento_origem_id']);
            $table->dropConstrainedForeignId('agendamento_origem_id');
        });
    }
};
