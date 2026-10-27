<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigirMfaAdministrativo
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        if (! filter_var($request->session()->get('admin_mfa_verificado', false), FILTER_VALIDATE_BOOLEAN)) {
            return to_route($request->user('administrador')?->mfa_confirmado_em ? 'admin.mfa.verificar' : 'admin.mfa.configurar');
        }

        return $next($request);
    }
}
