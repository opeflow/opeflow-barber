<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarAgendamentoRequest extends FormRequest
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
            'profissional_id' => ['required', Rule::exists('profissionais', 'id')->where('barbearia_id', $barbeariaId)->where('ativo', true)],
            'servico_id' => ['required', Rule::exists('servicos', 'id')->where('barbearia_id', $barbeariaId)->where('ativo', true)],
            'data' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
