<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('horarios_de_trabalho', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_da_semana');
            $table->time('inicio_as');
            $table->time('fim_as');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->index(['barbearia_id', 'profissional_id', 'dia_da_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_de_trabalho');
    }
};
