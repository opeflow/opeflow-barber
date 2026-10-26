<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->dropUnique(['beneficio_do_cliente_id']);
        });
    }

    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table): void {
            $table->unique('beneficio_do_cliente_id');
        });
    }
};
