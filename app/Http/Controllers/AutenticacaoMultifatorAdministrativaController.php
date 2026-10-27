<?php

namespace App\Http\Controllers;

use App\Models\AdministradorDaPlataforma;
use App\Services\Administracao\AutenticadorTotp;
use App\Services\Administracao\RegistradorDeAuditoria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AutenticacaoMultifatorAdministrativaController extends Controller
{
    public function configurar(Request $request, AutenticadorTotp $totp): Response|RedirectResponse
    {
        $administrador = $request->user('administrador');
        if ($administrador->mfa_confirmado_em) {
            return to_route('admin.mfa.verificar');
        }

        try {
            $segredo = Crypt::decryptString((string) $request->session()->get('admin_mfa_segredo_pendente'));
        } catch (\Throwable) {
            $segredo = $totp->gerarSegredo();
            $request->session()->put('admin_mfa_segredo_pendente', Crypt::encryptString($segredo));
        }

        return Inertia::render('Administracao/ConfigurarMfa', [
            'segredo' => $segredo,
            'emissor' => config('administracao.emissores_totp'),
            'conta' => $administrador->email,
        ]);
    }

    public function confirmarConfiguracao(Request $request, AutenticadorTotp $totp): RedirectResponse
    {
        $dados = $request->validate(['codigo' => ['required', 'digits:6']]);
        try {
            $segredo = Crypt::decryptString((string) $request->session()->get('admin_mfa_segredo_pendente'));
        } catch (\Throwable) {
            $segredo = null;
        }
        if (! is_string($segredo) || ! $totp->validarCodigo($segredo, $dados['codigo'])) {
            throw ValidationException::withMessages(['codigo' => 'Código inválido. Confira o horário do autenticador e tente novamente.']);
        }

        $codigos = $totp->gerarCodigosDeRecuperacao();
        $administrador = $request->user('administrador');
        DB::transaction(function () use ($administrador, $segredo, $codigos, $totp, $request): void {
            $administradorBloqueado = AdministradorDaPlataforma::query()->whereKey($administrador->id)->lockForUpdate()->firstOrFail();
            $administradorBloqueado->update(['segredo_mfa' => $segredo, 'mfa_confirmado_em' => now('UTC')]);
            DB::table('codigos_de_recuperacao_administrativa')->where('administrador_da_plataforma_id', $administrador->id)->delete();
            foreach ($codigos as $codigo) {
                DB::table('codigos_de_recuperacao_administrativa')->insert([
                    'administrador_da_plataforma_id' => $administrador->id,
                    'codigo_hash' => hash('sha256', $totp->normalizarCodigoDeRecuperacao($codigo)),
                    'created_at' => now('UTC'),
                    'updated_at' => now('UTC'),
                ]);
            }
            app(RegistradorDeAuditoria::class)->registrar('mfa.configurado', 'Autenticação multifator configurada.', request: $request);
        });

        $request->session()->forget('admin_mfa_segredo_pendente');
        $request->session()->put('admin_mfa_verificado', true);
        $request->session()->flash('admin_codigos_recuperacao', Crypt::encryptString(json_encode($codigos, JSON_THROW_ON_ERROR)));

        return to_route('admin.mfa.codigos');
    }

    public function codigosDeRecuperacao(Request $request): Response
    {
        try {
            $codigos = json_decode(Crypt::decryptString((string) $request->session()->get('admin_codigos_recuperacao')), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $codigos = [];
        }
        abort_unless(is_array($codigos) && count($codigos) === 10, 404);
        $request->session()->forget('admin_codigos_recuperacao');

        return Inertia::render('Administracao/CodigosDeRecuperacao', ['codigos' => $codigos]);
    }

    public function verificarFormulario(Request $request): Response
    {
        abort_unless($request->user('administrador')->mfa_confirmado_em, 404);

        return Inertia::render('Administracao/VerificarMfa');
    }

    public function verificar(Request $request, AutenticadorTotp $totp): RedirectResponse
    {
        $dados = $request->validate(['codigo' => ['required', 'string', 'min:6', 'max:30']]);
        $administrador = $request->user('administrador');
        $codigo = trim($dados['codigo']);
        $metodo = 'totp';
        $valido = $totp->validarCodigo((string) $administrador->segredo_mfa, $codigo);

        if (! $valido) {
            $hash = hash('sha256', $totp->normalizarCodigoDeRecuperacao($codigo));
            $valido = DB::transaction(function () use ($administrador, $hash, $request): bool {
                $registro = DB::table('codigos_de_recuperacao_administrativa')
                    ->where('administrador_da_plataforma_id', $administrador->id)
                    ->where('codigo_hash', $hash)->whereNull('utilizado_em')->lockForUpdate()->first();
                if (! $registro) {
                    return false;
                }
                DB::table('codigos_de_recuperacao_administrativa')->where('id', $registro->id)
                    ->update(['utilizado_em' => now('UTC'), 'updated_at' => now('UTC')]);
                app(RegistradorDeAuditoria::class)->registrar('mfa.verificado', 'Verificação multifator concluída por código de recuperação.', metadados: ['metodo_mfa' => 'recuperacao'], request: $request);

                return true;
            });
            $metodo = 'recuperacao';
        }

        if (! $valido) {
            app(RegistradorDeAuditoria::class)->registrar('mfa.falha', 'Tentativa de verificação multifator inválida.', 'aviso');
            throw ValidationException::withMessages(['codigo' => 'Código inválido ou já utilizado.']);
        }

        $request->session()->put('admin_mfa_verificado', true);
        if ($metodo === 'totp') {
            app(RegistradorDeAuditoria::class)->registrar('mfa.verificado', 'Verificação multifator concluída.', metadados: ['metodo_mfa' => $metodo]);
        }

        return to_route('admin.inicio');
    }
}
