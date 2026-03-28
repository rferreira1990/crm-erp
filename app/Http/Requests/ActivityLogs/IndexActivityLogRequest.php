<?php

namespace App\Http\Requests\ActivityLogs;

use Illuminate\Foundation\Http\FormRequest;

class IndexActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('activity-logs.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'entity' => $this->normalizeString($this->input('entity')),
            'action' => $this->normalizeString($this->input('action')),
            'search' => $this->normalizeString($this->input('search')),
            'date_from' => $this->normalizeString($this->input('date_from')),
            'date_to' => $this->normalizeString($this->input('date_to')),
            'per_page' => $this->normalizeInteger($this->input('per_page')) ?? 25,
        ]);
    }

    public function rules(): array
    {
        return [
            'entity' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => 'A data inicial é inválida.',
            'date_to.date' => 'A data final é inválida.',
            'date_to.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'per_page.integer' => 'O número de registos por página é inválido.',
            'per_page.min' => 'O número mínimo de registos por página é 10.',
            'per_page.max' => 'O número máximo de registos por página é 100.',
        ];
    }

    public function filters(): array
    {
        return [
            'entity' => $this->input('entity'),
            'action' => $this->input('action'),
            'search' => $this->input('search'),
            'date_from' => $this->input('date_from'),
            'date_to' => $this->input('date_to'),
            'per_page' => (int) ($this->input('per_page') ?? 25),
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

        return $value === '' ? null : (int) $value;
    }
}
