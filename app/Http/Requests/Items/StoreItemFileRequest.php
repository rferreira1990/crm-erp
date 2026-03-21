<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
                'max:10240', // 10MB por ficheiro
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {

            $files = $this->file('files', []);

            foreach ($files as $index => $file) {

                if (! $file->isValid()) {
                    $validator->errors()->add("files.$index", 'Ficheiro inválido.');
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | MIME REAL (não confiar no browser)
                |--------------------------------------------------------------------------
                */
                $realMime = mime_content_type($file->getPathname());

                $allowedMimes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'application/pdf',
                ];

                if (! in_array($realMime, $allowedMimes, true)) {
                    $validator->errors()->add(
                        "files.$index",
                        'Tipo de ficheiro inválido. Só são permitidos JPG, PNG, WEBP e PDF.'
                    );
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validação extra para imagens
                |--------------------------------------------------------------------------
                */
                if (str_starts_with($realMime, 'image/')) {

                    $imageInfo = @getimagesize($file->getPathname());

                    if ($imageInfo === false) {
                        $validator->errors()->add(
                            "files.$index",
                            'Imagem inválida ou corrompida.'
                        );
                        continue;
                    }

                    [$width, $height] = $imageInfo;

                    /*
                    |--------------------------------------------------------------------------
                    | Limite de dimensões (anti abuso)
                    |--------------------------------------------------------------------------
                    */
                    if ($width > 5000 || $height > 5000) {
                        $validator->errors()->add(
                            "files.$index",
                            'Imagem demasiado grande (máx: 5000x5000).'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Tem de selecionar pelo menos um ficheiro.',
            'files.array' => 'O envio de ficheiros é inválido.',
            'files.min' => 'Tem de enviar pelo menos um ficheiro.',
            'files.max' => 'Pode enviar no máximo 10 ficheiros de cada vez.',
            'files.*.max' => 'Cada ficheiro pode ter no máximo 10 MB.',
        ];
    }
}
