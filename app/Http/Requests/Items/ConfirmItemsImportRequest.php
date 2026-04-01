<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmItemsImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can('items.create')
            && $user?->can('items.edit');
    }

    public function rules(): array
    {
        return [
            'import_token' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'import_token.required' => 'A pre-visualizacao expirou. Carrega o ficheiro novamente.',
        ];
    }
}
