<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdicionarNaFilaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->papel->podeGerenciarAtendimento();
    }

    public function rules(): array
    {
        $barbeariaId = $this->user()->barbearia_id;

        return [
            'cliente_id' => ['required', Rule::exists('clientes', 'id')->where('barbearia_id', $barbeariaId)],
            'servico_id' => ['required', Rule::exists('servicos', 'id')->where('barbearia_id', $barbeariaId)->where('ativo', true)],
            'profissional_preferido_id' => ['nullable', Rule::exists('profissionais', 'id')->where('barbearia_id', $barbeariaId)->where('ativo', true)],
            'prioridade' => ['nullable', 'integer', 'between:0,1'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
