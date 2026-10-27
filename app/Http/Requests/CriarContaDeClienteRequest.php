<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CriarContaDeClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'barbearia' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'senha' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }
}
