<?php

namespace App\Http\Controllers;

use App\Models\Agendamento;
use App\Models\Despesa;
use App\Models\Pagamento;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatoriosController extends Controller
{
    public function exibir(Request $request): Response
    {
        abort_unless($request->user()->papel->podeAcessarFinanceiro(), 403);
        [$inicio, $fim, $inicioUtc, $fimUtc] = $this->periodo($request);
        $barbeariaId = $request->user()->barbearia_id;
        $base = Pagamento::query()->where('pagamentos.barbearia_id', $barbeariaId)
            ->where('pagamentos.registrado_em', '>=', $inicioUtc)->where('pagamentos.registrado_em', '<', $fimUtc);
        $porServico = (clone $base)->join('atendimentos', 'pagamentos.atendimento_id', '=', 'atendimentos.id')->join('servicos', 'atendimentos.servico_id', '=', 'servicos.id')
            ->select('servicos.nome as nome', DB::raw('SUM(pagamentos.valor) as total'))->groupBy('servicos.nome')->orderByDesc('total')->get();
        $porProfissional = (clone $base)->join('atendimentos', 'pagamentos.atendimento_id', '=', 'atendimentos.id')->join('profissionais', 'atendimentos.profissional_id', '=', 'profissionais.id')
            ->select('profissionais.nome as nome', DB::raw('SUM(pagamentos.valor) as total'))->groupBy('profissionais.nome')->orderByDesc('total')->get();
        $atendimentos = (clone $base)->distinct()->count('pagamentos.atendimento_id');
        $agendamentos = Agendamento::query()->where('barbearia_id', $barbeariaId)->where('inicio_em', '>=', $inicioUtc)->where('inicio_em', '<', $fimUtc);
        $totalRecebido = (float) (clone $base)->sum('valor');
        $totalDespesas = (float) Despesa::query()->where('barbearia_id', $barbeariaId)->where('movimentada_em', '>=', $inicioUtc)->where('movimentada_em', '<', $fimUtc)->sum('valor');

        return Inertia::render('Relatorios/Index', [
            'inicio' => $inicio, 'fim' => $fim, 'fusoHorario' => $request->user()->barbearia->fuso_horario,
            'resumo' => [
                'totalRecebido' => number_format($totalRecebido, 2, '.', ''),
                'totalDespesas' => number_format($totalDespesas, 2, '.', ''),
                'saldo' => number_format($totalRecebido - $totalDespesas, 2, '.', ''),
                'ticketMedio' => number_format($atendimentos ? $totalRecebido / $atendimentos : 0, 2, '.', ''),
                'atendimentosComPagamento' => $atendimentos,
                'cancelamentos' => (clone $agendamentos)->where('status', 'cancelado')->count(),
                'faltas' => (clone $agendamentos)->where('status', 'nao_compareceu')->count(),
            ],
            'porServico' => $porServico->map(fn ($linha) => ['nome' => $linha->nome, 'total' => number_format((float) $linha->total, 2, '.', '')]),
            'porProfissional' => $porProfissional->map(fn ($linha) => ['nome' => $linha->nome, 'total' => number_format((float) $linha->total, 2, '.', '')]),
            'urlExportacao' => route('relatorios.exportar', ['inicio' => $inicio, 'fim' => $fim]),
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        abort_unless($request->user()->papel->podeAcessarFinanceiro(), 403);
        [$inicio, $fim, $inicioUtc, $fimUtc] = $this->periodo($request);
        $barbeariaId = $request->user()->barbearia_id;

        return response()->streamDownload(function () use ($barbeariaId, $inicioUtc, $fimUtc): void {
            $saida = fopen('php://output', 'w');
            fputcsv($saida, ['Tipo', 'Descricao', 'Valor', 'Data UTC']);
            Pagamento::query()->where('pagamentos.barbearia_id', $barbeariaId)->where('registrado_em', '>=', $inicioUtc)->where('registrado_em', '<', $fimUtc)->orderBy('registrado_em')->chunk(500, function ($itens) use ($saida): void {
                foreach ($itens as $item) {
                    fputcsv($saida, ['Receita', 'Pagamento #'.$item->id, $item->valor, $item->registrado_em->toIso8601String()]);
                }
            });
            Despesa::query()->where('barbearia_id', $barbeariaId)->where('movimentada_em', '>=', $inicioUtc)->where('movimentada_em', '<', $fimUtc)->orderBy('movimentada_em')->chunk(500, function ($itens) use ($saida): void {
                foreach ($itens as $item) {
                    $descricao = $item->categoria.' - '.$item->descricao;
                    if (preg_match('/^[=+@\-\t\r]/', $descricao)) {
                        $descricao = "'".$descricao;
                    }
                    fputcsv($saida, ['Despesa', $descricao, '-'.$item->valor, $item->movimentada_em->toIso8601String()]);
                }
            });
            fclose($saida);
        }, 'relatorio-financeiro.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function periodo(Request $request): array
    {
        $dados = $request->validate(['inicio' => ['nullable', 'date_format:Y-m-d'], 'fim' => ['nullable', 'date_format:Y-m-d']]);
        $fuso = $request->user()->barbearia->fuso_horario;
        $fimPadrao = CarbonImmutable::now($fuso)->toDateString();
        $inicio = $dados['inicio'] ?? CarbonImmutable::parse($fimPadrao, $fuso)->startOfMonth()->toDateString();
        $fim = $dados['fim'] ?? $fimPadrao;
        abort_if($inicio > $fim || CarbonImmutable::parse($inicio)->diffInDays(CarbonImmutable::parse($fim)) > 366, 422, 'Selecione um período válido de até um ano.');
        $inicioUtc = CarbonImmutable::parse($inicio, $fuso)->startOfDay()->utc();
        $fimUtc = CarbonImmutable::parse($fim, $fuso)->addDay()->startOfDay()->utc();

        return [$inicio, $fim, $inicioUtc, $fimUtc];
    }
}
