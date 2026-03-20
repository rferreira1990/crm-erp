<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'saft_code' => ['required', 'string', 'max:10'],
            'country_code' => ['required', 'string', 'size:2'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'saft_code' => strtoupper(trim((string) $this->saft_code)),
            'country_code' => strtoupper(trim((string) $this->country_code)),
            'sort_order' => $this->filled('sort_order') ? (int) $this->sort_order : 0,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
