<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntrarAdministradorDaPlataformaRequest;
use App\Models\Agendamento;
use App\Models\Barbearia;
use App\Models\Usuario;
use App\Services\Administracao\RegistradorDeAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AcessoDeAdministradorController extends Controller
{
    public function formulario(): Response
    {
        return Inertia::render('Administracao/Entrar', ['mostrarCredenciaisDeDesenvolvimento' => app()->environment('local')]);
    }

    public function entrar(EntrarAdministradorDaPlataformaRequest $request): RedirectResponse
    {
        $request->autenticar();

        app(RegistradorDeAuditoria::class)->registrar('autenticacao.sucesso', 'Autenticação administrativa concluída.');
        if (app()->environment(['local', 'testing'])) {
            $request->session()->put('admin_mfa_verificado', true);

            return to_route('admin.inicio');
        }

        $request->session()->put('admin_mfa_verificado', false);

        return to_route($request->user('administrador')->mfa_confirmado_em ? 'admin.mfa.verificar' : 'admin.mfa.configurar');
    }

    public function inicio(Request $request): Response
    {
        $saude = ['banco' => 'indisponível', 'redis' => 'indisponível', 'filaFalha' => null];
        try {
            DB::select('select 1');
            $saude['banco'] = 'operacional';
        } catch (\Throwable) {
            report(new \RuntimeException('Verificação de saúde do banco falhou.'));
        }
        try {
            Redis::connection()->ping();
            $saude['redis'] = 'operacional';
        } catch (\Throwable) {
            report(new \RuntimeException('Verificação de saúde do Redis falhou.'));
        }
        if (Schema::hasTable('failed_jobs')) {
            $saude['filaFalha'] = DB::table('failed_jobs')->count();
        }

        return Inertia::render('Administracao/Inicio', [
            'nome' => $request->user('administrador')->nome,
            'ambiente' => app()->environment(),
            'saude' => $saude,
            'indicadores' => [
                'barbearias' => Barbearia::query()->count(),
                'barbeariasAtivas' => Barbearia::query()->where('ativa', true)->count(),
                'usuariosDeEquipe' => Usuario::query()->count(),
                'agendamentosFuturos' => Agendamento::query()->whereIn('status', ['aguardando_confirmacao', 'confirmado'])->where('inicio_em', '>=', now('UTC'))->count(),
            ],
            'barbeariasRecentes' => Barbearia::query()->latest()->limit(8)->get(['id', 'nome', 'slug', 'ativa', 'created_at'])
                ->map(fn (Barbearia $barbearia): array => ['id' => $barbearia->id, 'nome' => $barbearia->nome, 'slug' => $barbearia->slug, 'ativa' => $barbearia->ativa, 'criadaEm' => $barbearia->created_at->format('d/m/Y')]),
            'escopo' => 'Visão global somente leitura. Dados operacionais e financeiros de clientes permanecem isolados nos painéis de cada barbearia.',
        ]);
    }

    public function sair(Request $request): RedirectResponse
    {
        app(RegistradorDeAuditoria::class)->registrar('autenticacao.encerrada', 'Sessão administrativa encerrada.', request: $request);
        Auth::guard('administrador')->logout();
        $request->session()->forget(['admin_mfa_verificado', 'admin_mfa_segredo_pendente']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.entrar');
    }
}
