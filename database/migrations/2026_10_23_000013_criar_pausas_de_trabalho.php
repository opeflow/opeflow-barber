<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pausas_de_trabalho', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('horario_de_trabalho_id')->constrained('horarios_de_trabalho')->cascadeOnDelete();
            $table->time('inicio_as');
            $table->time('fim_as');
            $table->timestamps();
            $table->index(['barbearia_id', 'horario_de_trabalho_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pausas_de_trabalho');
    }
};
