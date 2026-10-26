<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdicionarNaFilaRequest;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\FilaDeEspera;
use App\Models\ItemDaFila;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\Beneficios\EmissorDeBeneficio;
use App\Services\Fila\ChamadorProximoDaFila;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FilaController extends Controller
{
    public function exibir(Request $request): Response
    {
        $usuario = $request->user();
        $barbearia = $usuario->barbearia;
        $modoProfissional = $usuario->papel->value === 'profissional';
        if ($modoProfissional) {
            abort_unless($usuario->profissional_id !== null, 403, 'Esta conta ainda não está vinculada a um profissional.');
        }
        $dataOperacional = now($barbearia->fuso_horario)->toDateString();
        $fila = FilaDeEspera::query()
            ->where('barbearia_id', $barbearia->id)
            ->whereDate('data_operacional', $dataOperacional)
            ->first();

        $itens = $fila?->itens()
            ->when($modoProfissional, fn ($consulta) => $consulta->where('profissional_id', $usuario->profissional_id))
            ->with(['cliente:id,nome,telefone', 'servico:id,nome,duracao_em_minutos', 'profissional:id,nome', 'profissionalPreferido:id,nome', 'atendimento.pagamentos'])
            ->whereNotIn('status', ['atendido', 'desistiu', 'cancelado'])
            ->orderByRaw("CASE WHEN status = 'em_atendimento' THEN 0 WHEN status = 'chamado' THEN 1 ELSE 2 END")
            ->orderBy('chegou_em')
            ->orderByDesc('prioridade')
            ->get()
            ->values()
            ->map(fn (ItemDaFila $item, int $indice): array => [
                'id' => $item->id,
                'posicao' => $indice + 1,
                'cliente' => $item->cliente->nome,
                'telefone' => $item->cliente->telefone,
                'servico' => $item->servico->nome,
                'duracao' => $item->servico->duracao_em_minutos,
                'profissional' => $item->profissional?->nome,
                'profissionalPreferido' => $item->profissionalPreferido?->nome,
                'chegouEm' => $item->chegou_em->toIso8601String(),
                'previsao' => $item->previsao_atendimento_em?->toIso8601String(),
                'prioridade' => $item->prioridade,
                'status' => $item->status,
                'observacoes' => $item->observacoes,
                'atendimentoId' => $item->atendimento_id,
                'valorTotal' => $item->atendimento?->valor_total,
                'valorPago' => $item->atendimento?->pagamentos->sum('valor') ?? '0.00',
                'saldo' => $item->atendimento
                    ? number_format(max(0, (float) $item->atendimento->valor_total - (float) $item->atendimento->pagamentos->sum('valor')), 2, '.', '')
                    : null,
            ]) ?? collect();

        return Inertia::render('Fila/Index', [
            'itens' => $itens,
            'clientes' => $modoProfissional ? [] : $barbearia->clientes()->orderBy('nome')->get(['id', 'nome', 'telefone']),
            'servicos' => $modoProfissional ? [] : $barbearia->servicos()->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'duracao_em_minutos']),
            'profissionais' => $modoProfissional
                ? $barbearia->profissionais()->whereKey($usuario->profissional_id)->get(['id', 'nome'])
                : $barbearia->profissionais()->where('ativo', true)->orderBy('nome')->get(['id', 'nome']),
            'dataOperacional' => $dataOperacional,
            'fusoHorario' => $barbearia->fuso_horario,
            'modoProfissional' => $modoProfissional,
            'podeAdministrarFila' => ! $modoProfissional,
            'podeRegistrarPagamento' => $usuario->papel->podeRegistrarPagamento(),
        ]);
    }

    public function adicionar(AdicionarNaFilaRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $barbearia = $request->user()->barbearia;
        $dataOperacional = now($barbearia->fuso_horario)->toDateString();

        DB::transaction(function () use ($barbearia, $dataOperacional, $dados): void {
            Barbearia::query()->whereKey($barbearia->id)->lockForUpdate()->firstOrFail();

            $fila = FilaDeEspera::firstOrCreate([
                'barbearia_id' => $barbearia->id,
                'data_operacional' => $dataOperacional,
            ]);

            $fila = FilaDeEspera::query()->whereKey($fila->id)->lockForUpdate()->firstOrFail();
            $agora = CarbonImmutable::now('UTC');
            $duracaoNaFila = (int) $fila->itens()
                ->whereIn('status', ['aguardando', 'chamado', 'em_atendimento'])
                ->join('servicos', 'itens_da_fila.servico_id', '=', 'servicos.id')
                ->sum('servicos.duracao_em_minutos');

            $profissionaisElegiveis = Profissional::query()
                ->where('barbearia_id', $barbearia->id)
                ->where('ativo', true)
                ->whereHas('servicos', fn ($consulta) => $consulta->whereKey($dados['servico_id']))
                ->count();

            $minutosDeEspera = (int) ceil($duracaoNaFila / max(1, $profissionaisElegiveis));

            $fila->itens()->create([
                'barbearia_id' => $barbearia->id,
                'cliente_id' => $dados['cliente_id'],
                'servico_id' => $dados['servico_id'],
                'profissional_preferido_id' => $dados['profissional_preferido_id'] ?? null,
                'chegou_em' => $agora,
                'previsao_atendimento_em' => $profissionaisElegiveis > 0 ? $agora->addMinutes($minutosDeEspera) : null,
                'posicao' => ((int) $fila->itens()->max('posicao')) + 1,
                'prioridade' => $dados['prioridade'] ?? 0,
                'status' => 'aguardando',
                'observacoes' => $dados['observacoes'] ?? null,
            ]);
        });

        return to_route('fila.exibir')->with('sucesso', 'Cliente adicionado à fila.');
    }

    public function chamarProximo(Request $request, ChamadorProximoDaFila $chamador): RedirectResponse
    {
        abort_unless($request->user()->papel->podeGerenciarAtendimento(), 403);
        $fila = $this->filaDeHoje($request);
        $item = $chamador->chamarProximoCliente($fila);

        return back()->with('sucesso', $item->cliente->nome.' foi chamado para atendimento.');
    }

    public function iniciarAtendimento(Request $request, ItemDaFila $item): RedirectResponse
    {
        $this->autorizarItemDaFila($request, $item);

        DB::transaction(function () use ($item): void {
            $item = ItemDaFila::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($item->status !== 'chamado' || $item->atendimento_id !== null || $item->profissional_id === null) {
                throw ValidationException::withMessages(['fila' => 'Este cliente ainda não pode iniciar o atendimento.']);
            }

            $servico = Servico::query()->where('barbearia_id', $item->barbearia_id)->whereKey($item->servico_id)->firstOrFail();
            $atendimento = Atendimento::create([
                'barbearia_id' => $item->barbearia_id,
                'cliente_id' => $item->cliente_id,
                'profissional_id' => $item->profissional_id,
                'servico_id' => $item->servico_id,
                'item_da_fila_id' => $item->id,
                'iniciado_em' => CarbonImmutable::now('UTC'),
                'valor_total' => $servico->preco,
                'status' => 'em_andamento',
            ]);

            $item->update(['status' => 'em_atendimento', 'atendimento_id' => $atendimento->id]);
        });

        return back()->with('sucesso', 'Atendimento iniciado.');
    }

    public function concluirAtendimento(Request $request, ItemDaFila $item, EmissorDeBeneficio $emissor): RedirectResponse
    {
        $this->autorizarItemDaFila($request, $item);

        DB::transaction(function () use ($item, $emissor): void {
            $item = ItemDaFila::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ($item->status !== 'em_atendimento' || $item->atendimento_id === null) {
                throw ValidationException::withMessages(['fila' => 'Não existe atendimento ativo para concluir.']);
            }

            $atendimento = Atendimento::query()->where('barbearia_id', $item->barbearia_id)
                ->whereKey($item->atendimento_id)->where('status', 'em_andamento')->firstOrFail();
            $atendimento->update(['status' => 'concluido', 'concluido_em' => CarbonImmutable::now('UTC')]);
            $emissor->emitirParaAtendimentoConcluido($atendimento->fresh());

            $item->update(['status' => 'atendido']);
        });

        return back()->with('sucesso', 'Atendimento concluído.');
    }

    public function desistir(Request $request, ItemDaFila $item): RedirectResponse
    {
        $this->autorizarItemDaFila($request, $item);
        abort_unless(in_array($item->status, ['aguardando', 'chamado'], true), 422, 'Este item não pode ser removido da fila agora.');

        $item->update(['status' => 'desistiu']);

        return back()->with('sucesso', 'Cliente removido da fila.');
    }

    private function filaDeHoje(Request $request): FilaDeEspera
    {
        $barbearia = $request->user()->barbearia;

        return FilaDeEspera::query()
            ->where('barbearia_id', $barbearia->id)
            ->whereDate('data_operacional', now($barbearia->fuso_horario)->toDateString())
            ->firstOrFail();
    }

    private function autorizarItemDaFila(Request $request, ItemDaFila $item): void
    {
        $usuario = $request->user();
        abort_unless($item->barbearia_id === $usuario->barbearia_id, 404);
        if ($usuario->papel->value === 'profissional') {
            abort_unless($usuario->profissional_id !== null && $item->profissional_id === $usuario->profissional_id, 404);
        }
    }
}
