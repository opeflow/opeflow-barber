<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarRegraDeBeneficioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->papel->podeGerenciarConfiguracoes() ?? false;
    }

    public function rules(): array
    {
        $barbeariaId = $this->user()->barbearia_id;

        return [
            'servico_originador_id' => ['required', 'integer', Rule::exists('servicos', 'id')->where('barbearia_id', $barbeariaId)],
            'servico_beneficiado_id' => ['required', 'integer', 'different:servico_originador_id', Rule::exists('servicos', 'id')->where('barbearia_id', $barbeariaId)],
            'disponivel_apos_dias' => ['required', 'integer', 'min:0', 'max:180'],
            'expira_apos_dias' => ['required', 'integer', 'min:1', 'max:365', 'gt:disponivel_apos_dias'],
            'ativo' => ['required', 'boolean'],
        ];
    }
}
