<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriarClienteRequest;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\Lembrete;
use App\Services\Beneficios\CodigoDeResgateDeBeneficio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientesController extends Controller
{
    public function exibir(Request $request): Response
    {
        abort_unless($request->user()->papel->podeConsultarClientes(), 403);
        $termo = trim((string) $request->query('busca', ''));

        $clientes = Cliente::query()
            ->where('barbearia_id', $request->user()->barbearia_id)
            ->when($termo !== '', fn ($consulta) => $consulta->where(function ($consulta) use ($termo): void {
                $consulta->where('nome', 'ilike', '%'.$termo.'%')
                    ->orWhere('telefone', 'ilike', '%'.$termo.'%');
            }))
            ->with(['beneficios' => fn ($consulta) => $consulta->with('regra.servicoBeneficiado')->orderByDesc('created_at')])
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        $fuso = $request->user()->barbearia->fuso_horario;
        $clientes->through(function (Cliente $cliente) use ($fuso): array {
            $dados = $cliente->toArray();
            $dados['beneficios'] = $cliente->beneficios->map(function ($beneficio) use ($fuso): array {
                $estado = $beneficio->status;
                if ($estado === 'disponivel' && $beneficio->expira_em->lessThanOrEqualTo(now('UTC'))) {
                    $estado = 'expirado';
                } elseif ($estado === 'disponivel' && $beneficio->disponivel_em->greaterThan(now('UTC'))) {
                    $estado = 'agendado_para_disponibilidade';
                }

                return [
                    'id' => $beneficio->id,
                    'status' => $estado,
                    'expiraEm' => $beneficio->expira_em->setTimezone($fuso)->format('d/m/Y'),
                    'disponivelEm' => $beneficio->disponivel_em->setTimezone($fuso)->format('d/m/Y'),
                    'servico' => $beneficio->regra?->servicoBeneficiado?->nome,
                    'codigoDeResgate' => in_array($estado, ['disponivel', 'agendado_para_disponibilidade'], true)
                        ? app(CodigoDeResgateDeBeneficio::class)->gerar($beneficio)
                        : null,
                ];
            })->all();

            return $dados;
        });

        return Inertia::render('Clientes/Index', [
            'clientes' => $clientes,
            'busca' => $termo,
        ]);
    }

    public function criar(CriarClienteRequest $request): RedirectResponse
    {
        abort_unless($request->user()->papel->podeConsultarClientes(), 403);
        Cliente::create([
            ...$request->validated(),
            'barbearia_id' => $request->user()->barbearia_id,
        ]);

        return to_route('clientes.exibir')->with('sucesso', 'Cliente cadastrado.');
    }

    public function beneficios(Request $request, int $cliente): Response
    {
        abort_unless($request->user()->papel->podeConsultarClientes(), 403);

        $cliente = Cliente::query()
            ->where('barbearia_id', $request->user()->barbearia_id)
            ->findOrFail($cliente);
        $fuso = $request->user()->barbearia->fuso_horario;

        $beneficios = BeneficioDoCliente::query()
            ->where('barbearia_id', $cliente->barbearia_id)
            ->where('cliente_id', $cliente->id)
            ->with([
                'regra.servicoBeneficiado',
                'atendimentoOrigem.servico',
                'agendamentos' => fn ($consulta) => $consulta->with('servico')->orderByDesc('inicio_em'),
            ])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $beneficios->through(function (BeneficioDoCliente $beneficio) use ($fuso): array {
            $estado = $beneficio->status;
            if ($estado === 'disponivel' && $beneficio->expira_em->lessThanOrEqualTo(now('UTC'))) {
                $estado = 'expirado';
            } elseif ($estado === 'disponivel' && $beneficio->disponivel_em->greaterThan(now('UTC'))) {
                $estado = 'agendado_para_disponibilidade';
            }

            $agendamento = $beneficio->agendamentos->first();

            return [
                'id' => $beneficio->id,
                'status' => $estado,
                'servico' => $beneficio->regra?->servicoBeneficiado?->nome,
                'servicoOrigem' => $beneficio->atendimentoOrigem?->servico?->nome,
                'concluidoEm' => $beneficio->atendimentoOrigem?->concluido_em?->setTimezone($fuso)->format('d/m/Y H:i'),
                'disponivelEm' => $beneficio->disponivel_em->setTimezone($fuso)->format('d/m/Y'),
                'expiraEm' => $beneficio->expira_em->setTimezone($fuso)->format('d/m/Y'),
                'utilizadoEm' => $beneficio->utilizado_em?->setTimezone($fuso)->format('d/m/Y H:i'),
                'agendamento' => $agendamento ? [
                    'servico' => $agendamento->servico?->nome,
                    'inicioEm' => $agendamento->inicio_em->setTimezone($fuso)->format('d/m/Y H:i'),
                    'status' => $agendamento->status,
                ] : null,
                'codigoDeResgate' => $estado === 'disponivel'
                    ? app(CodigoDeResgateDeBeneficio::class)->gerar($beneficio)
                    : null,
            ];
        });

        return Inertia::render('Clientes/Beneficios', [
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome, 'telefone' => $cliente->telefone],
            'beneficios' => $beneficios,
        ]);
    }

    public function ficha(Request $request, int $cliente): Response
    {
        abort_unless($request->user()->papel->podeConsultarClientes(), 403);

        $cliente = Cliente::query()->where('barbearia_id', $request->user()->barbearia_id)->findOrFail($cliente);
        $fuso = $request->user()->barbearia->fuso_horario;
        $agora = now('UTC');

        $proximoAgendamento = Agendamento::query()
            ->where('barbearia_id', $cliente->barbearia_id)
            ->where('cliente_id', $cliente->id)
            ->whereIn('status', ['aguardando_confirmacao', 'confirmado'])
            ->where('inicio_em', '>=', $agora)
            ->with(['servico:id,nome', 'profissional:id,nome'])
            ->orderBy('inicio_em')
            ->first();

        $agendamentos = Agendamento::query()
            ->where('barbearia_id', $cliente->barbearia_id)
            ->where('cliente_id', $cliente->id)
            ->when($proximoAgendamento, fn ($consulta) => $consulta->whereKeyNot($proximoAgendamento->id))
            ->with(['servico:id,nome', 'profissional:id,nome'])
            ->orderByDesc('inicio_em')
            ->paginate(10, ['*'], 'pagina_agendamentos')
            ->withQueryString();

        $atendimentos = Atendimento::query()
            ->where('barbearia_id', $cliente->barbearia_id)
            ->where('cliente_id', $cliente->id)
            ->with(['servico:id,nome', 'profissional:id,nome'])
            ->orderByDesc('iniciado_em')
            ->paginate(10, ['*'], 'pagina_atendimentos')
            ->withQueryString();

        $formatarData = fn ($data) => $data?->setTimezone($fuso)->format('d/m/Y H:i');
        $mapearAgendamento = fn (Agendamento $agendamento): array => [
            'id' => $agendamento->id,
            'inicioEm' => $formatarData($agendamento->inicio_em),
            'status' => $agendamento->status,
            'servico' => $agendamento->servico?->nome,
            'profissional' => $agendamento->profissional?->nome,
        ];

        $agendamentos->through($mapearAgendamento);
        $atendimentos->through(fn (Atendimento $atendimento): array => [
            'id' => $atendimento->id,
            'iniciadoEm' => $formatarData($atendimento->iniciado_em),
            'status' => $atendimento->status,
            'servico' => $atendimento->servico?->nome,
            'profissional' => $atendimento->profissional?->nome,
        ]);

        return Inertia::render('Clientes/Ficha', [
            'cliente' => [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'telefone' => $cliente->telefone,
                'email' => $cliente->email,
                'observacoes' => $cliente->observacoes,
                'aceitaLembretes' => $cliente->aceita_lembretes,
            ],
            'proximoAgendamento' => $proximoAgendamento ? $mapearAgendamento($proximoAgendamento) : null,
            'agendamentos' => $agendamentos,
            'atendimentos' => $atendimentos,
        ]);
    }

    public function retirarConsentimento(Request $request, Cliente $cliente): RedirectResponse
    {
        abort_unless($request->user()->papel->podeConsultarClientes(), 403);
        abort_unless($cliente->barbearia_id === $request->user()->barbearia_id, 404);

        $cliente->update(['aceita_lembretes' => false]);
        Lembrete::query()->where('barbearia_id', $cliente->barbearia_id)->where('cliente_id', $cliente->id)
            ->where('status', 'pendente')->update(['status' => 'cancelado']);

        return back()->with('sucesso', 'Consentimento removido e lembretes pendentes cancelados.');
    }
}
