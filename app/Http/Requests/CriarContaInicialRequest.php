<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CriarContaInicialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_da_barbearia' => ['required', 'string', 'max:120'],
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'senha' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
