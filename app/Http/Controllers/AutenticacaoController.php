<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntrarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AutenticacaoController extends Controller
{
    public function formulario(): Response
    {
        return Inertia::render('Autenticacao/Entrar');
    }

    public function entrar(EntrarRequest $request): RedirectResponse
    {
        $request->autenticar();

        return to_route('inicio');
    }

    public function sair(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('entrar');
    }
}
