<?php

use App\Enums\PapelDeUsuario;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\BeneficioDoCliente;
use App\Models\Cliente;
use App\Models\HorarioDeTrabalho;
use App\Models\ItemDaFila;
use App\Models\Profissional;
use App\Models\RegraDeBeneficio;
use App\Models\Servico;
use App\Models\Usuario;
use App\Services\Beneficios\EmissorDeBeneficio;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->barbearia = Barbearia::create([
        'nome' => 'Barbearia de Teste',
        'slug' => 'barbearia-teste',
        'fuso_horario' => 'UTC',
        'ativa' => true,
    ]);

    $this->usuario = Usuario::create([
        'barbearia_id' => $this->barbearia->id,
        'nome' => 'Proprietario de Teste',
        'email' => 'proprietario@teste.local',
        'senha' => 'senha-segura-de-teste',
        'papel' => PapelDeUsuario::Proprietario,
        'ativo' => true,
    ]);

    $this->cliente = Cliente::create([
        'barbearia_id' => $this->barbearia->id,
        'nome' => 'Cliente de Teste',
        'telefone' => '559999900001',
    ]);

    $this->servico = Servico::create([
        'barbearia_id' => $this->barbearia->id,
        'nome' => 'Corte',
        'duracao_em_minutos' => 30,
        'preco' => 50,
        'ativo' => true,
    ]);

    $this->profissional = Profissional::create([
        'barbearia_id' => $this->barbearia->id,
        'nome' => 'Profissional de Teste',
        'ativo' => true,
    ]);
    $this->profissional->servicos()->attach($this->servico->id);

    foreach (range(0, 6) as $diaDaSemana) {
        HorarioDeTrabalho::create([
            'barbearia_id' => $this->barbearia->id,
            'profissional_id' => $this->profissional->id,
            'dia_da_semana' => $diaDaSemana,
            'inicio_as' => '00:00',
            'fim_as' => '23:59',
            'ativo' => true,
        ]);
    }

    $this->actingAs($this->usuario);
});

it('adiciona, chama e conclui o atendimento de um cliente presencial', function (): void {
    $servicoBeneficiado = Servico::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Pezinho', 'duracao_em_minutos' => 10, 'preco' => 15, 'ativo' => true]);
    $this->post('/configuracoes/beneficios', ['servico_originador_id' => $this->servico->id, 'servico_beneficiado_id' => $servicoBeneficiado->id, 'disponivel_apos_dias' => 7, 'expira_apos_dias' => 14, 'ativo' => true])->assertRedirect()->assertSessionHas('sucesso');
    $this->post('/fila', [
        'cliente_id' => $this->cliente->id,
        'servico_id' => $this->servico->id,
        'prioridade' => 0,
    ])->assertRedirect('/fila');

    $item = ItemDaFila::query()->firstOrFail();
    expect($item->status)->toBe('aguardando')
        ->and($item->previsao_atendimento_em)->not->toBeNull();

    $this->post('/fila/chamar-proximo')->assertRedirect();
    $item->refresh();
    expect($item->status)->toBe('chamado')
        ->and($item->profissional_id)->toBe($this->profissional->id);

    $this->post('/fila/'.$item->id.'/iniciar')->assertRedirect();
    $item->refresh();
    expect($item->status)->toBe('em_atendimento')
        ->and($item->atendimento_id)->not->toBeNull();

    $dadosPagamento = [
        'valor' => '25.00',
        'forma_pagamento' => 'dinheiro',
        'chave_idempotencia' => 'd937ea10-8e35-4a49-9a6a-111111111111',
    ];
    $this->post('/atendimentos/'.$item->atendimento_id.'/pagamentos', $dadosPagamento)->assertRedirect();
    $this->post('/atendimentos/'.$item->atendimento_id.'/pagamentos', $dadosPagamento)->assertRedirect();
    $this->post('/atendimentos/'.$item->atendimento_id.'/pagamentos', [
        'valor' => '25.00',
        'forma_pagamento' => 'pix',
        'chave_idempotencia' => '9cc10fb4-3d01-4953-97a0-222222222222',
    ])->assertRedirect();
    $this->from('/fila')->post('/atendimentos/'.$item->atendimento_id.'/pagamentos', [
        'valor' => '0.01',
        'forma_pagamento' => 'pix',
        'chave_idempotencia' => '97aabb76-166d-4d1d-b35c-333333333333',
    ])->assertSessionHasErrors('valor');
    expect($item->atendimento->pagamentos()->count())->toBe(2)
        ->and((float) $item->atendimento->pagamentos()->sum('valor'))->toBe(50.0);

    $this->post('/fila/'.$item->id.'/concluir')->assertRedirect();
    $item->refresh();
    expect($item->status)->toBe('atendido')
        ->and(Atendimento::query()->findOrFail($item->atendimento_id)->status)->toBe('concluido')
        ->and(BeneficioDoCliente::query()->where('atendimento_origem_id', $item->atendimento_id)->count())->toBe(1);
    $beneficioExistente = BeneficioDoCliente::firstOrFail();
    $beneficioReemitido = app(EmissorDeBeneficio::class)->emitirParaAtendimentoConcluido(Atendimento::findOrFail($item->atendimento_id));
    expect($beneficioReemitido->id)->toBe($beneficioExistente->id)->and(BeneficioDoCliente::count())->toBe(1);
    $this->get('/clientes')->assertInertia(fn ($pagina) => $pagina->where('clientes.data.0.beneficios.0.servico', 'Pezinho'));

    $this->get('/financeiro')->assertOk()
        ->assertInertia(fn ($pagina) => $pagina
            ->component('Financeiro/Index')
            ->where('totalRecebido', '50.00')
            ->where('quantidadePagamentos', 2));
});

it('não permite inserir na fila um cliente de outra barbearia', function (): void {
    $outraBarbearia = Barbearia::create([
        'nome' => 'Outra Barbearia',
        'slug' => 'outra-barbearia',
        'fuso_horario' => 'UTC',
        'ativa' => true,
    ]);
    $clienteDeOutraBarbearia = Cliente::create([
        'barbearia_id' => $outraBarbearia->id,
        'nome' => 'Cliente Externo',
        'telefone' => '559999900002',
    ]);

    $this->from('/fila')->post('/fila', [
        'cliente_id' => $clienteDeOutraBarbearia->id,
        'servico_id' => $this->servico->id,
    ])->assertSessionHasErrors('cliente_id');

    expect(ItemDaFila::query()->count())->toBe(0);
});

it('expira somente créditos disponíveis vencidos e preserva outros estados', function (): void {
    $servicoBeneficiado = Servico::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Pezinho', 'duracao_em_minutos' => 10, 'preco' => 15, 'ativo' => true]);
    $regra = RegraDeBeneficio::create(['barbearia_id' => $this->barbearia->id, 'servico_originador_id' => $this->servico->id, 'servico_beneficiado_id' => $servicoBeneficiado->id, 'disponivel_apos_dias' => 0, 'expira_apos_dias' => 14, 'ativo' => true]);
    $beneficios = collect(['disponivel', 'utilizado', 'cancelado'])->map(function (string $status) use ($regra): BeneficioDoCliente {
        $atendimento = Atendimento::create(['barbearia_id' => $this->barbearia->id, 'cliente_id' => $this->cliente->id, 'profissional_id' => $this->profissional->id, 'servico_id' => $this->servico->id, 'iniciado_em' => CarbonImmutable::now('UTC')->subDays(20), 'concluido_em' => CarbonImmutable::now('UTC')->subDays(19), 'valor_total' => 50, 'status' => 'concluido']);

        return BeneficioDoCliente::create(['barbearia_id' => $this->barbearia->id, 'cliente_id' => $this->cliente->id, 'regra_de_beneficio_id' => $regra->id, 'atendimento_origem_id' => $atendimento->id, 'disponivel_em' => CarbonImmutable::now('UTC')->subDays(19), 'expira_em' => CarbonImmutable::now('UTC')->subDay(), 'status' => $status]);
    });

    $this->artisan('beneficios:expirar')->assertSuccessful();

    expect($beneficios[0]->fresh()->status)->toBe('expirado')
        ->and($beneficios[1]->fresh()->status)->toBe('utilizado')
        ->and($beneficios[2]->fresh()->status)->toBe('cancelado');
});
