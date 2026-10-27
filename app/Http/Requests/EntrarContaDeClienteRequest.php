<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EntrarContaDeClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email'], 'senha' => ['required', 'string'], 'lembrar' => ['sometimes', 'boolean']];
    }

    public function autenticar(): void
    {
        if (! Auth::guard('cliente')->attempt(['email' => $this->string('email')->toString(), 'password' => $this->input('senha'), 'ativo' => true], $this->boolean('lembrar'))) {
            throw ValidationException::withMessages(['email' => 'As credenciais informadas não conferem.']);
        }

        if (! Auth::guard('cliente')->user()->hasVerifiedEmail()) {
            Auth::guard('cliente')->logout();
            throw ValidationException::withMessages(['email' => 'Confirme seu e-mail antes de entrar no portal.']);
        }

        $this->session()->regenerate();
    }
}
