<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profissionais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->string('nome');
            $table->string('telefone', 20)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('servicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedSmallInteger('duracao_em_minutos');
            $table->decimal('preco', 10, 2);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('profissional_servico', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('profissional_id')->constrained('profissionais')->cascadeOnDelete();
            $table->foreignId('servico_id')->constrained('servicos')->cascadeOnDelete();
            $table->unique(['profissional_id', 'servico_id']);
        });

        Schema::create('clientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->string('nome');
            $table->string('telefone', 20);
            $table->string('email')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->unique(['barbearia_id', 'telefone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profissional_servico');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('servicos');
        Schema::dropIfExists('profissionais');
    }
};
