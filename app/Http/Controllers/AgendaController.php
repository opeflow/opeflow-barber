<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriarAgendamentoRequest;
use App\Models\Agendamento;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\Agenda\CalculadoraDeDisponibilidade;
use App\Services\Lembretes\AgendadorDeLembretes;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AgendaController extends Controller
{
    public function exibir(Request $request): Response
    {
        $usuario = $request->user();
        $barbearia = $usuario->barbearia;
        $profissionalLogado = $usuario->papel->value === 'profissional';
        if ($profissionalLogado) {
            abort_unless($usuario->profissional_id !== null, 403, 'Esta conta ainda não está vinculada a um profissional.');
        }
        $fusoHorario = $barbearia->fuso_horario;
        $data = $request->validate([
            'data' => ['nullable', 'date_format:Y-m-d'],
        ])['data'] ?? now($fusoHorario)->toDateString();

        $inicioUtc = CarbonImmutable::parse($data, $fusoHorario)->startOfDay()->utc();
        $fimUtc = $inicioUtc->addDay();

        $agendamentos = Agendamento::query()
            ->where('barbearia_id', $barbearia->id)
            ->when($profissionalLogado, fn ($consulta) => $consulta->where('profissional_id', $usuario->profissional_id))
            ->where('inicio_em', '>=', $inicioUtc)
            ->where('inicio_em', '<', $fimUtc)
            ->with(['cliente:id,nome,telefone', 'profissional:id,nome', 'servico:id,nome,duracao_em_minutos,preco'])
            ->orderBy('inicio_em')
            ->get()
            ->map(fn (Agendamento $agendamento): array => [
                'id' => $agendamento->id,
                'horaInicio' => $agendamento->inicio_em->setTimezone($fusoHorario)->format('H:i'),
                'horaFim' => $agendamento->fim_em->setTimezone($fusoHorario)->format('H:i'),
                'status' => $agendamento->status,
                'cliente' => $agendamento->cliente->nome,
                'telefone' => $agendamento->cliente->telefone,
                'profissional' => $agendamento->profissional->nome,
                'servico' => $agendamento->servico->nome,
                'preco' => $agendamento->servico->preco,
                'podeEncerrar' => ! $profissionalLogado
                    ? $agendamento->fim_em->lessThanOrEqualTo(CarbonImmutable::now('UTC'))
                    : $agendamento->fim_em->lessThanOrEqualTo(CarbonImmutable::now('UTC')) && $agendamento->profissional_id === $usuario->profissional_id,
            ]);

        return Inertia::render('Agenda/Index', [
            'data' => $data,
            'agendamentos' => $agendamentos,
            'clientes' => $profissionalLogado ? [] : Cliente::query()->where('barbearia_id', $barbearia->id)->orderBy('nome')->get(['id', 'nome', 'telefone']),
            'profissionais' => Profissional::query()
                ->where('barbearia_id', $barbearia->id)
                ->when($profissionalLogado, fn ($consulta) => $consulta->whereKey($usuario->profissional_id))
                ->where('ativo', true)
                ->with('servicos:id')
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn (Profissional $profissional): array => [
                    'id' => $profissional->id,
                    'nome' => $profissional->nome,
                    'servicoIds' => $profissional->servicos->pluck('id'),
                ]),
            'servicos' => $profissionalLogado ? [] : Servico::query()->where('barbearia_id', $barbearia->id)->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'duracao_em_minutos', 'preco']),
            'podeGerenciarAgenda' => ! $profissionalLogado,
            'fusoHorario' => $fusoHorario,
        ]);
    }

    public function criar(CriarAgendamentoRequest $request, CalculadoraDeDisponibilidade $calculadora): RedirectResponse
    {
        $dados = $request->validated();
        $usuario = $request->user();
        $barbearia = $usuario->barbearia;
        $profissional = Profissional::query()
            ->where('barbearia_id', $barbearia->id)
            ->whereKey($dados['profissional_id'])
            ->firstOrFail();

        $servico = Servico::query()
            ->where('barbearia_id', $barbearia->id)
            ->whereKey($dados['servico_id'])
            ->whereHas('profissionais', fn ($consulta) => $consulta->whereKey($profissional->id))
            ->firstOrFail();

        $inicioLocal = CarbonImmutable::createFromFormat(
            'Y-m-d H:i',
            $dados['data'].' '.$dados['hora'],
            $barbearia->fuso_horario,
        );
        $inicioUtc = $inicioLocal->utc();
        $fimUtc = $inicioUtc->addMinutes($servico->duracao_em_minutos);

        $agendamento = DB::transaction(function () use ($barbearia, $dados, $profissional, $servico, $inicioUtc, $fimUtc, $calculadora): Agendamento {
            $profissionalBloqueado = Profissional::query()
                ->where('barbearia_id', $barbearia->id)
                ->whereKey($profissional->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $calculadora->profissionalEstaDisponivel($profissionalBloqueado, $inicioUtc, $fimUtc)) {
                throw ValidationException::withMessages([
                    'hora' => 'Esse horário não está disponível para o profissional selecionado.',
                ]);
            }

            return Agendamento::create([
                'barbearia_id' => $barbearia->id,
                'cliente_id' => $dados['cliente_id'],
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'inicio_em' => $inicioUtc,
                'fim_em' => $fimUtc,
                'status' => 'confirmado',
                'observacoes' => $dados['observacoes'] ?? null,
            ]);
        });
        app(AgendadorDeLembretes::class)->agendarParaAgendamento($agendamento);

        return to_route('agenda.exibir', ['data' => $dados['data']])->with('sucesso', 'Agendamento criado para '.$inicioLocal->format('H:i').'.');
    }

    public function cancelar(Request $request, Agendamento $agendamento): RedirectResponse
    {
        abort_unless($request->user()->papel->podeGerenciarAtendimento(), 403);
        abort_unless($agendamento->barbearia_id === $request->user()->barbearia_id, 404);
        abort_unless(in_array($agendamento->status, ['aguardando_confirmacao', 'confirmado'], true), 422, 'Este agendamento não pode mais ser cancelado.');

        DB::transaction(function () use ($agendamento): void {
            $agendamento = Agendamento::query()->whereKey($agendamento->id)->lockForUpdate()->firstOrFail();
            $agendamento->cancelarAgendamento();
            if ($agendamento->beneficio_do_cliente_id) {
                $beneficio = BeneficioDoCliente::query()->where('barbearia_id', $agendamento->barbearia_id)->whereKey($agendamento->beneficio_do_cliente_id)->lockForUpdate()->first();
                if ($beneficio?->status === 'reservado') {
                    $beneficio->update(['status' => $beneficio->expira_em->greaterThan(CarbonImmutable::now('UTC')) ? 'disponivel' : 'expirado']);
                }
            }
        });
        app(AgendadorDeLembretes::class)->cancelarPendentesDoAgendamento($agendamento);

        return back()->with('sucesso', 'Agendamento cancelado.');
    }

    public function marcarAtendido(Request $request, Agendamento $agendamento): RedirectResponse
    {
        $this->autorizarConclusao($request, $agendamento);
        DB::transaction(function () use ($agendamento): void {
            $agendamento = Agendamento::query()->whereKey($agendamento->id)->lockForUpdate()->firstOrFail();
            abort_unless($agendamento->status === 'confirmado', 422, 'Somente agendamentos confirmados podem ser concluídos.');
            abort_if($agendamento->fim_em->greaterThan(CarbonImmutable::now('UTC')), 422, 'O horário ainda não terminou.');
            $agendamento->update(['status' => 'atendido']);
            if ($agendamento->beneficio_do_cliente_id) {
                BeneficioDoCliente::query()->where('barbearia_id', $agendamento->barbearia_id)->whereKey($agendamento->beneficio_do_cliente_id)
                    ->where('status', 'reservado')->update(['status' => 'utilizado', 'utilizado_em' => CarbonImmutable::now('UTC')]);
            }
            app(AgendadorDeLembretes::class)->cancelarPendentesDoAgendamento($agendamento);
        });

        return back()->with('sucesso', 'Agendamento marcado como atendido.');
    }

    public function marcarNaoCompareceu(Request $request, Agendamento $agendamento): RedirectResponse
    {
        $this->autorizarConclusao($request, $agendamento);
        DB::transaction(function () use ($agendamento): void {
            $agendamento = Agendamento::query()->whereKey($agendamento->id)->lockForUpdate()->firstOrFail();
            abort_unless($agendamento->status === 'confirmado', 422, 'Somente agendamentos confirmados podem ser marcados como falta.');
            abort_if($agendamento->fim_em->greaterThan(CarbonImmutable::now('UTC')), 422, 'O horário ainda não terminou.');
            $agendamento->update(['status' => 'nao_compareceu']);
            if ($agendamento->beneficio_do_cliente_id) {
                $beneficio = BeneficioDoCliente::query()->where('barbearia_id', $agendamento->barbearia_id)->whereKey($agendamento->beneficio_do_cliente_id)->lockForUpdate()->first();
                if ($beneficio?->status === 'reservado') {
                    $beneficio->update(['status' => $beneficio->expira_em->greaterThan(CarbonImmutable::now('UTC')) ? 'disponivel' : 'expirado']);
                }
            }
            app(AgendadorDeLembretes::class)->cancelarPendentesDoAgendamento($agendamento);
        });

        return back()->with('sucesso', 'Agendamento marcado como não comparecimento.');
    }

    private function autorizarConclusao(Request $request, Agendamento $agendamento): void
    {
        $usuario = $request->user();
        abort_unless($agendamento->barbearia_id === $usuario->barbearia_id, 404);
        if ($usuario->papel->value === 'profissional') {
            abort_unless($usuario->profissional_id !== null && $agendamento->profissional_id === $usuario->profissional_id, 404);
        } else {
            abort_unless($usuario->papel->podeGerenciarAtendimento(), 403);
        }
    }
}
