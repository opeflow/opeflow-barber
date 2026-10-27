<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administradores_da_plataforma', function (Blueprint $table): void {
            $table->text('segredo_mfa')->nullable();
            $table->timestampTz('mfa_confirmado_em')->nullable();
        });

        Schema::create('codigos_de_recuperacao_administrativa', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('administrador_da_plataforma_id')->constrained('administradores_da_plataforma')->cascadeOnDelete();
            $table->string('codigo_hash', 64)->unique();
            $table->timestampTz('utilizado_em')->nullable();
            $table->timestampsTz();
            $table->index(['administrador_da_plataforma_id', 'utilizado_em']);
        });

        Schema::create('eventos_de_auditoria', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('administrador_da_plataforma_id')->nullable()->constrained('administradores_da_plataforma')->nullOnDelete();
            $table->string('acao', 100);
            $table->string('nivel', 20)->default('info');
            $table->string('servico', 100)->default('painel_administrativo');
            $table->string('identificador_requisicao', 100)->nullable();
            $table->string('descricao_redigida', 500);
            $table->json('metadados')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['nivel', 'created_at']);
            $table->index(['servico', 'created_at']);
            $table->index(['identificador_requisicao']);
        });

        Schema::create('integracoes_da_plataforma', function (Blueprint $table): void {
            $table->id();
            $table->string('provedor', 50);
            $table->string('rotulo', 120);
            $table->text('credencial_cifrada')->nullable();
            $table->boolean('ativa')->default(true);
            $table->foreignId('atualizada_por_administrador_id')->nullable()->constrained('administradores_da_plataforma')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['provedor', 'ativa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integracoes_da_plataforma');
        Schema::dropIfExists('eventos_de_auditoria');
        Schema::dropIfExists('codigos_de_recuperacao_administrativa');
        Schema::table('administradores_da_plataforma', function (Blueprint $table): void {
            $table->dropColumn(['segredo_mfa', 'mfa_confirmado_em']);
        });
    }
};
