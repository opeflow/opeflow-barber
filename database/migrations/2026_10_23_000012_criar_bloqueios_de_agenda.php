<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueios_de_agenda', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->foreignId('criado_por_usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestampTz('inicio_em');
            $table->timestampTz('fim_em');
            $table->string('motivo', 160)->nullable();
            $table->timestamps();
            $table->index(['barbearia_id', 'profissional_id', 'inicio_em', 'fim_em'], 'bloqueios_agenda_periodo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueios_de_agenda');
    }
};
