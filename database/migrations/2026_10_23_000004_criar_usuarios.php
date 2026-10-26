<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->string('nome');
            $table->string('email');
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('senha');
            $table->string('papel')->default('proprietario');
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
