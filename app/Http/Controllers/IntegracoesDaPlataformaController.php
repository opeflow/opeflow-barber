<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalvarIntegracaoDaPlataformaRequest;
use App\Models\IntegracaoDaPlataforma;
use App\Services\Administracao\RegistradorDeAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class IntegracoesDaPlataformaController extends Controller
{
    public function exibir(): Response
    {
        $integracoes = IntegracaoDaPlataforma::query()->latest()->get()->map(fn (IntegracaoDaPlataforma $integracao): array => [
            'id' => $integracao->id,
            'provedor' => $integracao->provedor,
            'rotulo' => $integracao->rotulo,
            'ativa' => $integracao->ativa,
            'credencialMascarada' => $integracao->credencialMascarada(),
            'atualizadaEm' => $integracao->updated_at?->format('d/m/Y H:i'),
        ]);

        return Inertia::render('Administracao/Integracoes', ['integracoes' => $integracoes]);
    }

    public function criar(SalvarIntegracaoDaPlataformaRequest $request): RedirectResponse
    {
        $dados = $request->validated();
        $integracao = null;
        DB::transaction(function () use ($dados, $request, &$integracao): void {
            $integracao = IntegracaoDaPlataforma::query()->create([
                'provedor' => $dados['provedor'],
                'rotulo' => $dados['rotulo'],
                'credencial_cifrada' => $dados['credencial'],
                'ativa' => true,
                'atualizada_por_administrador_id' => $request->user('administrador')->id,
            ]);
            app(RegistradorDeAuditoria::class)->registrar('integracao.criada', 'Credencial de integração cadastrada.', metadados: ['integracao_id' => $integracao->id, 'provedor' => $integracao->provedor]);
        });

        return to_route('admin.integracoes')->with('sucesso', 'Credencial armazenada cifrada. Ela não será exibida novamente.');
    }

    public function atualizar(SalvarIntegracaoDaPlataformaRequest $request, IntegracaoDaPlataforma $integracao): RedirectResponse
    {
        $dados = $request->validated();
        DB::transaction(function () use ($dados, $request, $integracao): void {
            $integracao->update([
                'provedor' => $dados['provedor'],
                'rotulo' => $dados['rotulo'],
                'credencial_cifrada' => $dados['credencial'] ?? $integracao->credencial_cifrada,
                'atualizada_por_administrador_id' => $request->user('administrador')->id,
            ]);
            app(RegistradorDeAuditoria::class)->registrar('integracao.atualizada', 'Configuração ou credencial de integração atualizada.', metadados: ['integracao_id' => $integracao->id, 'provedor' => $integracao->provedor]);
        });

        return to_route('admin.integracoes')->with('sucesso', empty($dados['credencial']) ? 'Integração atualizada.' : 'Credencial rotacionada e armazenada cifrada.');
    }

    public function revogar(IntegracaoDaPlataforma $integracao): RedirectResponse
    {
        DB::transaction(function () use ($integracao): void {
            $integracao->update(['ativa' => false, 'atualizada_por_administrador_id' => request()->user('administrador')->id]);
            app(RegistradorDeAuditoria::class)->registrar('integracao.revogada', 'Integração desativada; a credencial cifrada foi mantida para auditoria e possível reativação.', metadados: ['integracao_id' => $integracao->id, 'provedor' => $integracao->provedor, 'estado' => 'inativa']);
        });

        return to_route('admin.integracoes')->with('sucesso', 'Integração desativada. A credencial permanece cifrada e mascarada.');
    }
}
