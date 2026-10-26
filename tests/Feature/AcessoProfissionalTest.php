<?php

use App\Enums\PapelDeUsuario;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\Cliente;
use App\Models\FilaDeEspera;
use App\Models\ItemDaFila;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->barbearia = Barbearia::create(['nome' => 'Barbearia de Permissões', 'slug' => 'permissoes', 'fuso_horario' => 'UTC', 'ativa' => true]);
    $this->profissional = Profissional::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Profissional A', 'ativo' => true]);
    $this->outroProfissional = Profissional::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Profissional B', 'ativo' => true]);
    $this->usuario = Usuario::create(['barbearia_id' => $this->barbearia->id, 'profissional_id' => $this->profissional->id, 'nome' => 'Profissional A', 'email' => 'pro@permissoes.test', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Profissional, 'ativo' => true]);
    $this->cliente = Cliente::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Cliente da agenda', 'telefone' => '63999991111']);
    $this->clienteAlheio = Cliente::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Outro cliente', 'telefone' => '63999992222']);
    $this->servico = Servico::create(['barbearia_id' => $this->barbearia->id, 'nome' => 'Corte', 'duracao_em_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $this->profissional->servicos()->attach($this->servico->id);
    $this->outroProfissional->servicos()->attach($this->servico->id);
    $this->actingAs($this->usuario);
});

it('exibe ao profissional somente os agendamentos próprios e sem controles de gestão', function (): void {
    $inicio = now('UTC')->addDay()->setTime(10, 0);
    $agendamentoProprio = Agendamento::create(['barbearia_id' => $this->barbearia->id, 'cliente_id' => $this->cliente->id, 'profissional_id' => $this->profissional->id, 'servico_id' => $this->servico->id, 'inicio_em' => $inicio, 'fim_em' => $inicio->copy()->addMinutes(30), 'status' => 'confirmado']);
    Agendamento::create(['barbearia_id' => $this->barbearia->id, 'cliente_id' => $this->clienteAlheio->id, 'profissional_id' => $this->outroProfissional->id, 'servico_id' => $this->servico->id, 'inicio_em' => $inicio->copy()->addHour(), 'fim_em' => $inicio->copy()->addMinutes(90), 'status' => 'confirmado']);

    $this->get('/agenda?data='.$inicio->toDateString())->assertOk()->assertInertia(fn ($pagina) => $pagina
        ->component('Agenda/Index')
        ->where('podeGerenciarAgenda', false)
        ->has('agendamentos', 1)
        ->where('agendamentos.0.cliente', 'Cliente da agenda')
        ->has('clientes', 0));
    $this->post('/agenda', [])->assertForbidden();
    $this->delete('/agenda/'.$agendamentoProprio->id)->assertForbidden();
    $this->get('/clientes')->assertForbidden();
});

it('limita a fila aos atendimentos atribuídos e bloqueia mutações de outros profissionais', function (): void {
    $fila = FilaDeEspera::create(['barbearia_id' => $this->barbearia->id, 'data_operacional' => now('UTC')->toDateString()]);
    $proprio = ItemDaFila::create(['barbearia_id' => $this->barbearia->id, 'fila_de_espera_id' => $fila->id, 'cliente_id' => $this->cliente->id, 'servico_id' => $this->servico->id, 'profissional_id' => $this->profissional->id, 'chegou_em' => now('UTC'), 'posicao' => 1, 'status' => 'chamado']);
    $alheio = ItemDaFila::create(['barbearia_id' => $this->barbearia->id, 'fila_de_espera_id' => $fila->id, 'cliente_id' => $this->clienteAlheio->id, 'servico_id' => $this->servico->id, 'profissional_id' => $this->outroProfissional->id, 'chegou_em' => now('UTC')->addMinute(), 'posicao' => 2, 'status' => 'chamado']);

    $this->get('/fila')->assertOk()->assertInertia(fn ($pagina) => $pagina
        ->component('Fila/Index')
        ->where('modoProfissional', true)
        ->where('podeAdministrarFila', false)
        ->has('itens', 1)
        ->where('itens.0.cliente', 'Cliente da agenda')
        ->has('clientes', 0));
    $this->post('/fila/'.$alheio->id.'/iniciar')->assertNotFound();
    $this->post('/fila/chamar-proximo')->assertForbidden();
    $this->post('/fila', ['cliente_id' => $this->cliente->id, 'servico_id' => $this->servico->id])->assertForbidden();
    $this->post('/fila/'.$proprio->id.'/iniciar')->assertRedirect();
});

it('permite ao profissional registrar pagamento apenas no atendimento próprio em andamento', function (): void {
    $atendimento = Atendimento::create(['barbearia_id' => $this->barbearia->id, 'cliente_id' => $this->cliente->id, 'profissional_id' => $this->profissional->id, 'servico_id' => $this->servico->id, 'iniciado_em' => now('UTC'), 'valor_total' => 50, 'status' => 'em_andamento']);
    $outroAtendimento = Atendimento::create(['barbearia_id' => $this->barbearia->id, 'cliente_id' => $this->clienteAlheio->id, 'profissional_id' => $this->outroProfissional->id, 'servico_id' => $this->servico->id, 'iniciado_em' => now('UTC'), 'valor_total' => 50, 'status' => 'em_andamento']);

    $this->post('/atendimentos/'.$atendimento->id.'/pagamentos', ['valor' => '50.00', 'forma_pagamento' => 'pix', 'chave_idempotencia' => '673899a9-bd26-41f1-a45d-f663fd424509'])->assertRedirect();
    $this->post('/atendimentos/'.$outroAtendimento->id.'/pagamentos', ['valor' => '50.00', 'forma_pagamento' => 'pix', 'chave_idempotencia' => '397d86be-f887-41f1-a45d-f663fd424509'])->assertForbidden();
    $atendimento->update(['status' => 'concluido']);
    $this->post('/atendimentos/'.$atendimento->id.'/pagamentos', ['valor' => '1.00', 'forma_pagamento' => 'pix', 'chave_idempotencia' => 'ae9679b8-4561-4ebf-b2a5-14cba60b7b19'])->assertForbidden();
});
