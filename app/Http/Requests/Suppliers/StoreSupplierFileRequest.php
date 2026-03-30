<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSupplierFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', 'file', 'max:20480'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $files = $this->file('files', []);

            $allowedMimes = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
            ];

            foreach ($files as $index => $file) {
                if (! $file->isValid()) {
                    $validator->errors()->add("files.$index", 'Ficheiro invalido.');
                    continue;
                }

                $realMime = mime_content_type($file->getPathname());

                if (! in_array($realMime, $allowedMimes, true)) {
                    $validator->errors()->add(
                        "files.$index",
                        'Tipo de ficheiro nao suportado para anexos de fornecedor.'
                    );
                }
            }
        });
    }
}

