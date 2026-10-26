<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriarClienteRequest;
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
