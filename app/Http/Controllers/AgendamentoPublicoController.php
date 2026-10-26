<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriarAgendamentoPublicoRequest;
use App\Models\Agendamento;
use App\Models\Barbearia;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Services\Agenda\CalculadoraDeDisponibilidade;
use App\Services\Agenda\GeradorDeHorariosPublicos;
use App\Services\Beneficios\CodigoDeResgateDeBeneficio;
use App\Services\Lembretes\AgendadorDeLembretes;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgendamentoPublicoController extends Controller
{
    public function exibir(Request $request, Barbearia $barbearia, GeradorDeHorariosPublicos $gerador): Response
    {
        abort_unless($barbearia->ativa, 404);
        $fuso = $barbearia->fuso_horario ?: config('app.timezone');
        $hoje = CarbonImmutable::now($fuso)->toDateString();
        $servicos = Servico::query()->where('barbearia_id', $barbearia->id)->where('ativo', true)->orderBy('nome')->get(['id', 'nome', 'descricao', 'duracao_em_minutos', 'preco']);
        $servicoId = $request->integer('servico_id') ?: null;
        $servico = $servicos->firstWhere('id', $servicoId);
        $profissionais = Profissional::query()
            ->where('barbearia_id', $barbearia->id)
            ->where('ativo', true)
            ->when($servico, fn ($consulta) => $consulta->whereHas('servicos', fn ($vinculos) => $vinculos->where('servicos.id', $servico->id)))
            ->orderBy('nome')
            ->get(['id', 'nome']);
        $profissionalId = $request->integer('profissional_id') ?: null;
        $data = $request->query('data', $hoje);
        if (! is_string($data) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || $data < $hoje || $data > CarbonImmutable::parse($hoje, $fuso)->addDays(60)->toDateString()) {
            $data = $hoje;
        }
        if ($profissionalId && ! $profissionais->contains('id', $profissionalId)) {
            $profissionalId = null;
        }

        return Inertia::render('AgendamentoPublico/Index', [
            'barbearia' => ['nome' => $barbearia->nome, 'slug' => $barbearia->slug, 'corPrimaria' => $barbearia->cor_primaria, 'logoUrl' => $barbearia->logo_path ? route('agendamento.publico.logo', $barbearia->slug) : null],
            'servicos' => $servicos,
            'profissionais' => $profissionais,
            'servicoSelecionado' => $servicoId,
            'profissionalSelecionado' => $profissionalId,
            'dataSelecionada' => $data,
            'dataMinima' => $hoje,
            'dataMaxima' => CarbonImmutable::parse($hoje, $fuso)->addDays(60)->toDateString(),
            'horarios' => $servico ? $gerador->listarHorarios($barbearia, $servico, $data, $profissionalId, app(CalculadoraDeDisponibilidade::class)) : [],
            'reserva' => $request->session()->get('reserva_publica'),
        ]);
    }

    public function criar(CriarAgendamentoPublicoRequest $request, Barbearia $barbearia, CalculadoraDeDisponibilidade $disponibilidade): RedirectResponse
    {
        abort_unless($barbearia->ativa, 404);
        $dados = $request->validated();
        $telefone = preg_replace('/\D+/', '', $dados['telefone']);
        if (strlen($telefone) < 10 || strlen($telefone) > 15) {
            return back()->withErrors(['telefone' => 'Informe um telefone válido com DDD.'])->withInput();
        }
        $agendamento = DB::transaction(function () use ($dados, $telefone, $barbearia, $disponibilidade): Agendamento {
            $barbearia = Barbearia::query()->whereKey($barbearia->id)->lockForUpdate()->firstOrFail();
            $existente = Agendamento::query()->where('barbearia_id', $barbearia->id)->where('chave_idempotencia_publica', $dados['chave_idempotencia'])->first();
            if ($existente) {
                return $existente;
            }
            $servico = Servico::query()->where('barbearia_id', $barbearia->id)->where('ativo', true)->findOrFail($dados['servico_id']);
            $profissional = Profissional::query()->where('barbearia_id', $barbearia->id)->where('ativo', true)->whereHas('servicos', fn ($query) => $query->where('servicos.id', $servico->id))->findOrFail($dados['profissional_id']);
            $fuso = $barbearia->fuso_horario ?: config('app.timezone');
            $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', $dados['data'].' '.$dados['hora'], $fuso);
            if ($inicio->lessThanOrEqualTo(CarbonImmutable::now($fuso)) || $inicio->greaterThan(CarbonImmutable::now($fuso)->addDays(60))) {
                throw ValidationException::withMessages(['hora' => 'Escolha um horário futuro dentro dos próximos 60 dias.']);
            }
            $profissionalBloqueado = Profissional::query()->where('barbearia_id', $barbearia->id)->whereKey($profissional->id)->lockForUpdate()->firstOrFail();
            $fim = $inicio->addMinutes($servico->duracao_em_minutos);
            if (! $disponibilidade->profissionalEstaDisponivel($profissionalBloqueado, $inicio->utc(), $fim->utc())) {
                throw ValidationException::withMessages(['hora' => 'Esse horário acabou de ficar indisponível. Escolha outro horário.']);
            }
            $cliente = Cliente::query()->where('barbearia_id', $barbearia->id)->whereRaw("regexp_replace(telefone, '[^0-9]', '', 'g') = ?", [$telefone])->first();
            if (! $cliente) {
                $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => $dados['nome'], 'telefone' => $telefone, 'email' => $dados['email'] ?? null, 'aceita_lembretes' => $dados['aceita_lembretes'] ?? false]);
            } elseif (! empty($dados['aceita_lembretes']) && ! $cliente->aceita_lembretes) {
                $cliente->update(['aceita_lembretes' => true]);
            }

            $beneficio = null;
            if (! empty($dados['codigo_beneficio'])) {
                $beneficios = BeneficioDoCliente::query()->where('barbearia_id', $barbearia->id)
                    ->where('cliente_id', $cliente->id)->where('status', 'disponivel')->lockForUpdate()->get();
                $geradorDeCodigo = app(CodigoDeResgateDeBeneficio::class);
                $beneficio = $beneficios->first(fn (BeneficioDoCliente $item): bool => hash_equals($geradorDeCodigo->gerar($item), strtoupper($dados['codigo_beneficio'])));

                if (! $beneficio) {
                    throw ValidationException::withMessages(['codigo_beneficio' => 'Código inválido, expirado ou não pertence a este telefone.']);
                }

                $regra = $beneficio->regra()->where('barbearia_id', $barbearia->id)->first();
                if (! $regra || ! $regra->ativo || $servico->id !== $regra->servico_beneficiado_id
                    || $inicio->utc()->lessThan($beneficio->disponivel_em)
                    || $inicio->utc()->greaterThanOrEqualTo($beneficio->expira_em)) {
                    throw ValidationException::withMessages(['codigo_beneficio' => 'Este benefício não é válido para o serviço ou horário escolhido.']);
                }
            }

            $agendamento = Agendamento::create([
                'barbearia_id' => $barbearia->id,
                'cliente_id' => $cliente->id,
                'profissional_id' => $profissional->id,
                'servico_id' => $servico->id,
                'beneficio_do_cliente_id' => $beneficio?->id,
                'inicio_em' => $inicio->utc(),
                'fim_em' => $inicio->addMinutes($servico->duracao_em_minutos)->utc(),
                'status' => 'confirmado',
                'chave_idempotencia_publica' => $dados['chave_idempotencia'],
            ]);
            $beneficio?->update(['status' => 'reservado']);

            return $agendamento;
        });
        app(AgendadorDeLembretes::class)->agendarParaAgendamento($agendamento);
        $agendamento->load(['servico:id,nome', 'profissional:id,nome']);
        $request->session()->flash('reserva_publica', ['data' => $agendamento->inicio_em->timezone($barbearia->fuso_horario)->format('d/m/Y'), 'hora' => $agendamento->inicio_em->timezone($barbearia->fuso_horario)->format('H:i'), 'servico' => $agendamento->servico->nome, 'profissional' => $agendamento->profissional->nome, 'beneficioAplicado' => $agendamento->beneficio_do_cliente_id !== null]);

        return redirect()->route('agendamento.publico', $barbearia->slug)->with('sucesso', 'Horário reservado com sucesso!');
    }

    public function exibirLogoPublico(Barbearia $barbearia): StreamedResponse
    {
        abort_unless($barbearia->ativa && $barbearia->logo_path && Storage::disk('public')->exists($barbearia->logo_path), 404);

        return Storage::disk('public')->response($barbearia->logo_path, null, ['Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }
}
