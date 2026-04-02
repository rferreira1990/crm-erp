<?php

namespace App\Http\Requests\Works;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'checklist_name' => trim((string) $this->input('checklist_name')),
            'checklist_description' => $this->normalizeString($this->input('checklist_description')),
        ]);
    }

    public function rules(): array
    {
        return [
            'checklist_name' => ['required', 'string', 'max:255'],
            'checklist_description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
