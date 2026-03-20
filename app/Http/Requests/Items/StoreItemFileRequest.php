<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('items.edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Tem de selecionar pelo menos um ficheiro.',
            'files.array' => 'O envio de ficheiros é inválido.',
            'files.min' => 'Tem de enviar pelo menos um ficheiro.',
            'files.max' => 'Pode enviar no máximo 10 ficheiros de cada vez.',
            'files.*.mimes' => 'Só são permitidos ficheiros JPG, JPEG, PNG, WEBP e PDF.',
            'files.*.max' => 'Cada ficheiro pode ter no máximo 10 MB.',
        ];
    }
}
