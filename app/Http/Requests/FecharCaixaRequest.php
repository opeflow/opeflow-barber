<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FecharCaixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->papel->podeGerenciarConfiguracoes() ?? false;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'date_format:Y-m-d'],
            'valor_conferido' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
