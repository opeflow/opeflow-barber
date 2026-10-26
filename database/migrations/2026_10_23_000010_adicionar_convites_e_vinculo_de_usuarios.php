<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->foreignId('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->string('convite_token_hash', 64)->nullable()->unique();
            $table->timestampTz('convite_expira_em')->nullable();
            $table->timestampTz('convite_aceito_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('profissional_id');
            $table->dropUnique(['convite_token_hash']);
            $table->dropColumn(['convite_token_hash', 'convite_expira_em', 'convite_aceito_em']);
        });
    }
};
