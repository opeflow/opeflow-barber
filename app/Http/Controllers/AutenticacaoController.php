<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntrarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AutenticacaoController extends Controller
{
    public function formulario(): Response
    {
        return Inertia::render('Autenticacao/Entrar', ['mostrarCredenciaisDeDesenvolvimento' => app()->environment('local')]);
    }

    public function entrar(EntrarRequest $request): RedirectResponse
    {
        $request->autenticar();

        return to_route('inicio');
    }

    public function sair(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('entrar');
    }
}
