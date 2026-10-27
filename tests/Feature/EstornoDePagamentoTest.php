<?php

use App\Enums\PapelDeUsuario;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\Cliente;
use App\Models\EstornoDePagamento;
use App\Models\Pagamento;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepararPagamentoParaEstorno(string $slug, PapelDeUsuario $papel = PapelDeUsuario::Proprietario): array
{
    $barbearia = Barbearia::create(['nome' => 'Barbearia '.$slug, 'slug' => $slug, 'fuso_horario' => 'America/Araguaina', 'ativa' => true]);
    $usuario = Usuario::create([
        'barbearia_id' => $barbearia->id,
        'nome' => 'Responsável',
        'email' => $slug.'@teste.local',
        'senha' => 'senha-segura',
        'papel' => $papel,
        'ativo' => true,
    ]);
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Cliente', 'telefone' => '63999990000']);
    $profissional = Profissional::create(['barbearia_id' => $barbearia->id, 'nome' => 'Barbeiro', 'ativo' => true]);
    $servico = Servico::create(['barbearia_id' => $barbearia->id, 'nome' => 'Corte', 'duracao_em_minutos' => 30, 'preco' => 100, 'ativo' => true]);
    $atendimento = Atendimento::create([
        'barbearia_id' => $barbearia->id,
        'cliente_id' => $cliente->id,
        'profissional_id' => $profissional->id,
        'servico_id' => $servico->id,
        'iniciado_em' => now('UTC')->subHour(),
        'concluido_em' => now('UTC'),
        'valor_total' => 100,
        'status' => 'concluido',
    ]);
    $pagamento = Pagamento::create([
        'barbearia_id' => $barbearia->id,
        'atendimento_id' => $atendimento->id,
        'valor' => 100,
        'forma_pagamento' => 'pix',
        'chave_idempotencia' => (string) str()->uuid(),
        'registrado_em' => now('UTC'),
    ]);

    return [$barbearia, $usuario, $pagamento];
}

it('registra estorno parcial idempotente, mantém o lançamento original e calcula o líquido', function (): void {
    [$barbearia, $usuario, $pagamento] = prepararPagamentoParaEstorno('estorno-parcial');
    $chave = '88d52fc2-c333-4570-87f6-2da80a523f4f';
    $dados = ['valor' => '25.00', 'motivo' => 'Serviço não realizado integralmente', 'chave_idempotencia' => $chave];

    $this->actingAs($usuario)->post("/financeiro/pagamentos/{$pagamento->id}/estornos", $dados)->assertRedirect()->assertSessionHas('sucesso');
    $this->post("/financeiro/pagamentos/{$pagamento->id}/estornos", $dados)->assertRedirect();

    expect(EstornoDePagamento::query()->where('pagamento_id', $pagamento->id)->count())->toBe(1)
        ->and($pagamento->fresh()->valor)->toBe('100.00');

    $data = CarbonImmutable::now($barbearia->fuso_horario)->toDateString();
    $this->get('/financeiro?data='.$data)->assertInertia(fn ($pagina) => $pagina
        ->where('totalRecebido', '100.00')
        ->where('totalEstornado', '25.00')
        ->where('saldoDoDia', '75.00')
        ->where('pagamentos.data.0.saldoEstornavel', '75.00')
        ->where('estornos.total', 1));
    $this->get('/relatorios?inicio='.$data.'&fim='.$data)->assertInertia(fn ($pagina) => $pagina
        ->where('resumo.totalRecebido', '75.00')
        ->where('resumo.totalEstornado', '25.00')
        ->where('resumo.saldo', '75.00')
        ->where('porServico.0.total', '75.00'));
});

it('impede estorno acima do valor disponível e restringe a ação ao proprietário ou gerente', function (): void {
    [, $proprietario, $pagamento] = prepararPagamentoParaEstorno('estorno-limite');
    $this->actingAs($proprietario)->post("/financeiro/pagamentos/{$pagamento->id}/estornos", [
        'valor' => '100.01',
        'motivo' => 'Valor excede o pagamento original',
        'chave_idempotencia' => '3d40b8f8-a35b-48ef-98fc-72f227c1a47b',
    ])->assertSessionHasErrors('valor');
    expect(EstornoDePagamento::query()->count())->toBe(0);

    [, $recepcao] = prepararPagamentoParaEstorno('estorno-recepcao', PapelDeUsuario::Recepcao);
    $this->actingAs($recepcao)->post("/financeiro/pagamentos/{$pagamento->id}/estornos", [
        'valor' => '10.00',
        'motivo' => 'Estorno não autorizado',
        'chave_idempotencia' => '246a5a2b-1380-4f10-b687-fb0858cd9b1c',
    ])->assertForbidden();
});

it('isola estornos por barbearia', function (): void {
    [, $usuarioA, $pagamentoA] = prepararPagamentoParaEstorno('estorno-a');
    [, , $pagamentoB] = prepararPagamentoParaEstorno('estorno-b');

    $this->actingAs($usuarioA)->post("/financeiro/pagamentos/{$pagamentoB->id}/estornos", [
        'valor' => '10.00',
        'motivo' => 'Tentativa entre barbearias',
        'chave_idempotencia' => 'f2f341c8-4b17-4f8c-b5aa-03fdf38f23ef',
    ])->assertNotFound();

    expect(EstornoDePagamento::query()->where('pagamento_id', $pagamentoA->id)->count())->toBe(0)
        ->and(EstornoDePagamento::query()->where('pagamento_id', $pagamentoB->id)->count())->toBe(0);
});
