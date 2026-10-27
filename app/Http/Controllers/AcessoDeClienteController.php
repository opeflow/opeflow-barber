<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriarContaDeClienteRequest;
use App\Http\Requests\EntrarContaDeClienteRequest;
use App\Http\Requests\ReagendarAgendamentoDeClienteRequest;
use App\Http\Requests\RedefinirSenhaDeClienteRequest;
use App\Http\Requests\SolicitarRedefinicaoDeSenhaDeClienteRequest;
use App\Models\Agendamento;
use App\Models\Barbearia;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\ContaDeCliente;
use App\Models\Profissional;
use App\Models\Servico;
use App\Notifications\RedefinirSenhaDaContaDeCliente;
use App\Notifications\VerificarEmailDaContaDeCliente;
use App\Services\Agenda\CalculadoraDeDisponibilidade;
use App\Services\Lembretes\AgendadorDeLembretes;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcessoDeClienteController extends Controller
{
    public function formulario(): Response
    {
        return Inertia::render('Cliente/Entrar', ['mostrarCredenciaisDeDesenvolvimento' => app()->environment('local')]);
    }

    public function entrar(EntrarContaDeClienteRequest $request): RedirectResponse
    {
        $request->autenticar();

        return to_route('cliente.inicio');
    }

    public function inicio(Request $request): Response
    {
        $conta = $request->user('cliente');
        abort_unless($conta->hasVerifiedEmail(), 403, 'Confirme seu e-mail antes de acessar o portal.');
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
                'podeCancelar' => in_array($agendamento->status, ['aguardando_confirmacao', 'confirmado'], true)
                    && $agendamento->inicio_em->isFuture(),
                'podeReagendar' => in_array($agendamento->status, ['aguardando_confirmacao', 'confirmado'], true)
                    && $agendamento->inicio_em->isFuture(),
                'dataISO' => $agendamento->inicio_em->setTimezone($agendamento->barbearia->fuso_horario)->format('Y-m-d'),
                'horaISO' => $agendamento->inicio_em->setTimezone($agendamento->barbearia->fuso_horario)->format('H:i'),
            ]);

        return Inertia::render('Cliente/Inicio', [
            'nome' => $conta->nome,
            'agendamentos' => $agendamentos,
            'linkAgendamento' => route('agendamento.publico', $conta->barbearia->slug),
        ]);
    }

    public function cancelar(Request $request, Agendamento $agendamento): RedirectResponse
    {
        $conta = $request->user('cliente');
        abort_unless($conta->hasVerifiedEmail(), 403, 'Confirme seu e-mail antes de acessar o portal.');

        abort_unless(
            $agendamento->barbearia_id === $conta->barbearia_id && $agendamento->cliente_id === $conta->cliente_id,
            404,
        );

        DB::transaction(function () use ($agendamento): void {
            $agendamento = Agendamento::query()->whereKey($agendamento->id)->lockForUpdate()->firstOrFail();
            abort_unless(
                in_array($agendamento->status, ['aguardando_confirmacao', 'confirmado'], true)
                    && $agendamento->inicio_em->isFuture(),
                422,
                'Somente horários futuros ainda não iniciados podem ser cancelados.',
            );

            $agendamento->cancelarAgendamento();

            if ($agendamento->beneficio_do_cliente_id) {
                $beneficio = BeneficioDoCliente::query()
                    ->where('barbearia_id', $agendamento->barbearia_id)
                    ->whereKey($agendamento->beneficio_do_cliente_id)
                    ->lockForUpdate()
                    ->first();

                if ($beneficio?->status === 'reservado') {
                    $beneficio->update([
                        'status' => $beneficio->expira_em->greaterThan(CarbonImmutable::now('UTC')) ? 'disponivel' : 'expirado',
                    ]);
                }
            }
        });

        app(AgendadorDeLembretes::class)->cancelarPendentesDoAgendamento($agendamento);

        return to_route('cliente.inicio')->with('sucesso', 'Seu agendamento foi cancelado.');
    }

    public function reagendar(ReagendarAgendamentoDeClienteRequest $request, Agendamento $agendamento): RedirectResponse
    {
        $conta = $request->user('cliente');
        abort_unless($conta->hasVerifiedEmail(), 403, 'Confirme seu e-mail antes de acessar o portal.');
        abort_unless($agendamento->barbearia_id === $conta->barbearia_id && $agendamento->cliente_id === $conta->cliente_id, 404);

        $novoAgendamento = DB::transaction(function () use ($request, $agendamento, $conta): Agendamento {
            $barbearia = Barbearia::query()->whereKey($conta->barbearia_id)->lockForUpdate()->firstOrFail();
            $anterior = Agendamento::query()->where('barbearia_id', $conta->barbearia_id)
                ->where('cliente_id', $conta->cliente_id)->whereKey($agendamento->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($anterior->status, ['aguardando_confirmacao', 'confirmado'], true) && $anterior->inicio_em->isFuture(), 422,
                'Somente horários futuros ainda não iniciados podem ser reagendados.');

            $fuso = $barbearia->fuso_horario ?: config('app.timezone');
            $inicio = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->validated('data').' '.$request->validated('hora'), $fuso);
            $agora = CarbonImmutable::now($fuso);
            if ($inicio->lessThanOrEqualTo($agora) || $inicio->greaterThan($agora->addDays(60))) {
                throw ValidationException::withMessages(['hora' => 'Escolha um horário futuro dentro dos próximos 60 dias.']);
            }

            $profissional = Profissional::query()->where('barbearia_id', $conta->barbearia_id)
                ->whereKey($anterior->profissional_id)->lockForUpdate()->firstOrFail();
            $duracao = (int) $anterior->inicio_em->diffInMinutes($anterior->fim_em);
            if ($duracao < 1) {
                $duracao = (int) Servico::query()->where('barbearia_id', $conta->barbearia_id)
                    ->whereKey($anterior->servico_id)->value('duracao_em_minutos');
            }
            $fim = $inicio->addMinutes($duracao);

            $beneficio = null;
            if ($anterior->beneficio_do_cliente_id) {
                $beneficio = BeneficioDoCliente::query()->where('barbearia_id', $conta->barbearia_id)
                    ->where('cliente_id', $conta->cliente_id)->whereKey($anterior->beneficio_do_cliente_id)->lockForUpdate()->first();
                if (! $beneficio || $beneficio->status !== 'reservado'
                    || $inicio->utc()->lessThan($beneficio->disponivel_em)
                    || $inicio->utc()->greaterThanOrEqualTo($beneficio->expira_em)) {
                    throw ValidationException::withMessages(['hora' => 'O benefício reservado não é válido para esse novo horário.']);
                }
            }

            $anterior->marcarComoReagendado();
            if (! app(CalculadoraDeDisponibilidade::class)
                ->profissionalEstaDisponivel($profissional, $inicio->utc(), $fim->utc())) {
                throw ValidationException::withMessages(['hora' => 'Esse horário não está disponível. Escolha outro horário.']);
            }

            return Agendamento::create([
                'barbearia_id' => $anterior->barbearia_id,
                'cliente_id' => $anterior->cliente_id,
                'profissional_id' => $anterior->profissional_id,
                'servico_id' => $anterior->servico_id,
                'agendamento_origem_id' => $anterior->id,
                'beneficio_do_cliente_id' => $anterior->beneficio_do_cliente_id,
                'inicio_em' => $inicio->utc(),
                'fim_em' => $fim->utc(),
                'status' => 'confirmado',
                'chave_idempotencia_publica' => (string) Str::uuid(),
            ]);
        });

        app(AgendadorDeLembretes::class)->cancelarPendentesDoAgendamento($agendamento);
        app(AgendadorDeLembretes::class)->agendarParaAgendamento($novoAgendamento);

        return to_route('cliente.inicio')->with('sucesso', 'Agendamento reagendado. O horário anterior foi mantido no seu histórico.');
    }

    public function sair(Request $request): RedirectResponse
    {
        Auth::guard('cliente')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('cliente.entrar');
    }

    public function formularioDeCadastro(): Response
    {
        return Inertia::render('Cliente/Cadastro');
    }

    public function cadastrar(CriarContaDeClienteRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $barbearia = Barbearia::query()->where('slug', $dados['barbearia'])->where('ativa', true)->first();
        $cliente = $barbearia
            ? Cliente::query()->where('barbearia_id', $barbearia->id)->whereRaw('LOWER(email) = ?', [$dados['email']])->first()
            : null;

        if ($cliente) {
            $conta = ContaDeCliente::query()->firstOrCreate(
                ['cliente_id' => $cliente->id],
                [
                    'barbearia_id' => $barbearia->id,
                    'nome' => $cliente->nome,
                    'email' => $dados['email'],
                    'senha' => $dados['senha'],
                    'ativo' => true,
                ],
            );

            if (! $conta->hasVerifiedEmail()) {
                $conta->notify(new VerificarEmailDaContaDeCliente);
            }
        }

        return back()->with('sucesso', 'Se os dados corresponderem a um cadastro da barbearia, enviaremos um link de confirmação para o e-mail informado.');
    }

    public function verificarEmail(Request $request, int $conta, string $hash): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        $contaCliente = ContaDeCliente::query()->findOrFail($conta);
        abort_unless(hash_equals(sha1($contaCliente->getEmailForVerification()), $hash), 403);

        if (! $contaCliente->hasVerifiedEmail()) {
            $contaCliente->markEmailAsVerified();
        }

        return to_route('cliente.entrar')->with('sucesso', 'E-mail confirmado. Agora você pode entrar no portal.');
    }

    public function formularioDeRedefinicao(): Response
    {
        return Inertia::render('Cliente/EsqueciSenha');
    }

    public function solicitarRedefinicao(SolicitarRedefinicaoDeSenhaDeClienteRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $conta = ContaDeCliente::query()
            ->where('email', $email)
            ->where('ativo', true)
            ->whereNotNull('email_verified_at')
            ->first();

        if ($conta) {
            $token = Str::random(64);
            $tokenParaEnviar = DB::transaction(function () use ($conta, $token): ?string {
                $contaBloqueada = ContaDeCliente::query()->whereKey($conta->id)->lockForUpdate()->first();
                if (! $contaBloqueada || ! $contaBloqueada->ativo || ! $contaBloqueada->hasVerifiedEmail()) {
                    return null;
                }

                $solicitacaoRecente = DB::table('tokens_de_redefinicao_de_senha_de_clientes')
                    ->where('conta_de_cliente_id', $contaBloqueada->id)
                    ->where('created_at', '>', now()->subMinute())
                    ->exists();
                if ($solicitacaoRecente) {
                    return null;
                }

                DB::table('tokens_de_redefinicao_de_senha_de_clientes')->updateOrInsert(
                    ['conta_de_cliente_id' => $contaBloqueada->id],
                    [
                        'barbearia_id' => $contaBloqueada->barbearia_id,
                        'token_hash' => hash('sha256', $token),
                        'expira_em' => now()->addMinutes(60),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );

                return $token;
            });

            if ($tokenParaEnviar !== null) {
                $conta->notify(new RedefinirSenhaDaContaDeCliente($tokenParaEnviar));
            }
        }

        return back()->with('sucesso', 'Se houver uma conta verificada para esse e-mail, enviaremos instruções de redefinição.');
    }

    public function formularioDeNovaSenha(): Response
    {
        return Inertia::render('Cliente/RedefinirSenha');
    }

    public function redefinirSenha(RedefinirSenhaDeClienteRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $redefinido = DB::transaction(function () use ($dados): bool {
            $token = DB::table('tokens_de_redefinicao_de_senha_de_clientes')
                ->where('token_hash', hash('sha256', $dados['token']))
                ->lockForUpdate()
                ->first();

            if (! $token || now()->greaterThan($token->expira_em)) {
                return false;
            }

            $conta = ContaDeCliente::query()->where('barbearia_id', $token->barbearia_id)
                ->whereKey($token->conta_de_cliente_id)->lockForUpdate()->first();
            if (! $conta || ! $conta->ativo || ! $conta->hasVerifiedEmail()) {
                return false;
            }

            $conta->update(['senha' => $dados['senha']]);
            DB::table('tokens_de_redefinicao_de_senha_de_clientes')->where('id', $token->id)->delete();

            return true;
        });

        if (! $redefinido) {
            throw ValidationException::withMessages(['token' => 'O link expirou ou já foi utilizado. Solicite uma nova redefinição.']);
        }

        return to_route('cliente.entrar')->with('sucesso', 'Senha redefinida. Você já pode entrar no portal.');
    }
}
