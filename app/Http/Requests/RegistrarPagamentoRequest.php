<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'forma_pagamento' => ['required', Rule::in(['dinheiro', 'pix', 'credito', 'debito', 'outro'])],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
