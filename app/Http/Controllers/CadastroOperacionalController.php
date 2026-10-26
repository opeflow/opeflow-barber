<?php

namespace App\Http\Controllers;

use App\Http\Requests\CriarBloqueioDeAgendaRequest;
use App\Http\Requests\CriarPausaDeTrabalhoRequest;
use App\Http\Requests\SalvarRegraDeBeneficioRequest;
use App\Models\Agendamento;
use App\Models\BloqueioDeAgenda;
use App\Models\HorarioDeTrabalho;
use App\Models\ItemDaFila;
use App\Models\PausaDeTrabalho;
use App\Models\Profissional;
use App\Models\RegraDeBeneficio;
use App\Models\Servico;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CadastroOperacionalController extends Controller
{
    public function exibir(): Response
    {
        $this->autorizarGerenciamento();
        $barbeariaId = $this->barbeariaIdAtual();

        return Inertia::render('Configuracoes/Operacao', [
            'barbearia' => [
                'nome' => auth()->user()->barbearia->nome,
                'logoUrl' => auth()->user()->barbearia->logo_path ? route('barbearia.logo') : null,
                'corPrimaria' => auth()->user()->barbearia->cor_primaria,
                'corNavegacao' => auth()->user()->barbearia->cor_navegacao,
                'linkAgendamento' => route('agendamento.publico', auth()->user()->barbearia->slug),
            ],
            'dataHoraLocal' => now(auth()->user()->barbearia->fuso_horario)->format('Y-m-d\\TH:i'),
            'fimHoraLocal' => now(auth()->user()->barbearia->fuso_horario)->addHour()->format('Y-m-d\\TH:i'),
            'profissionais' => Profissional::query()
                ->where('barbearia_id', $barbeariaId)
                ->with(['servicos:id,nome', 'horariosDeTrabalho.pausas', 'bloqueiosDeAgenda' => fn ($consulta) => $consulta->where('fim_em', '>', now('UTC'))->orderBy('inicio_em')])
                ->get()
                ->map(function (Profissional $profissional): array {
                    $fuso = auth()->user()->barbearia->fuso_horario;

                    return [
                        'id' => $profissional->id,
                        'nome' => $profissional->nome,
                        'servicos' => $profissional->servicos,
                        'horarios_de_trabalho' => $profissional->horariosDeTrabalho,
                        'bloqueios' => $profissional->bloqueiosDeAgenda->map(fn (BloqueioDeAgenda $bloqueio) => [
                            'id' => $bloqueio->id,
                            'inicio_em' => $bloqueio->inicio_em->setTimezone($fuso)->format('Y-m-d H:i'),
                            'fim_em' => $bloqueio->fim_em->setTimezone($fuso)->format('Y-m-d H:i'),
                            'motivo' => $bloqueio->motivo,
                        ]),
                    ];
                }),
            'servicos' => Servico::query()
                ->where('barbearia_id', $barbeariaId)
                ->orderBy('nome')
                ->get(),
            'regraDeBeneficio' => RegraDeBeneficio::query()->where('barbearia_id', $barbeariaId)->first(),
        ]);
    }

    public function salvarRegraDeBeneficio(SalvarRegraDeBeneficioRequest $request): RedirectResponse
    {
        RegraDeBeneficio::updateOrCreate(
            ['barbearia_id' => $this->barbeariaIdAtual()],
            $request->validated(),
        );

        return back()->with('sucesso', 'Regra de benefício salva. Ela será emitida após concluir um atendimento elegível.');
    }

    public function atualizarIdentidade(Request $request): RedirectResponse
    {
        $this->autorizarGerenciamento();

        $dados = $request->validate([
            'cor_primaria' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cor_navegacao' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remover_logo' => ['sometimes', 'boolean'],
        ], [
            'cor_primaria.regex' => 'Informe uma cor principal válida no formato hexadecimal.',
            'cor_navegacao.regex' => 'Informe uma cor de navegação válida no formato hexadecimal.',
            'logo.image' => 'O arquivo enviado precisa ser uma imagem.',
            'logo.mimes' => 'Use uma imagem PNG, JPG ou WebP.',
            'logo.max' => 'A logo deve ter no máximo 2 MB.',
        ]);

        $barbearia = auth()->user()->barbearia;
        $logoAnterior = $barbearia->logo_path;
        $logoNova = $logoAnterior;

        if ($request->hasFile('logo')) {
            $arquivo = $request->file('logo');
            $nome = Str::uuid().'.'.$arquivo->guessExtension();
            $logoNova = $arquivo->storeAs('barbearias/'.$barbearia->id, $nome, 'public');
        } elseif ($request->boolean('remover_logo')) {
            $logoNova = null;
        }

        try {
            $barbearia->update([
                'cor_primaria' => strtoupper($dados['cor_primaria']),
                'cor_navegacao' => strtoupper($dados['cor_navegacao']),
                'logo_path' => $logoNova,
            ]);
        } catch (\Throwable $erro) {
            if ($logoNova && $logoNova !== $logoAnterior) {
                Storage::disk('public')->delete($logoNova);
            }

            throw $erro;
        }

        if ($logoAnterior && $logoAnterior !== $logoNova) {
            Storage::disk('public')->delete($logoAnterior);
        }

        return back()->with('sucesso', 'Identidade visual atualizada.');
    }

    public function exibirLogo(Request $request): StreamedResponse
    {
        $caminho = $request->user()->barbearia->logo_path;
        abort_unless($caminho && Storage::disk('public')->exists($caminho), 404);

        return Storage::disk('public')->response($caminho, null, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function criarProfissional(Request $request): RedirectResponse
    {
        $this->autorizarGerenciamento();

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'telefone' => ['nullable', 'string', 'max:20'],
        ]);

        Profissional::create([...$dados, 'barbearia_id' => $this->barbeariaIdAtual()]);

        return back()->with('sucesso', 'Profissional cadastrado.');
    }

    public function criarServico(Request $request): RedirectResponse
    {
        $this->autorizarGerenciamento();

        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'descricao' => ['nullable', 'string', 'max:1000'],
            'duracao_em_minutos' => ['required', 'integer', 'min:5', 'max:480'],
            'preco' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ]);

        Servico::create([...$dados, 'barbearia_id' => $this->barbeariaIdAtual()]);

        return back()->with('sucesso', 'Serviço cadastrado.');
    }

    public function vincularServico(Request $request, Profissional $profissional): RedirectResponse
    {
        $this->autorizarGerenciamento();
        $this->garantirProfissionalDaBarbearia($profissional);

        $dados = $request->validate([
            'servico_id' => [
                'required',
                Rule::exists('servicos', 'id')->where('barbearia_id', $this->barbeariaIdAtual()),
            ],
        ]);

        $profissional->servicos()->syncWithoutDetaching([$dados['servico_id']]);

        return back()->with('sucesso', 'Serviço vinculado ao profissional.');
    }

    public function adicionarHorario(Request $request, Profissional $profissional): RedirectResponse
    {
        $this->autorizarGerenciamento();
        $this->garantirProfissionalDaBarbearia($profissional);

        $dados = $request->validate([
            'dia_da_semana' => ['required', 'integer', 'between:0,6'],
            'inicio_as' => ['required', 'date_format:H:i'],
            'fim_as' => ['required', 'date_format:H:i', 'after:inicio_as'],
        ]);

        DB::transaction(function () use ($dados, $profissional): void {
            HorarioDeTrabalho::create([
                ...$dados,
                'barbearia_id' => $this->barbeariaIdAtual(),
                'profissional_id' => $profissional->id,
            ]);
        });

        return back()->with('sucesso', 'Horário de trabalho adicionado.');
    }

    public function criarPausa(CriarPausaDeTrabalhoRequest $request, Profissional $profissional, HorarioDeTrabalho $horario): RedirectResponse
    {
        $this->garantirProfissionalDaBarbearia($profissional);
        abort_unless($horario->barbearia_id === $this->barbeariaIdAtual() && $horario->profissional_id === $profissional->id, 404);
        $dados = $request->validated();

        DB::transaction(function () use ($horario, $dados): void {
            if ($dados['inicio_as'] < $horario->inicio_as || $dados['fim_as'] > $horario->fim_as) {
                throw ValidationException::withMessages(['inicio_as' => 'A pausa deve estar inteiramente dentro do horário de trabalho.']);
            }

            if ($horario->pausas()->where('inicio_as', '<', $dados['fim_as'])->where('fim_as', '>', $dados['inicio_as'])->exists()) {
                throw ValidationException::withMessages(['inicio_as' => 'Esta pausa sobrepõe outra pausa do mesmo horário.']);
            }

            PausaDeTrabalho::create(['barbearia_id' => $this->barbeariaIdAtual(), 'horario_de_trabalho_id' => $horario->id, ...$dados]);
        });

        return back()->with('sucesso', 'Pausa recorrente adicionada.');
    }

    public function removerPausa(Profissional $profissional, HorarioDeTrabalho $horario, PausaDeTrabalho $pausa): RedirectResponse
    {
        $this->autorizarGerenciamento();
        $this->garantirProfissionalDaBarbearia($profissional);
        abort_unless($horario->barbearia_id === $this->barbeariaIdAtual() && $horario->profissional_id === $profissional->id, 404);
        abort_unless($pausa->barbearia_id === $this->barbeariaIdAtual() && $pausa->horario_de_trabalho_id === $horario->id, 404);
        $pausa->delete();

        return back()->with('sucesso', 'Pausa recorrente removida.');
    }

    public function criarBloqueio(CriarBloqueioDeAgendaRequest $request, Profissional $profissional): RedirectResponse
    {
        $this->garantirProfissionalDaBarbearia($profissional);
        $dados = $request->validated();
        $fuso = auth()->user()->barbearia->fuso_horario;
        $inicio = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $dados['inicio_em'], $fuso)->utc();
        $fim = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $dados['fim_em'], $fuso)->utc();

        DB::transaction(function () use ($profissional, $dados, $inicio, $fim): void {
            Profissional::query()->whereKey($profissional->id)->lockForUpdate()->firstOrFail();

            $conflitoAgendamento = Agendamento::query()
                ->where('barbearia_id', $this->barbeariaIdAtual())->where('profissional_id', $profissional->id)
                ->whereNotIn('status', ['cancelado', 'reagendado', 'nao_compareceu'])
                ->where('inicio_em', '<', $fim)->where('fim_em', '>', $inicio)->exists();
            $conflitoBloqueio = BloqueioDeAgenda::query()->where('profissional_id', $profissional->id)
                ->where('inicio_em', '<', $fim)->where('fim_em', '>', $inicio)->exists();
            $conflitoFila = ItemDaFila::query()->where('barbearia_id', $this->barbeariaIdAtual())
                ->where('profissional_id', $profissional->id)->whereIn('status', ['chamado', 'em_atendimento'])->exists();

            if ($conflitoAgendamento || $conflitoBloqueio || $conflitoFila) {
                throw ValidationException::withMessages(['inicio_em' => 'Este período conflita com um agendamento, bloqueio ou atendimento em andamento.']);
            }

            BloqueioDeAgenda::create([
                'barbearia_id' => $this->barbeariaIdAtual(), 'profissional_id' => $profissional->id,
                'criado_por_usuario_id' => auth()->id(), 'inicio_em' => $inicio, 'fim_em' => $fim,
                'motivo' => $dados['motivo'] ?? null,
            ]);
        });

        return back()->with('sucesso', 'Bloqueio de agenda cadastrado.');
    }

    public function removerBloqueio(Profissional $profissional, BloqueioDeAgenda $bloqueio): RedirectResponse
    {
        $this->autorizarGerenciamento();
        $this->garantirProfissionalDaBarbearia($profissional);
        abort_unless($bloqueio->barbearia_id === $this->barbeariaIdAtual() && $bloqueio->profissional_id === $profissional->id, 404);
        $bloqueio->delete();

        return back()->with('sucesso', 'Bloqueio removido; o período voltou a ficar disponível.');
    }

    private function barbeariaIdAtual(): int
    {
        return auth()->user()->barbearia_id;
    }

    private function autorizarGerenciamento(): void
    {
        abort_unless(auth()->user()->papel->podeGerenciarConfiguracoes(), 403);
    }

    private function garantirProfissionalDaBarbearia(Profissional $profissional): void
    {
        abort_unless($profissional->barbearia_id === $this->barbeariaIdAtual(), 404);
    }
}
