<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class ManipularRequisicaoInertia extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'nomeDoProduto' => config('app.name'),
            'usuarioPainel' => fn (): ?array => $request->user() ? [
                'nome' => $request->user()->nome,
                'papel' => $request->user()->papel->value,
                'barbearia' => $request->user()->barbearia->nome,
                'podeGerenciarConfiguracoes' => $request->user()->papel->podeGerenciarConfiguracoes(),
                'podeGerenciarEquipe' => $request->user()->papel->podeGerenciarEquipe(),
                'logoUrl' => $request->user()->barbearia->logo_path ? route('barbearia.logo') : null,
                'corPrimaria' => $request->user()->barbearia->cor_primaria,
                'corNavegacao' => $request->user()->barbearia->cor_navegacao,
            ] : null,
            'flash' => [
                'sucesso' => fn () => $request->session()->get('sucesso'),
                'erro' => fn () => $request->session()->get('erro'),
                'aviso' => fn () => $request->session()->get('aviso'),
                'conviteEquipe' => fn () => $request->session()->get('conviteEquipe'),
                'conviteAceite' => fn () => $request->session()->get('conviteAceite'),
            ],
        ];
    }
}
