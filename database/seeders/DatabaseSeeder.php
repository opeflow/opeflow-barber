<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Seeds de demonstração ignorados: disponíveis somente em local/testing.');

            return;
        }

        $this->call(DesenvolvimentoSeeder::class);
    }
}
