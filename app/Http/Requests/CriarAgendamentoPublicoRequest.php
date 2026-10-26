<?php

namespace App\Http\Requests;

use App\Models\Barbearia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarAgendamentoPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $barbearia = $this->route('barbearia');
        $barbeariaId = $barbearia instanceof Barbearia ? $barbearia->id : null;

        return [
            'servico_id' => ['required', 'integer', Rule::exists('servicos', 'id')->where('barbearia_id', $barbeariaId)->where('ativo', true)],
            'profissional_id' => ['required', 'integer', Rule::exists('profissionais', 'id')->where('barbearia_id', $barbeariaId)->where('ativo', true)],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'nome' => ['required', 'string', 'max:120'],
            'telefone' => ['required', 'string', 'max:20', 'regex:/^[0-9\s()+.-]+$/'],
            'codigo_beneficio' => ['nullable', 'string', 'size:20', 'regex:/^[A-Fa-f0-9]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'aceita_lembretes' => ['sometimes', 'boolean'],
            'chave_idempotencia' => ['required', 'uuid'],
        ];
    }
}
