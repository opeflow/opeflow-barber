<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PainelController extends Controller
{
    public function exibir(): Response
    {
        return Inertia::render('Inicio', [
            'titulo' => 'Opeflow Barber',
            'usuario' => [
                'nome' => auth()->user()->nome,
                'papel' => auth()->user()->papel->value,
                'barbearia' => auth()->user()->barbearia->nome,
                'podeGerenciarConfiguracoes' => auth()->user()->papel->podeGerenciarConfiguracoes(),
            ],
        ]);
    }
}
