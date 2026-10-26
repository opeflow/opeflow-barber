<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntrarContaDeClienteRequest;
use App\Models\Agendamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AcessoDeClienteController extends Controller
{
    public function formulario(): Response
    {
        $this->somenteEmDesenvolvimento();

        return Inertia::render('Cliente/Entrar', ['mostrarCredenciaisDeDesenvolvimento' => app()->environment('local')]);
    }

    public function entrar(EntrarContaDeClienteRequest $request): RedirectResponse
    {
        $this->somenteEmDesenvolvimento();
        $request->autenticar();

        return to_route('cliente.inicio');
    }

    public function inicio(Request $request): Response
    {
        $this->somenteEmDesenvolvimento();
        $conta = $request->user('cliente');
        $agendamentos = Agendamento::query()
            ->where('barbearia_id', $conta->barbearia_id)
            ->where('cliente_id', $conta->cliente_id)
            ->with(['barbearia:id,nome,slug,fuso_horario', 'servico:id,nome', 'profissional:id,nome'])
            ->orderByDesc('inicio_em')
            ->limit(30)
            ->get()
            ->map(fn (Agendamento $agendamento): array => [
                'id' => $agendamento->id,
                'barbearia' => $agendamento->barbearia->nome,
                'dataHora' => $agendamento->inicio_em->setTimezone($agendamento->barbearia->fuso_horario)->format('d/m/Y H:i'),
                'servico' => $agendamento->servico->nome,
                'profissional' => $agendamento->profissional->nome,
                'status' => $agendamento->status,
            ]);

        return Inertia::render('Cliente/Inicio', [
            'nome' => $conta->nome,
            'agendamentos' => $agendamentos,
            'linkAgendamento' => route('agendamento.publico', $conta->barbearia->slug),
        ]);
    }

    public function sair(Request $request): RedirectResponse
    {
        $this->somenteEmDesenvolvimento();
        Auth::guard('cliente')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('cliente.entrar');
    }

    private function somenteEmDesenvolvimento(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
    }
}
