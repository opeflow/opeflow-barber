<?php

namespace App\Enums;

enum PapelDeUsuario: string
{
    case Proprietario = 'proprietario';
    case Gerente = 'gerente';
    case Recepcao = 'recepcao';
    case Profissional = 'profissional';

    public function podeGerenciarConfiguracoes(): bool
    {
        return in_array($this, [self::Proprietario, self::Gerente], true);
    }

    public function podeGerenciarEquipe(): bool
    {
        return in_array($this, [self::Proprietario, self::Gerente], true);
    }

    public function podeAcessarFinanceiro(): bool
    {
        return in_array($this, [self::Proprietario, self::Gerente, self::Recepcao], true);
    }

    public function podeGerenciarAtendimento(): bool
    {
        return $this !== self::Profissional;
    }

    public function podeConsultarClientes(): bool
    {
        return $this !== self::Profissional;
    }

    public function podeRegistrarPagamento(): bool
    {
        return true;
    }
}
