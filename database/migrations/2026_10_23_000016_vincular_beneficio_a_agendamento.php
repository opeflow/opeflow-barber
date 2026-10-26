<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->foreignId('beneficio_do_cliente_id')->nullable()->unique()->constrained('beneficios_dos_clientes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('beneficio_do_cliente_id');
        });
    }
};
