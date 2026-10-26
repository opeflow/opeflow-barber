<?php

namespace App\Http\Requests;

use App\Enums\PapelDeUsuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarConviteDeEquipeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->papel->podeGerenciarEquipe() ?? false;
    }

    public function rules(): array
    {
        $papeisPermitidos = [PapelDeUsuario::Gerente->value, PapelDeUsuario::Recepcao->value, PapelDeUsuario::Profissional->value];

        return [
            'nome' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'papel' => ['required', Rule::in($papeisPermitidos)],
            'profissional_id' => ['required_if:papel,profissional', 'nullable', 'integer', Rule::exists('profissionais', 'id')->where('barbearia_id', $this->user()->barbearia_id)],
        ];
    }
}
