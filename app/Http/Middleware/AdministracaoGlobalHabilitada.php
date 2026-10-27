<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdministracaoGlobalHabilitada
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment(['local', 'testing']) || config('administracao.habilitada'), 404);

        $resposta = $next($request);
        if ($request->is('admin/mfa/*')) {
            $resposta->headers->set('Cache-Control', 'no-store, private');
        }

        return $resposta;
    }
}
