<?php

use App\Enums\PapelDeUsuario;
use App\Models\Agendamento;
use App\Models\Atendimento;
use App\Models\Barbearia;
use App\Models\Cliente;
use App\Models\Despesa;
use App\Models\Pagamento;
use App\Models\Profissional;
use App\Models\Servico;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resume receitas e despesas por serviço e profissional e exporta CSV', function (): void {
    $barbearia = Barbearia::create(['nome' => 'Relatórios', 'slug' => 'relatorios', 'fuso_horario' => 'America/Araguaina', 'ativa' => true]);
    $usuario = Usuario::create(['barbearia_id' => $barbearia->id, 'nome' => 'Dono', 'email' => 'relatorios@teste.local', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Proprietario, 'ativo' => true]);
    $cliente = Cliente::create(['barbearia_id' => $barbearia->id, 'nome' => 'Cliente', 'telefone' => '63999990000']);
    $profissional = Profissional::create(['barbearia_id' => $barbearia->id, 'nome' => 'Barbeiro', 'ativo' => true]);
    $servico = Servico::create(['barbearia_id' => $barbearia->id, 'nome' => 'Corte', 'duracao_em_minutos' => 30, 'preco' => 50, 'ativo' => true]);
    $atendimento = Atendimento::create(['barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'profissional_id' => $profissional->id, 'servico_id' => $servico->id, 'iniciado_em' => '2026-10-26 13:00:00+00:00', 'concluido_em' => '2026-10-26 13:30:00+00:00', 'valor_total' => 50, 'status' => 'concluido']);
    Pagamento::create(['barbearia_id' => $barbearia->id, 'atendimento_id' => $atendimento->id, 'valor' => 50, 'forma_pagamento' => 'pix', 'chave_idempotencia' => 'a0ab0195-93e0-4a58-9b8a-8d554c0f6398', 'registrado_em' => '2026-10-26 13:10:00+00:00']);
    Despesa::create(['barbearia_id' => $barbearia->id, 'descricao' => 'Produtos', 'categoria' => 'Insumos', 'valor' => 15, 'movimentada_em' => '2026-10-26 14:00:00+00:00', 'chave_idempotencia' => 'a0ab0195-93e0-4a58-9b8a-8d554c0f6399']);
    Agendamento::create(['barbearia_id' => $barbearia->id, 'cliente_id' => $cliente->id, 'profissional_id' => $profissional->id, 'servico_id' => $servico->id, 'inicio_em' => '2026-10-26 15:00:00+00:00', 'fim_em' => '2026-10-26 15:30:00+00:00', 'status' => 'nao_compareceu']);

    $this->actingAs($usuario)->get('/relatorios?inicio=2026-10-26&fim=2026-10-26')->assertInertia(fn ($pagina) => $pagina
        ->where('resumo.totalRecebido', '50.00')->where('resumo.totalDespesas', '15.00')->where('resumo.saldo', '35.00')
        ->where('resumo.ticketMedio', '50.00')->where('resumo.faltas', 1)
        ->where('porServico.0.nome', 'Corte')->where('porProfissional.0.nome', 'Barbeiro'));
    $this->get('/relatorios/exportar?inicio=2026-10-26&fim=2026-10-26')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

it('não permite a profissional acessar relatórios ou exportar dados', function (): void {
    $barbearia = Barbearia::create(['nome' => 'Privada', 'slug' => 'privada-relatorios', 'ativa' => true]);
    $usuario = Usuario::create(['barbearia_id' => $barbearia->id, 'nome' => 'Profissional', 'email' => 'pro@privada.local', 'senha' => 'senha-segura', 'papel' => PapelDeUsuario::Profissional, 'ativo' => true]);
    $this->actingAs($usuario)->get('/relatorios')->assertForbidden();
    $this->get('/relatorios/exportar')->assertForbidden();
});
