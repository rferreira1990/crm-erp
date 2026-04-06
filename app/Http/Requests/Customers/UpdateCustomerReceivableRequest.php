<?php

namespace App\Http\Requests\Customers;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerReceivableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customers.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->normalizeInteger($this->input('customer_id')),
            'issue_date' => $this->normalizeString($this->input('issue_date')),
            'due_date' => $this->normalizeString($this->input('due_date')),
            'amount' => $this->normalizeDecimal($this->input('amount')),
            'description' => $this->normalizeString($this->input('description')),
            'reference_type' => $this->normalizeString($this->input('reference_type')),
            'reference_id' => $this->normalizeInteger($this->input('reference_id')),
            'notes' => $this->normalizeString($this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'reference_type' => ['nullable', 'string', 'max:100'],
            'reference_id' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ownerId = (int) Auth::id();
            $customerId = (int) ($this->input('customer_id') ?? 0);

            if ($customerId <= 0) {
                return;
            }

            $customerIsValid = Customer::query()
                ->whereKey($customerId)
                ->where('owner_id', $ownerId)
                ->exists();

            if (! $customerIsValid) {
                $validator->errors()->add('customer_id', 'Cliente invalido para o utilizador atual.');
            }
        });
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

        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized)
            ? (float) $normalized
            : null;
    }
}
