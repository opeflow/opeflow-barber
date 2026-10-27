<?php

namespace App\Services\Administracao;

use Illuminate\Support\Str;

class AutenticadorTotp
{
    private const ALFABETO_BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function gerarSegredo(): string
    {
        return $this->codificarBase32(random_bytes(20));
    }

    public function gerarCodigo(string $segredo, ?int $instante = null): string
    {
        $contador = intdiv($instante ?? time(), 30);
        $mensagem = pack('N*', 0, $contador);
        $hash = hash_hmac('sha1', $mensagem, $this->decodificarBase32($segredo), true);
        $deslocamento = ord($hash[19]) & 0x0F;
        $numero = unpack('N', substr($hash, $deslocamento, 4))[1] & 0x7FFFFFFF;

        return str_pad((string) ($numero % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public function validarCodigo(string $segredo, string $codigo, ?int $instante = null): bool
    {
        if (! preg_match('/^\d{6}$/', $codigo)) {
            return false;
        }

        $instante ??= time();
        foreach ([-30, 0, 30] as $desvio) {
            if (hash_equals($this->gerarCodigo($segredo, $instante + $desvio), $codigo)) {
                return true;
            }
        }

        return false;
    }

    public function gerarCodigosDeRecuperacao(int $quantidade = 10): array
    {
        return collect(range(1, $quantidade))->map(fn (): string => Str::upper(Str::random(4).'-'.Str::random(4).'-'.Str::random(4)))->all();
    }

    public function normalizarCodigoDeRecuperacao(string $codigo): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $codigo) ?? '');
    }

    private function codificarBase32(string $dados): string
    {
        $bits = '';
        foreach (str_split($dados) as $caractere) {
            $bits .= str_pad(decbin(ord($caractere)), 8, '0', STR_PAD_LEFT);
        }

        $resultado = '';
        foreach (str_split($bits, 5) as $grupo) {
            if (strlen($grupo) === 5) {
                $resultado .= self::ALFABETO_BASE32[bindec($grupo)];
            }
        }

        return $resultado;
    }

    private function decodificarBase32(string $segredo): string
    {
        $bits = '';
        foreach (str_split(strtoupper($segredo)) as $caractere) {
            $valor = strpos(self::ALFABETO_BASE32, $caractere);
            if ($valor === false) {
                throw new \InvalidArgumentException('Segredo TOTP inválido.');
            }
            $bits .= str_pad(decbin($valor), 5, '0', STR_PAD_LEFT);
        }

        $resultado = '';
        foreach (str_split($bits, 8) as $grupo) {
            if (strlen($grupo) === 8) {
                $resultado .= chr(bindec($grupo));
            }
        }

        return $resultado;
    }
}
