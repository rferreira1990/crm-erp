<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.view') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->normalizeString($this->input('search')),
            'movement_type' => $this->normalizeString($this->input('movement_type')),
            'direction' => $this->normalizeString($this->input('direction')),
            'date_from' => $this->normalizeString($this->input('date_from')),
            'date_to' => $this->normalizeString($this->input('date_to')),
            'user_id' => $this->normalizeInteger($this->input('user_id')),
            'only_works' => $this->boolean('only_works'),
            'per_page' => $this->normalizeInteger($this->input('per_page')) ?? 25,
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'movement_type' => ['nullable', 'string', 'max:60'],
            'direction' => ['nullable', Rule::in(['in', 'out', 'adjustment'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'only_works' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => $this->input('search'),
            'movement_type' => $this->input('movement_type'),
            'direction' => $this->input('direction'),
            'date_from' => $this->input('date_from'),
            'date_to' => $this->input('date_to'),
            'user_id' => $this->input('user_id'),
            'only_works' => (bool) $this->input('only_works', false),
            'per_page' => (int) $this->input('per_page', 25),
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
