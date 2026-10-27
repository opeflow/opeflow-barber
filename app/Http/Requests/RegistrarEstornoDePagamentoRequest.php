<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarEstornoDePagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->papel->podeEstornarPagamento() ?? false;
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0', 'max:9999999.99', 'decimal:0,2'],
            'motivo' => ['required', 'string', 'min:3', 'max:240'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
