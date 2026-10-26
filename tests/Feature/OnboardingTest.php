<?php

it('exibe o formulário de criação de conta', function () {
    $this->get('/criar-conta')->assertOk();
});

it('protege a agenda e os clientes atrás da autenticação', function () {
    $this->get('/agenda')->assertRedirect('/entrar');
    $this->get('/clientes')->assertRedirect('/entrar');
    $this->get('/fila')->assertRedirect('/entrar');
});
