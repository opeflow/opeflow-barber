<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDespesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->papel->podeAcessarFinanceiro() ?? false;
    }

    public function rules(): array
    {
        return [
            'descricao' => ['required', 'string', 'max:160'],
            'categoria' => ['required', 'string', 'max:60'],
            'valor' => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
