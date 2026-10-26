<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barbearias', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('telefone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('fuso_horario')->default('America/Araguaina');
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('barbearias'); }
};
