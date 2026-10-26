<?php

use App\Models\AdministradorDaPlataforma;
use App\Models\ContaDeCliente;
use App\Models\Usuario;

return [
    'defaults' => ['guard' => 'web', 'passwords' => 'usuarios'],
    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'usuarios'],
        'cliente' => ['driver' => 'session', 'provider' => 'contas_de_clientes'],
        'administrador' => ['driver' => 'session', 'provider' => 'administradores_da_plataforma'],
    ],
    'providers' => [
        'usuarios' => ['driver' => 'eloquent', 'model' => Usuario::class],
        'contas_de_clientes' => ['driver' => 'eloquent', 'model' => ContaDeCliente::class],
        'administradores_da_plataforma' => ['driver' => 'eloquent', 'model' => AdministradorDaPlataforma::class],
    ],
    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => 10800,
];
