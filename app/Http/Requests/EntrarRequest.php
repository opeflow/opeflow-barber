<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EntrarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'senha' => ['required', 'string'],
        ];
    }

    public function autenticar(): void
    {
        $credenciais = $this->only('email', 'senha');

        if (! Auth::attempt(['email' => $credenciais['email'], 'password' => $credenciais['senha'], 'ativo' => true], $this->boolean('lembrar'))) {
            throw ValidationException::withMessages([
                'email' => 'As credenciais informadas não conferem.',
            ]);
        }

        $this->session()->regenerate();
    }
}
