<?php

namespace App\Http\Controllers;

use App\Models\EventoDeAuditoria;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaAdministrativaController extends Controller
{
    public function exibir(Request $request): Response
    {
        $filtros = $request->validate([
            'nivel' => ['nullable', 'in:info,aviso,erro'],
            'servico' => ['nullable', 'string', 'max:100'],
            'requisicao' => ['nullable', 'string', 'max:100'],
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
        ]);

        $eventos = EventoDeAuditoria::query()
            ->when($filtros['nivel'] ?? null, fn ($consulta, $valor) => $consulta->where('nivel', $valor))
            ->when($filtros['servico'] ?? null, fn ($consulta, $valor) => $consulta->where('servico', $valor))
            ->when($filtros['requisicao'] ?? null, fn ($consulta, $valor) => $consulta->where('identificador_requisicao', $valor))
            ->when($filtros['de'] ?? null, fn ($consulta, $valor) => $consulta->where('created_at', '>=', $valor.' 00:00:00'))
            ->when($filtros['ate'] ?? null, fn ($consulta, $valor) => $consulta->where('created_at', '<', CarbonImmutable::parse($valor)->addDay()->toDateString()))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (EventoDeAuditoria $evento): array => [
                'id' => $evento->id,
                'acao' => $evento->acao,
                'nivel' => $evento->nivel,
                'servico' => $evento->servico,
                'requisicao' => $evento->identificador_requisicao,
                'descricao' => $evento->descricao_redigida,
                'metadados' => $evento->metadados ?? [],
                'criadoEm' => $evento->created_at->format('d/m/Y H:i:s'),
                'administradorId' => $evento->administrador_da_plataforma_id,
            ]);

        return Inertia::render('Administracao/Auditoria', ['eventos' => $eventos, 'filtros' => $filtros]);
    }
}
