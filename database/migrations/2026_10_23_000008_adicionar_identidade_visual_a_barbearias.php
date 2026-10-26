<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbearias', function (Blueprint $table): void {
            $table->string('logo_path')->nullable();
            $table->string('cor_primaria', 7)->default('#2563EB');
            $table->string('cor_navegacao', 7)->default('#0F172A');
        });
    }

    public function down(): void
    {
        Schema::table('barbearias', function (Blueprint $table): void {
            $table->dropColumn(['logo_path', 'cor_primaria', 'cor_navegacao']);
        });
    }
};
