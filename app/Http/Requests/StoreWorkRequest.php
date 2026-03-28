<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalize($this->input('name')),
            'work_type' => $this->normalize($this->input('work_type')),
            'location' => $this->normalize($this->input('location')),
            'postal_code' => $this->normalize($this->input('postal_code')),
            'city' => $this->normalize($this->input('city')),
            'description' => $this->normalize($this->input('description')),
            'internal_notes' => $this->normalize($this->input('internal_notes')),
            'budget_id' => $this->normalizeInteger($this->input('budget_id')),
            'technical_manager_id' => $this->normalizeInteger($this->input('technical_manager_id')),
            'team' => array_values(array_filter((array) $this->input('team', []), fn ($value) => trim((string) $value) !== '')),
        ]);
    }

    public function rules(): array
    {
        $ownerId = $this->user()?->id;

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->where('owner_id', $ownerId)),
            ],
            'budget_id' => [
                'nullable',
                'integer',
                Rule::exists('budgets', 'id')->where(fn ($q) => $q->where('owner_id', $ownerId)),
            ],
            'technical_manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'work_type' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'start_date_planned' => ['nullable', 'date'],
            'end_date_planned' => ['nullable', 'date', 'after_or_equal:start_date_planned'],
            'start_date_actual' => ['nullable', 'date'],
            'end_date_actual' => ['nullable', 'date', 'after_or_equal:start_date_actual'],
            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'team' => ['nullable', 'array'],
            'team.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ];
    }

    private function normalize(mixed $value): ?string
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
