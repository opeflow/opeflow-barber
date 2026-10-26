<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CriarBloqueioDeAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->papel->podeGerenciarConfiguracoes() ?? false;
    }

    public function rules(): array
    {
        return [
            'inicio_em' => ['required', 'date_format:Y-m-d\TH:i'],
            'fim_em' => ['required', 'date_format:Y-m-d\TH:i', 'after:inicio_em'],
            'motivo' => ['nullable', 'string', 'max:160'],
        ];
    }
}
