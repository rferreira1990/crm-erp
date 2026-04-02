<?php

namespace App\Http\Requests\Works;

use App\Models\Work;
use App\Models\WorkDailyReport;
use App\Models\WorkFile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'category' => $this->normalizeString($this->input('category')) ?: WorkFile::CATEGORY_DOCUMENT,
            'work_daily_report_id' => $this->normalizeInteger($this->input('work_daily_report_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:' . max((int) config('work_files.max_kb', 10240), 1)],
            'category' => ['required', Rule::in(array_keys(WorkFile::categories()))],
            'work_daily_report_id' => ['nullable', 'integer', Rule::exists('work_daily_reports', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Work|null $work */
            $work = $this->route('work');
            $uploadedFile = $this->file('file');

            if (! $uploadedFile || ! $uploadedFile->isValid()) {
                $validator->errors()->add('file', 'Ficheiro invalido.');
                return;
            }

            $realMimeType = mime_content_type($uploadedFile->getPathname()) ?: 'application/octet-stream';
            $allowedMimes = config('work_files.allowed_mimes', []);

            if (! in_array($realMimeType, $allowedMimes, true)) {
                $validator->errors()->add('file', 'Tipo de ficheiro nao suportado.');
            }

            $extension = strtolower((string) $uploadedFile->getClientOriginalExtension());
            $blockedExtensions = config('work_files.blocked_extensions', []);

            if ($extension !== '' && in_array($extension, $blockedExtensions, true)) {
                $validator->errors()->add('file', 'Extensao de ficheiro bloqueada por seguranca.');
            }

            $reportId = $this->input('work_daily_report_id');
            if ($reportId !== null && $work) {
                $exists = WorkDailyReport::query()
                    ->where('id', (int) $reportId)
                    ->where('work_id', $work->id)
                    ->where('owner_id', $work->owner_id)
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add(
                        'work_daily_report_id',
                        'O registo diario selecionado nao pertence a esta obra.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Seleciona um ficheiro.',
            'file.file' => 'O ficheiro enviado e invalido.',
            'file.max' => 'O ficheiro excede o tamanho maximo permitido.',
            'category.required' => 'Indica a categoria.',
            'category.in' => 'Categoria de ficheiro invalida.',
            'work_daily_report_id.integer' => 'Registo diario invalido.',
            'work_daily_report_id.exists' => 'Registo diario inexistente.',
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) === false
            ? null
            : (int) $value;
    }
}

