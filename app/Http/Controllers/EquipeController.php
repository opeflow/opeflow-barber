<?php

namespace App\Http\Controllers;

use App\Enums\PapelDeUsuario;
use App\Http\Requests\CriarConviteDeEquipeRequest;
use App\Models\Profissional;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EquipeController extends Controller
{
    public function exibir(Request $request): Response
    {
        abort_unless($request->user()->papel->podeGerenciarEquipe(), 403);
        $barbeariaId = $request->user()->barbearia_id;

        return Inertia::render('Configuracoes/Equipe', [
            'usuarios' => Usuario::query()->where('barbearia_id', $barbeariaId)->where('id', '!=', $request->user()->id)->with('profissional:id,nome')->orderBy('nome')->get(['id', 'nome', 'email', 'papel', 'ativo', 'profissional_id', 'convite_expira_em', 'convite_aceito_em'])->map(fn (Usuario $usuario) => [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
                'email' => $usuario->email,
                'papel' => $usuario->papel->value,
                'ativo' => $usuario->ativo,
                'profissional' => $usuario->profissional?->nome,
                'conviteAceito' => $usuario->convite_aceito_em !== null,
                'convitePendente' => ! $usuario->ativo && $usuario->convite_token_hash !== null && $usuario->convite_expira_em?->isFuture(),
            ]),
            'profissionais' => Profissional::query()->where('barbearia_id', $barbeariaId)->where('ativo', true)
                ->whereNotIn('id', Usuario::query()->where('barbearia_id', $barbeariaId)->whereNotNull('profissional_id')->select('profissional_id'))
                ->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function convidar(CriarConviteDeEquipeRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $token = Str::random(64);
        $usuario = Usuario::create([
            'barbearia_id' => $request->user()->barbearia_id,
            'profissional_id' => $dados['profissional_id'] ?? null,
            'nome' => $dados['nome'],
            'email' => mb_strtolower($dados['email']),
            'papel' => $dados['papel'],
            'senha' => Hash::make(Str::random(64)),
            'ativo' => false,
            'convite_token_hash' => hash('sha256', $token),
            'convite_expira_em' => now()->addHours(48),
        ]);

        return back()->with('sucesso', 'Convite criado. Copie e envie o link com segurança.')->with('conviteEquipe', [
            'nome' => $usuario->nome,
            'url' => route('equipe.aceitar').'#'.$token,
            'expiraEm' => $usuario->convite_expira_em->timezone($request->user()->barbearia->fuso_horario)->format('d/m/Y H:i'),
        ]);
    }

    public function formularioDeAceite(): Response
    {
        return Inertia::render('Autenticacao/AceitarConvite');
    }

    public function validarConvite(Request $request): RedirectResponse
    {
        $dados = $request->validate(['token' => ['required', 'string', 'size:64', 'alpha_num']]);
        $usuario = $this->encontrarConvite($dados['token']);

        return back()->with('conviteAceite', ['nome' => $usuario->nome, 'email' => $usuario->email, 'valido' => true]);
    }

    public function aceitar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'token' => ['required', 'string', 'size:64', 'alpha_num'],
            'senha' => ['required', 'string', 'min:12', 'confirmed'],
        ], [
            'senha.min' => 'A senha deve ter pelo menos 12 caracteres.',
            'senha.confirmed' => 'A confirmação da senha não confere.',
        ]);
        $usuario = DB::transaction(function () use ($dados): Usuario {
            $usuario = Usuario::query()->where('convite_token_hash', hash('sha256', $dados['token']))->whereNull('convite_aceito_em')->lockForUpdate()->first();
            if (! $usuario || ! $usuario->convite_expira_em?->isFuture()) {
                throw ValidationException::withMessages(['convite' => 'Este convite é inválido, expirou ou já foi utilizado. Peça um novo link ao proprietário.']);
            }
            $usuario->update(['senha' => $dados['senha'], 'ativo' => true, 'convite_token_hash' => null, 'convite_aceito_em' => now()]);

            return $usuario;
        });
        auth()->login($usuario);
        $request->session()->regenerate();

        return to_route('inicio')->with('sucesso', 'Acesso ativado. Bem-vindo à equipe!');
    }

    public function alterarAcesso(Request $request, Usuario $usuario): RedirectResponse
    {
        abort_unless($request->user()->papel->podeGerenciarEquipe(), 403);
        abort_unless($usuario->barbearia_id === $request->user()->barbearia_id, 404);
        abort_if($usuario->id === $request->user()->id || $usuario->papel === PapelDeUsuario::Proprietario, 422);
        abort_if(! $usuario->ativo && $usuario->convite_token_hash !== null, 422, 'Um convite ainda não aceito não pode ser ativado manualmente. Gere um novo link de convite.');
        $usuario->update(['ativo' => ! $usuario->ativo]);

        return back()->with('sucesso', $usuario->ativo ? 'Acesso reativado.' : 'Acesso desativado.');
    }

    public function renovarConvite(Request $request, Usuario $usuario): RedirectResponse
    {
        abort_unless($request->user()->papel->podeGerenciarEquipe(), 403);
        abort_unless($usuario->barbearia_id === $request->user()->barbearia_id, 404);
        abort_unless(! $usuario->ativo && $usuario->convite_aceito_em === null, 422);
        $token = Str::random(64);
        $usuario->update(['convite_token_hash' => hash('sha256', $token), 'convite_expira_em' => now()->addHours(48)]);

        return back()->with('sucesso', 'Novo convite criado. O link anterior foi invalidado.')->with('conviteEquipe', [
            'nome' => $usuario->nome,
            'url' => route('equipe.aceitar').'#'.$token,
            'expiraEm' => $usuario->convite_expira_em->timezone($request->user()->barbearia->fuso_horario)->format('d/m/Y H:i'),
        ]);
    }

    private function encontrarConvite(string $token): Usuario
    {
        $usuario = Usuario::query()->where('convite_token_hash', hash('sha256', $token))->whereNull('convite_aceito_em')->first();
        if (! $usuario || ! $usuario->convite_expira_em?->isFuture()) {
            throw ValidationException::withMessages(['convite' => 'Este convite é inválido, expirou ou já foi utilizado. Peça um novo link ao proprietário.']);
        }

        return $usuario;
    }
}
