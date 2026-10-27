<?php

namespace App\Http\Controllers;

use App\Http\Requests\FecharCaixaRequest;
use App\Http\Requests\ReabrirCaixaRequest;
use App\Http\Requests\RegistrarDespesaRequest;
use App\Http\Requests\RegistrarEstornoDePagamentoRequest;
use App\Http\Requests\RegistrarPagamentoRequest;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\Despesa;
use App\Models\EstornoDePagamento;
use App\Models\FechamentoDeCaixa;
use App\Models\Pagamento;
use App\Services\Financeiro\FechadorDeCaixa;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FinanceiroController extends Controller
{
    public function exibir(Request $request, FechadorDeCaixa $fechador): Response
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
        $consultaEstornos = EstornoDePagamento::query()->where('barbearia_id', $barbearia->id)
            ->where('estornado_em', '>=', $inicio)->where('estornado_em', '<', $fim);
        $totalRecebido = (float) (clone $consulta)->sum('valor');
        $totalEstornado = (float) (clone $consultaEstornos)->sum('valor');
        $totalDespesas = (float) (clone $consultaDespesas)->sum('valor');
        $fechamento = $fechador->fechamentoAtual($barbearia->id, $data);
        $historicoFechamentos = FechamentoDeCaixa::query()->where('barbearia_id', $barbearia->id)->whereDate('data_operacional', $data)
            ->with(['fechadoPor:id,nome', 'reabertura.reabertoPor:id,nome'])->orderByDesc('versao')->limit(10)->get();

        return Inertia::render('Financeiro/Index', [
            'data' => $data,
            'fusoHorario' => $barbearia->fuso_horario,
            'totalRecebido' => number_format($totalRecebido, 2, '.', ''),
            'totalEstornado' => number_format($totalEstornado, 2, '.', ''),
            'totalDespesas' => number_format($totalDespesas, 2, '.', ''),
            'saldoDoDia' => number_format($totalRecebido - $totalEstornado - $totalDespesas, 2, '.', ''),
            'quantidadePagamentos' => (clone $consulta)->count(),
            'quantidadeEstornos' => (clone $consultaEstornos)->count(),
            'quantidadeDespesas' => (clone $consultaDespesas)->count(),
            'porForma' => (clone $consulta)->select('forma_pagamento', DB::raw('SUM(valor) as total'))
                ->groupBy('forma_pagamento')->orderBy('forma_pagamento')->get()
                ->map(fn ($linha): array => ['forma' => $linha->forma_pagamento, 'total' => number_format((float) $linha->total, 2, '.', '')]),
            'podeEstornar' => $request->user()->papel->podeEstornarPagamento(),
            'podeFecharCaixa' => $request->user()->papel->podeGerenciarConfiguracoes(),
            'caixaFechado' => $fechamento !== null && $fechamento->reabertura === null,
            'fechamento' => $fechamento ? [
                'id' => $fechamento->id,
                'versao' => $fechamento->versao,
                'totalRecebido' => $fechamento->total_recebido,
                'totalEstornado' => $fechamento->total_estornado,
                'totalDespesas' => $fechamento->total_despesas,
                'saldoEsperado' => $fechamento->saldo_esperado,
                'valorConferido' => $fechamento->valor_conferido,
                'diferenca' => $fechamento->diferenca,
                'totaisPorForma' => $fechamento->totais_por_forma,
                'fechadoEm' => $fechamento->fechado_em->toIso8601String(),
                'fechadoPor' => $fechamento->fechadoPor?->nome,
                'reabertura' => $fechamento->reabertura ? [
                    'motivo' => $fechamento->reabertura->motivo,
                    'reabertoEm' => $fechamento->reabertura->reaberto_em->toIso8601String(),
                    'reabertoPor' => $fechamento->reabertura->reabertoPor?->nome,
                ] : null,
            ] : null,
            'historicoFechamentos' => $historicoFechamentos->map(fn (FechamentoDeCaixa $item): array => [
                'versao' => $item->versao,
                'saldoEsperado' => $item->saldo_esperado,
                'valorConferido' => $item->valor_conferido,
                'diferenca' => $item->diferenca,
                'fechadoPor' => $item->fechadoPor?->nome,
                'fechadoEm' => $item->fechado_em->toIso8601String(),
                'reabertura' => $item->reabertura ? [
                    'motivo' => $item->reabertura->motivo,
                    'reabertoPor' => $item->reabertura->reabertoPor?->nome,
                    'reabertoEm' => $item->reabertura->reaberto_em->toIso8601String(),
                ] : null,
            ]),
            'pagamentos' => (clone $consulta)->withSum('estornos as valor_estornado', 'valor')->with(['atendimento.cliente:id,nome', 'atendimento.profissional:id,nome'])
                ->orderByDesc('registrado_em')->paginate(20)->withQueryString()->through(fn (Pagamento $pagamento): array => [
                    'id' => $pagamento->id,
                    'valor' => $pagamento->valor,
                    'forma' => $pagamento->forma_pagamento,
                    'registradoEm' => $pagamento->registrado_em->toIso8601String(),
                    'cliente' => $pagamento->atendimento->cliente->nome,
                    'profissional' => $pagamento->atendimento->profissional->nome,
                    'valorEstornado' => number_format((float) $pagamento->valor_estornado, 2, '.', ''),
                    'saldoEstornavel' => number_format((float) $pagamento->valor - (float) $pagamento->valor_estornado, 2, '.', ''),
                ]),
            'estornos' => (clone $consultaEstornos)->with('pagamento:id,atendimento_id', 'pagamento.atendimento:id,cliente_id', 'pagamento.atendimento.cliente:id,nome')
                ->orderByDesc('estornado_em')->paginate(20, ['*'], 'pagina_estornos')->withQueryString()->through(fn (EstornoDePagamento $estorno): array => [
                    'id' => $estorno->id,
                    'pagamentoId' => $estorno->pagamento_id,
                    'valor' => $estorno->valor,
                    'motivo' => $estorno->motivo,
                    'cliente' => $estorno->pagamento->atendimento->cliente->nome,
                    'estornadoEm' => $estorno->estornado_em->toIso8601String(),
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

            app(FechadorDeCaixa::class)->garantirDiaAberto($barbearia->id, $movimentadaEm->setTimezone($barbearia->fuso_horario)->toDateString());

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

    public function registrarEstorno(RegistrarEstornoDePagamentoRequest $request, Pagamento $pagamento): RedirectResponse
    {
        abort_unless($pagamento->barbearia_id === $request->user()->barbearia_id, 404);
        $dados = $request->validated();

        DB::transaction(function () use ($pagamento, $dados, $request): void {
            $barbearia = Barbearia::query()->whereKey($pagamento->barbearia_id)->lockForUpdate()->firstOrFail();
            $existente = EstornoDePagamento::query()->where('barbearia_id', $barbearia->id)
                ->where('chave_idempotencia', $dados['chave_idempotencia'])->first();

            if ($existente) {
                abort_unless($existente->pagamento_id === $pagamento->id
                    && (int) round((float) $existente->valor * 100) === (int) round((float) $dados['valor'] * 100), 409, 'A chave de idempotência já foi usada em outra operação.');

                return;
            }

            $agora = CarbonImmutable::now('UTC');
            app(FechadorDeCaixa::class)->garantirDiaAberto($barbearia->id, $agora->setTimezone($barbearia->fuso_horario)->toDateString());
            $pagamento = Pagamento::query()->whereKey($pagamento->id)->lockForUpdate()->firstOrFail();

            $valorCentavos = (int) round((float) $dados['valor'] * 100);
            $valorPagamentoCentavos = (int) round((float) $pagamento->valor * 100);
            $valorEstornadoCentavos = (int) round((float) $pagamento->estornos()->sum('valor') * 100);

            if ($valorCentavos > $valorPagamentoCentavos - $valorEstornadoCentavos) {
                throw ValidationException::withMessages(['valor' => 'O estorno não pode ultrapassar o saldo ainda não estornado deste pagamento.']);
            }

            EstornoDePagamento::create([
                'barbearia_id' => $pagamento->barbearia_id,
                'pagamento_id' => $pagamento->id,
                'registrado_por_usuario_id' => $request->user()->id,
                'valor' => number_format($valorCentavos / 100, 2, '.', ''),
                'motivo' => $dados['motivo'],
                'chave_idempotencia' => $dados['chave_idempotencia'],
                'estornado_em' => $agora,
            ]);
        });

        return back()->with('sucesso', 'Estorno registrado no histórico financeiro.');
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
            $barbearia = Barbearia::query()->whereKey($atendimento->barbearia_id)->lockForUpdate()->firstOrFail();
            $existente = Pagamento::query()->where('barbearia_id', $barbearia->id)
                ->where('chave_idempotencia', $dados['chave_idempotencia'])->first();
            if ($existente) {
                return;
            }
            $agora = CarbonImmutable::now('UTC');
            app(FechadorDeCaixa::class)->garantirDiaAberto($barbearia->id, $agora->setTimezone($barbearia->fuso_horario)->toDateString());
            $atendimento = Atendimento::query()->whereKey($atendimento->id)->lockForUpdate()->firstOrFail();
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
                'registrado_em' => $agora,
            ]);
        });

        return back()->with('sucesso', 'Pagamento registrado.');
    }

    public function fecharCaixa(FecharCaixaRequest $request, FechadorDeCaixa $fechador): RedirectResponse
    {
        $dados = $request->validated();
        $fechador->fechar(
            $request->user()->barbearia,
            $dados['data'],
            (int) round((float) $dados['valor_conferido'] * 100),
            $dados['chave_idempotencia'],
            $request->user(),
        );

        return back()->with('sucesso', 'Caixa fechado e snapshot financeiro registrado.');
    }

    public function reabrirCaixa(ReabrirCaixaRequest $request, FechamentoDeCaixa $fechamento, FechadorDeCaixa $fechador): RedirectResponse
    {
        $fechador->reabrir($request->user()->barbearia, $fechamento, $request->validated('motivo'), $request->user());

        return back()->with('sucesso', 'Caixa reaberto com motivo registrado; uma nova versão poderá ser fechada após os ajustes.');
    }
}
