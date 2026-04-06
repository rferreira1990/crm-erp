<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use App\Models\CustomerAccountEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerAccountEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('customers.edit') ?? false)
            || ($this->user()?->can('customers.create') ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'entry_date' => $this->normalizeString($this->input('entry_date')),
            'type' => $this->normalizeString($this->input('type')),
            'amount' => $this->normalizeDecimal($this->input('amount')),
            'description' => $this->normalizeString($this->input('description')),
            'reference_type' => $this->normalizeString($this->input('reference_type')),
            'reference_id' => $this->normalizeInteger($this->input('reference_id')),
            'due_date' => $this->normalizeString($this->input('due_date')),
            'notes' => $this->normalizeString($this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'entry_date' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(CustomerAccountEntry::types()))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('customers', 'id'),
                Rule::in([$customer?->id]),
            ],
        ];
    }

    private function normalizeString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return filter_var($normalized, FILTER_VALIDATE_INT) === false
            ? null
            : (int) $normalized;
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        return (float) str_replace(',', '.', $normalized);
    }
}

