<?php

namespace App\Http\Controllers;

use App\Models\Lembrete;
use App\Services\Beneficios\CodigoDeResgateDeBeneficio;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LembretesController extends Controller
{
    public function exibir(Request $request): Response
    {
        $usuario = $request->user();
        abort_unless($usuario->papel->podeGerenciarAtendimento(), 403);
        $fuso = $usuario->barbearia->fuso_horario;
        $lembretes = Lembrete::query()->where('barbearia_id', $usuario->barbearia_id)
            ->with(['cliente:id,nome,telefone', 'agendamento.servico:id,nome', 'agendamento.profissional:id,nome', 'beneficio.regra.servicoBeneficiado:id,nome'])
            ->whereIn('status', ['pendente', 'enviado_manualmente', 'cancelado'])
            ->orderByRaw("CASE WHEN status = 'pendente' THEN 0 ELSE 1 END")
            ->orderBy('agendado_para')->limit(200)->get()
            ->map(function (Lembrete $lembrete) use ($fuso): array {
                $mensagem = $this->mensagem($lembrete, $fuso);
                $telefone = preg_replace('/\D+/', '', $lembrete->cliente->telefone);
                if (in_array(strlen($telefone), [10, 11], true)) {
                    $telefone = '55'.$telefone;
                }

                return [
                    'id' => $lembrete->id, 'tipo' => $lembrete->tipo,
                    'status' => $lembrete->status, 'agendadoPara' => $lembrete->agendado_para->setTimezone($fuso)->format('d/m/Y H:i'),
                    'enviadoEm' => $lembrete->enviado_manualmente_em?->setTimezone($fuso)->format('d/m/Y H:i'),
                    'cliente' => $lembrete->cliente->nome, 'telefone' => $lembrete->cliente->telefone,
                    'mensagem' => $mensagem,
                    'podeEnviar' => $lembrete->status === 'pendente' && $lembrete->agendado_para->lessThanOrEqualTo(CarbonImmutable::now('UTC')),
                    'linkWhatsApp' => 'https://wa.me/'.$telefone.'?text='.urlencode($mensagem),
                ];
            });

        return Inertia::render('Lembretes/Index', [
            'lembretes' => $lembretes,
            'linkAgendamento' => route('agendamento.publico', $usuario->barbearia->slug),
        ]);
    }

    public function marcarEnviado(Request $request, Lembrete $lembrete): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless($usuario->papel->podeGerenciarAtendimento(), 403);
        abort_unless($lembrete->barbearia_id === $usuario->barbearia_id, 404);
        abort_unless($lembrete->status === 'pendente', 422, 'Este lembrete não está pendente.');

        $lembrete->update([
            'status' => 'enviado_manualmente',
            'enviado_manualmente_em' => CarbonImmutable::now('UTC'),
            'enviado_por_usuario_id' => $usuario->id,
        ]);

        return back()->with('sucesso', 'Envio registrado manualmente.');
    }

    private function mensagem(Lembrete $lembrete, string $fuso): string
    {
        $barbearia = $lembrete->barbearia()->firstOrFail();
        $link = route('agendamento.publico', $barbearia->slug);
        $nome = $lembrete->cliente->nome;

        if ($lembrete->agendamento) {
            $agendamento = $lembrete->agendamento;
            $quando = $agendamento->inicio_em->setTimezone($fuso)->format('d/m \à\s H:i');

            return "Olá, {$nome}! Passando para lembrar do seu horário de {$agendamento->servico->nome} na {$barbearia->nome}, com {$agendamento->profissional->nome}, em {$quando}. Para consultar outros horários, acesse {$link}";
        }

        $beneficio = $lembrete->beneficio;
        $servico = $beneficio?->regra?->servicoBeneficiado?->nome ?? 'seu benefício';
        $validade = $beneficio?->expira_em?->setTimezone($fuso)->format('d/m/Y');
        $codigo = $beneficio ? app(CodigoDeResgateDeBeneficio::class)->gerar($beneficio) : '';

        return "Olá, {$nome}! {$servico} está disponível como benefício na {$barbearia->nome}".($validade ? " até {$validade}" : '').". Código: {$codigo}. Para agendar, acesse {$link}";
    }
}
