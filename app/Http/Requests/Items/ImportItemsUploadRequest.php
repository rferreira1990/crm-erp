<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

class ImportItemsUploadRequest extends FormRequest
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
            'import_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleciona um ficheiro CSV para importar.',
            'import_file.mimes' => 'O ficheiro deve estar em formato CSV.',
            'import_file.max' => 'O ficheiro excede o limite de 10MB.',
        ];
    }
}
