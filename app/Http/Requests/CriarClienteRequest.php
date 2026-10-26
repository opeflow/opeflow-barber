<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:120'],
            'telefone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'telefone')->where('barbearia_id', $this->user()->barbearia_id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'aceita_lembretes' => ['sometimes', 'boolean'],
        ];
    }
}
