<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CriarPausaDeTrabalhoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->papel->podeGerenciarConfiguracoes() ?? false;
    }

    public function rules(): array
    {
        return [
            'inicio_as' => ['required', 'date_format:H:i'],
            'fim_as' => ['required', 'date_format:H:i', 'after:inicio_as'],
        ];
    }
}
