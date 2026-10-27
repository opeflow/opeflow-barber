<?php

namespace App\Services\Administracao;

use App\Models\EventoDeAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistradorDeAuditoria
{
    public function registrar(string $acao, string $descricao, string $nivel = 'info', ?Request $request = null, array $metadados = []): EventoDeAuditoria
    {
        $request ??= request();
        $administrador = $request->user('administrador');

        $identificador = $request->attributes->get('request_id');
        if (! is_string($identificador)) {
            $identificador = (string) Str::uuid();
            $request->attributes->set('request_id', $identificador);
        }

        return EventoDeAuditoria::query()->create([
            'administrador_da_plataforma_id' => $administrador?->id,
            'acao' => $acao,
            'nivel' => $nivel,
            'servico' => 'painel_administrativo',
            'identificador_requisicao' => $identificador,
            'descricao_redigida' => $this->redigir($descricao),
            'metadados' => $this->filtrarMetadados($metadados),
        ]);
    }

    private function filtrarMetadados(array $metadados): array
    {
        $permitidos = ['integracao_id', 'provedor', 'estado', 'resultado', 'metodo_mfa'];

        return collect($metadados)->only($permitidos)->map(fn ($valor): string|int|bool|null => is_string($valor)
            ? $this->redigir($valor)
            : (is_int($valor) || is_bool($valor) || $valor === null ? $valor : '[redigido]'))->all();
    }

    private function redigir(string $descricao): string
    {
        $descricao = strip_tags($descricao);
        $descricao = preg_replace('/\bBearer\s+[A-Za-z0-9._~+\/=\-]+/i', 'Bearer [redigido]', $descricao) ?? '';
        $descricao = preg_replace('/\b(token|segredo|senha|password|api[_-]?key)\s*[:=]\s*\S+/i', '$1=[redigido]', $descricao) ?? '';
        $descricao = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[e-mail redigido]', $descricao) ?? '';

        return mb_substr($descricao, 0, 500);
    }
}
