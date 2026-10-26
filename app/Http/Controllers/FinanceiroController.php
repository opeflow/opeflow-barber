<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrarDespesaRequest;
use App\Http\Requests\RegistrarPagamentoRequest;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\Despesa;
use App\Models\Pagamento;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FinanceiroController extends Controller
{
    public function exibir(Request $request): Response
    {
        abort_unless($request->user()->papel->podeAcessarFinanceiro(), 403);
        $barbearia = $request->user()->barbearia;
        $data = $request->validate(['data' => ['nullable', 'date_format:Y-m-d']])['data'] ?? now($barbearia->fuso_horario)->toDateString();
        $inicio = CarbonImmutable::parse($data, $barbearia->fuso_horario)->startOfDay()->utc();
        $fim = $inicio->addDay();
        $consulta = Pagamento::query()
            ->where('barbearia_id', $barbearia->id)
            ->where('registrado_em', '>=', $inicio)
            ->where('registrado_em', '<', $fim);
        $consultaDespesas = Despesa::query()->where('barbearia_id', $barbearia->id)
            ->where('movimentada_em', '>=', $inicio)->where('movimentada_em', '<', $fim);

        return Inertia::render('Financeiro/Index', [
            'data' => $data,
            'fusoHorario' => $barbearia->fuso_horario,
            'totalRecebido' => number_format((float) (clone $consulta)->sum('valor'), 2, '.', ''),
            'totalDespesas' => number_format((float) (clone $consultaDespesas)->sum('valor'), 2, '.', ''),
            'saldoDoDia' => number_format((float) (clone $consulta)->sum('valor') - (float) (clone $consultaDespesas)->sum('valor'), 2, '.', ''),
            'quantidadePagamentos' => (clone $consulta)->count(),
            'quantidadeDespesas' => (clone $consultaDespesas)->count(),
            'porForma' => (clone $consulta)->select('forma_pagamento', DB::raw('SUM(valor) as total'))
                ->groupBy('forma_pagamento')->orderBy('forma_pagamento')->get()
                ->map(fn ($linha): array => ['forma' => $linha->forma_pagamento, 'total' => number_format((float) $linha->total, 2, '.', '')]),
            'pagamentos' => (clone $consulta)->with(['atendimento.cliente:id,nome', 'atendimento.profissional:id,nome'])
                ->orderByDesc('registrado_em')->paginate(20)->withQueryString()->through(fn (Pagamento $pagamento): array => [
                    'id' => $pagamento->id,
                    'valor' => $pagamento->valor,
                    'forma' => $pagamento->forma_pagamento,
                    'registradoEm' => $pagamento->registrado_em->toIso8601String(),
                    'cliente' => $pagamento->atendimento->cliente->nome,
                    'profissional' => $pagamento->atendimento->profissional->nome,
                ]),
            'despesas' => (clone $consultaDespesas)->orderByDesc('movimentada_em')->paginate(20, ['*'], 'pagina_despesas')->withQueryString()->through(fn (Despesa $despesa): array => [
                'id' => $despesa->id,
                'descricao' => $despesa->descricao,
                'categoria' => $despesa->categoria,
                'valor' => $despesa->valor,
                'movimentadaEm' => $despesa->movimentada_em->toIso8601String(),
            ]),
        ]);
    }

    public function registrarDespesa(RegistrarDespesaRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $barbearia = $request->user()->barbearia;
        $movimentadaEm = CarbonImmutable::createFromFormat('Y-m-d H:i', $dados['data'].' '.$dados['hora'], $barbearia->fuso_horario)->utc();

        DB::transaction(function () use ($dados, $request, $barbearia, $movimentadaEm): void {
            Barbearia::query()->whereKey($barbearia->id)->lockForUpdate()->firstOrFail();
            $existente = Despesa::query()->where('barbearia_id', $barbearia->id)
                ->where('chave_idempotencia', $dados['chave_idempotencia'])->exists();
            if ($existente) {
                return;
            }

            Despesa::create([
                'barbearia_id' => $barbearia->id,
                'registrada_por_usuario_id' => $request->user()->id,
                'descricao' => $dados['descricao'],
                'categoria' => $dados['categoria'],
                'valor' => number_format((int) round((float) $dados['valor'] * 100) / 100, 2, '.', ''),
                'movimentada_em' => $movimentadaEm,
                'chave_idempotencia' => $dados['chave_idempotencia'],
            ]);
        });

        return back()->with('sucesso', 'Despesa registrada no caixa.');
    }

    public function registrarPagamento(RegistrarPagamentoRequest $request, Atendimento $atendimento): RedirectResponse
    {
        $usuario = $request->user();
        $podeRegistrarNoAtendimento = $usuario->papel->podeRegistrarPagamento() && (
            $usuario->papel->podeAcessarFinanceiro()
            || ($usuario->papel->value === 'profissional'
                && $usuario->profissional_id !== null
                && $atendimento->profissional_id === $usuario->profissional_id
                && $atendimento->status === 'em_andamento'));
        abort_unless($podeRegistrarNoAtendimento, 403);
        abort_unless($atendimento->barbearia_id === $request->user()->barbearia_id, 404);
        $dados = $request->validated();

        DB::transaction(function () use ($atendimento, $dados): void {
            $atendimento = Atendimento::query()->whereKey($atendimento->id)->lockForUpdate()->firstOrFail();
            $existente = Pagamento::query()->where('barbearia_id', $atendimento->barbearia_id)
                ->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente) {
                return;
            }
            if ($atendimento->status === 'cancelado') {
                throw ValidationException::withMessages(['pagamento' => 'Não é possível receber por um atendimento cancelado.']);
            }

            $valorCentavos = (int) round((float) $dados['valor'] * 100);
            $totalCentavos = (int) round((float) $atendimento->valor_total * 100);
            $recebidoCentavos = (int) round((float) $atendimento->pagamentos()->sum('valor') * 100);
            if ($valorCentavos > $totalCentavos - $recebidoCentavos) {
                throw ValidationException::withMessages(['valor' => 'O pagamento não pode ultrapassar o saldo do atendimento.']);
            }

            Pagamento::create([
                'barbearia_id' => $atendimento->barbearia_id,
                'atendimento_id' => $atendimento->id,
                'valor' => number_format($valorCentavos / 100, 2, '.', ''),
                'forma_pagamento' => $dados['forma_pagamento'],
                'chave_idempotencia' => $dados['chave_idempotencia'],
                'registrado_em' => CarbonImmutable::now('UTC'),
            ]);
        });

        return back()->with('sucesso', 'Pagamento registrado.');
    }
}
