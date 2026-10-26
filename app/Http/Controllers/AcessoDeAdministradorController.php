<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntrarAdministradorDaPlataformaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AcessoDeAdministradorController extends Controller
{
    public function formulario(): Response
    {
        $this->somenteEmDesenvolvimento();

        return Inertia::render('Administracao/Entrar', ['mostrarCredenciaisDeDesenvolvimento' => app()->environment('local')]);
    }

    public function entrar(EntrarAdministradorDaPlataformaRequest $request): RedirectResponse
    {
        $this->somenteEmDesenvolvimento();
        $request->autenticar();

        return to_route('admin.inicio');
    }

    public function inicio(Request $request): Response
    {
        $this->somenteEmDesenvolvimento();

        return Inertia::render('Administracao/Inicio', [
            'nome' => $request->user('administrador')->nome,
            'ambiente' => app()->environment(),
            'escopo' => 'Acesso global da plataforma. Nenhuma operação de barbearia está disponível neste painel inicial.',
        ]);
    }

    public function sair(Request $request): RedirectResponse
    {
        $this->somenteEmDesenvolvimento();
        Auth::guard('administrador')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.entrar');
    }

    private function somenteEmDesenvolvimento(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
    }
}
