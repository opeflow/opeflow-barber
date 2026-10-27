<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RedefinirSenhaDeClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'senha' => ['required', 'string', 'min:12', 'confirmed'],
        ];
    }
}
