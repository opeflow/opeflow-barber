<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Serviços do domínio serão registrados aqui.
    }

    public function boot(): void
    {
        // Configurações globais da aplicação.
    }
}
