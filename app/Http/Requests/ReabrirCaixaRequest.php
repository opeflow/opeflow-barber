<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReabrirCaixaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->papel->podeGerenciarConfiguracoes() ?? false;
    }

    public function rules(): array
    {
        return ['motivo' => ['required', 'string', 'min:5', 'max:240']];
    }
}
