<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
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
            'usuarioPainel' => fn (): ?array => $request->user('web') instanceof Usuario ? [
                'nome' => $request->user('web')->nome,
                'papel' => $request->user('web')->papel->value,
                'barbearia' => $request->user('web')->barbearia->nome,
                'podeGerenciarConfiguracoes' => $request->user('web')->papel->podeGerenciarConfiguracoes(),
                'podeGerenciarEquipe' => $request->user('web')->papel->podeGerenciarEquipe(),
                'logoUrl' => $request->user('web')->barbearia->logo_path ? route('barbearia.logo') : null,
                'corPrimaria' => $request->user('web')->barbearia->cor_primaria,
                'corNavegacao' => $request->user('web')->barbearia->cor_navegacao,
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
