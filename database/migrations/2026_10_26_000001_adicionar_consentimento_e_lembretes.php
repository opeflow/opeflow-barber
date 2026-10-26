<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table): void {
            $table->boolean('aceita_lembretes')->default(false);
        });

        Schema::create('lembretes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('barbearia_id')->constrained('barbearias')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('agendamento_id')->nullable()->constrained('agendamentos')->nullOnDelete();
            $table->foreignId('beneficio_do_cliente_id')->nullable()->constrained('beneficios_dos_clientes')->nullOnDelete();
            $table->string('tipo', 40);
            $table->timestampTz('agendado_para');
            $table->string('status', 30)->default('pendente');
            $table->timestampTz('enviado_manualmente_em')->nullable();
            $table->foreignId('enviado_por_usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('chave_idempotencia')->unique();
            $table->timestamps();
            $table->index(['barbearia_id', 'status', 'agendado_para']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lembretes');
        Schema::table('clientes', fn (Blueprint $table) => $table->dropColumn('aceita_lembretes'));
    }
};
