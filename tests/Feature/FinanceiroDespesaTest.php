<?php

use App\Enums\PapelDeUsuario;
use App\Models\Barbearia;
use App\Models\Despesa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prepararFinanceiroDespesa(string $slug, PapelDeUsuario $papel = PapelDeUsuario::Proprietario): array
{
    $barbearia = Barbearia::create(['nome' => 'Barbearia '.$slug, 'slug' => $slug, 'fuso_horario' => 'America/Araguaina', 'ativa' => true]);
    $usuario = Usuario::create([
        'barbearia_id' => $barbearia->id,
        'nome' => 'Usuário',
        'email' => $slug.'@teste.local',
        'senha' => 'senha-segura',
        'papel' => $papel,
        'ativo' => true,
    ]);

    return [$barbearia, $usuario];
}

it('registra despesa idempotente e atualiza o saldo no fuso da barbearia', function (): void {
    [$barbearia, $usuario] = prepararFinanceiroDespesa('financeiro-teste');
    $chave = 'a0ab0195-93e0-4a58-9b8a-8d554c0f6395';
    $dados = ['descricao' => 'Compra de materiais', 'categoria' => 'Insumos', 'valor' => '25.50', 'data' => '2026-10-26', 'hora' => '10:15', 'chave_idempotencia' => $chave];

    $this->actingAs($usuario)->post('/financeiro/despesas', $dados)->assertRedirect()->assertSessionHas('sucesso');
    $this->post('/financeiro/despesas', $dados)->assertRedirect();

    expect(Despesa::query()->where('barbearia_id', $barbearia->id)->count())->toBe(1)
        ->and(Despesa::firstOrFail()->movimentada_em->toIso8601String())->toBe('2026-10-26T13:15:00+00:00');
    $this->get('/financeiro?data=2026-10-26')->assertInertia(fn ($pagina) => $pagina
        ->where('totalDespesas', '25.50')
        ->where('saldoDoDia', '-25.50')
        ->where('quantidadeDespesas', 1));
});

it('isola despesas entre barbearias e impede profissional de registrar saída de caixa', function (): void {
    [$barbeariaA, $proprietario] = prepararFinanceiroDespesa('financeiro-a');
    [$barbeariaB] = prepararFinanceiroDespesa('financeiro-b');
    Despesa::create([
        'barbearia_id' => $barbeariaB->id,
        'descricao' => 'Despesa privada',
        'categoria' => 'Operacional',
        'valor' => 10,
        'movimentada_em' => '2026-10-26 13:00:00+00:00',
        'chave_idempotencia' => 'a0ab0195-93e0-4a58-9b8a-8d554c0f6396',
    ]);

    $this->actingAs($proprietario)->get('/financeiro?data=2026-10-26')->assertInertia(fn ($pagina) => $pagina->where('totalDespesas', '0.00')->where('quantidadeDespesas', 0));
    [, $profissional] = prepararFinanceiroDespesa('financeiro-profissional', PapelDeUsuario::Profissional);
    $this->actingAs($profissional)->post('/financeiro/despesas', [
        'descricao' => 'Teste', 'categoria' => 'Operacional', 'valor' => '1', 'data' => '2026-10-26', 'hora' => '10:00', 'chave_idempotencia' => 'a0ab0195-93e0-4a58-9b8a-8d554c0f6397',
    ])->assertForbidden();
    expect(Despesa::query()->where('barbearia_id', $barbeariaA->id)->count())->toBe(0);
});
