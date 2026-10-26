<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/entrar');
        $middleware->web(append: [
            \App\Http\Middleware\ManipularRequisicaoInertia::class,
        ]);
        // Middlewares de contexto da barbearia serão registrados aqui.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tratamento centralizado de exceções do domínio.
    })->create();
