<?php

namespace App\Http\Controllers;

use App\Enums\PapelDeUsuario;
use App\Http\Requests\CriarContaInicialRequest;
use App\Models\Barbearia;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function formulario(): Response
    {
        return Inertia::render('Onboarding/CriarConta');
    }

    public function criar(CriarContaInicialRequest $request): RedirectResponse
    {
        $usuario = DB::transaction(function () use ($request): Usuario {
            $barbearia = Barbearia::create([
                'nome' => $request->string('nome_da_barbearia')->toString(),
                'slug' => $this->criarSlugDisponivel($request->string('nome_da_barbearia')->toString()),
            ]);

            return Usuario::create([
                'barbearia_id' => $barbearia->id,
                'nome' => $request->string('nome')->toString(),
                'email' => $request->string('email')->lower()->toString(),
                'senha' => $request->string('senha')->toString(),
                'papel' => PapelDeUsuario::Proprietario,
            ]);
        });

        Auth::guard('web')->login($usuario);
        $request->session()->regenerate();

        return to_route('inicio');
    }

    private function criarSlugDisponivel(string $nome): string
    {
        $base = Str::slug($nome) ?: 'barbearia';
        $slug = $base;
        $sufixo = 2;

        while (Barbearia::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$sufixo++;
        }

        return $slug;
    }
}
