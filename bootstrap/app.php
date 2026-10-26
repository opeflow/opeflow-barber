<?php

use App\Http\Middleware\ManipularRequisicaoInertia;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn (Request $request): string => match (true) {
            $request->is('cliente/*') => '/cliente/entrar',
            $request->is('admin/*') => '/admin/entrar',
            default => '/entrar',
        });
        $middleware->web(append: [
            ManipularRequisicaoInertia::class,
        ]);
        // Middlewares de contexto da barbearia serão registrados aqui.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Tratamento centralizado de exceções do domínio.
    })->create();
