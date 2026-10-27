<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalvarIntegracaoDaPlataformaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provedor' => ['required', 'string', Rule::in(['whatsapp_business', 'armazenamento_s3', 'pagamentos'])],
            'rotulo' => ['required', 'string', 'max:120'],
            'credencial' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:12', 'max:2000'],
        ];
    }
}
