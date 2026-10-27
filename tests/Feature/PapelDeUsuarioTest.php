<?php

use App\Enums\PapelDeUsuario;

it('aplica a matriz de permissões documentada para cada papel de equipe', function (PapelDeUsuario $papel, array $permissoes): void {
    expect([
        $papel->podeGerenciarConfiguracoes(),
        $papel->podeGerenciarEquipe(),
        $papel->podeAcessarFinanceiro(),
        $papel->podeEstornarPagamento(),
        $papel->podeGerenciarAtendimento(),
        $papel->podeConsultarClientes(),
        $papel->podeRegistrarPagamento(),
    ])->toBe($permissoes);
})->with([
    'proprietário' => [PapelDeUsuario::Proprietario, [true, true, true, true, true, true, true]],
    'gerente' => [PapelDeUsuario::Gerente, [true, true, true, true, true, true, true]],
    'recepção' => [PapelDeUsuario::Recepcao, [false, false, true, false, true, true, true]],
    'profissional' => [PapelDeUsuario::Profissional, [false, false, false, false, false, false, true]],
]);
