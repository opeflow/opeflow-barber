<?php

namespace App\Http\Requests;

use App\Services\Administracao\RegistradorDeAuditoria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EntrarAdministradorDaPlataformaRequest extends FormRequest
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
        if (! Auth::guard('administrador')->attempt(['email' => $this->string('email')->toString(), 'password' => $this->input('senha'), 'ativo' => true], $this->boolean('lembrar'))) {
            app(RegistradorDeAuditoria::class)->registrar('autenticacao.falha', 'Tentativa de autenticação administrativa inválida.', 'aviso', $this);
            throw ValidationException::withMessages(['email' => 'As credenciais informadas não conferem.']);
        }

        $this->session()->regenerate();
    }
}
