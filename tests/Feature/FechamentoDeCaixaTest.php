<?php

use App\Enums\PapelDeUsuario;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\Cliente;
use App\Models\Despesa;
use App\Models\EstornoDePagamento;
use App\Models\FechamentoDeCaixa;
use App\Models\Pagamento;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepararFechamentoDeCaixa(string $slug, PapelDeUsuario $papel = PapelDeUsuario::Proprietario): array
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
        'valor_total' => 200,
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

    return [$barbearia, $usuario, $atendimento, $pagamento];
}

it('fecha o caixa com snapshot conciliado e bloqueia novos lançamentos até reabertura', function (): void {
    [$barbearia, $usuario, $atendimento, $pagamento] = prepararFechamentoDeCaixa('fechamento-snapshot');
    $agora = CarbonImmutable::now('UTC');
    $dataLocal = $agora->setTimezone($barbearia->fuso_horario)->toDateString();
    $horaLocal = $agora->setTimezone($barbearia->fuso_horario)->format('H:i');
    Despesa::create([
        'barbearia_id' => $barbearia->id,
        'registrada_por_usuario_id' => $usuario->id,
        'descricao' => 'Compra de produtos',
        'categoria' => 'Insumos',
        'valor' => 20,
        'movimentada_em' => $agora,
        'chave_idempotencia' => (string) str()->uuid(),
    ]);
    EstornoDePagamento::create([
        'barbearia_id' => $barbearia->id,
        'pagamento_id' => $pagamento->id,
        'registrado_por_usuario_id' => $usuario->id,
        'valor' => 10,
        'motivo' => 'Ajuste de demonstração',
        'chave_idempotencia' => (string) str()->uuid(),
        'estornado_em' => $agora,
    ]);
    $chave = 'b4e02f5e-9e28-4fc3-8af7-0c7254e1bb83';

    $this->actingAs($usuario)->post('/financeiro/fechamentos', [
        'data' => $dataLocal,
        'valor_conferido' => '75.00',
        'chave_idempotencia' => $chave,
    ])->assertRedirect()->assertSessionHas('sucesso');
    $this->post('/financeiro/fechamentos', [
        'data' => $dataLocal,
        'valor_conferido' => '75.00',
        'chave_idempotencia' => $chave,
    ])->assertRedirect();

    $fechamento = FechamentoDeCaixa::query()->firstOrFail();
    expect($fechamento->saldo_esperado)->toBe('70.00')
        ->and($fechamento->diferenca)->toBe('5.00')
        ->and($fechamento->totais_por_forma)->toBe(['pix' => '100.00'])
        ->and(FechamentoDeCaixa::query()->count())->toBe(1);
    $this->get('/financeiro?data='.$dataLocal)->assertInertia(fn ($pagina) => $pagina
        ->where('caixaFechado', true)
        ->where('fechamento.saldoEsperado', '70.00')
        ->where('historicoFechamentos.0.versao', 1));

    $this->post('/financeiro/despesas', [
        'descricao' => 'Despesa após fechamento',
        'categoria' => 'Operacional',
        'valor' => '5.00',
        'data' => $dataLocal,
        'hora' => $horaLocal,
        'chave_idempotencia' => '62ae002c-f0d8-49f8-b60e-a85a21c6e1bd',
    ])->assertSessionHasErrors('data');
    $this->post("/atendimentos/{$atendimento->id}/pagamentos", [
        'valor' => '10.00',
        'forma_pagamento' => 'pix',
        'chave_idempotencia' => '02a4e36b-44bc-47f1-b56e-f4b0084bf428',
    ])->assertSessionHasErrors('data');
    $this->post("/financeiro/pagamentos/{$pagamento->id}/estornos", [
        'valor' => '5.00',
        'motivo' => 'Tentativa durante caixa fechado',
        'chave_idempotencia' => 'e79a7f4a-1d23-41fd-936a-184e0c95f692',
    ])->assertSessionHasErrors('data');

    $this->post("/financeiro/fechamentos/{$fechamento->id}/reabrir", ['motivo' => 'Corrigir lançamento esquecido'])->assertRedirect()->assertSessionHas('sucesso');
    $this->post('/financeiro/despesas', [
        'descricao' => 'Despesa após reabertura',
        'categoria' => 'Operacional',
        'valor' => '5.00',
        'data' => $dataLocal,
        'hora' => $horaLocal,
        'chave_idempotencia' => '763ac750-a498-4c2b-9c9d-c48a220cc580',
    ])->assertRedirect()->assertSessionHas('sucesso');

    $this->post('/financeiro/fechamentos', [
        'data' => $dataLocal,
        'valor_conferido' => '70.00',
        'chave_idempotencia' => 'acb8ee3c-5aee-4847-9f8b-557687ddaa4d',
    ])->assertRedirect();

    expect(FechamentoDeCaixa::query()->where('barbearia_id', $barbearia->id)->orderBy('versao')->pluck('versao')->all())->toBe([1, 2])
        ->and($fechamento->fresh()->reabertura->motivo)->toBe('Corrigir lançamento esquecido');
    $this->get('/financeiro?data='.$dataLocal)->assertInertia(fn ($pagina) => $pagina
        ->where('caixaFechado', true)
        ->has('historicoFechamentos', 2)
        ->where('historicoFechamentos.1.reabertura.motivo', 'Corrigir lançamento esquecido'));
});

it('restringe o fechamento e a reabertura a proprietário e gerente', function (): void {
    [$barbearia, $recepcao] = prepararFechamentoDeCaixa('fechamento-recepcao', PapelDeUsuario::Recepcao);
    $data = CarbonImmutable::now($barbearia->fuso_horario)->toDateString();

    $this->actingAs($recepcao)->post('/financeiro/fechamentos', [
        'data' => $data,
        'valor_conferido' => '0.00',
        'chave_idempotencia' => 'b15d01ad-6585-4416-923a-8a2a24f37396',
    ])->assertForbidden();
    expect(FechamentoDeCaixa::query()->count())->toBe(0);
});

it('não fecha datas futuras', function (): void {
    [$barbearia, $usuario] = prepararFechamentoDeCaixa('fechamento-data-futura');
    $amanha = CarbonImmutable::now($barbearia->fuso_horario)->addDay()->toDateString();

    $this->actingAs($usuario)->post('/financeiro/fechamentos', [
        'data' => $amanha,
        'valor_conferido' => '0.00',
        'chave_idempotencia' => 'e850f5fa-b0bb-46c3-a12a-13f7c3bdad9b',
    ])->assertSessionHasErrors('data');
    expect(FechamentoDeCaixa::query()->count())->toBe(0);
});
