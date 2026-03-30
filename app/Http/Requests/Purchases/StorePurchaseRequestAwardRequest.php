<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseRequestAward;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestAwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.award') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => trim((string) $this->input('mode')),
            'forced_supplier_id' => $this->normalizeInteger($this->input('forced_supplier_id')),
            'justification' => $this->normalizeString($this->input('justification')),
            'allow_partial' => $this->boolean('allow_partial'),
            'replace_existing' => $this->boolean('replace_existing'),
        ]);
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(array_keys(PurchaseRequestAward::modes()))],
            'forced_supplier_id' => [
                Rule::requiredIf(fn () => $this->input('mode') === PurchaseRequestAward::MODE_FORCED_SUPPLIER),
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id'),
            ],
            'justification' => [
                Rule::requiredIf(fn () => $this->input('mode') === PurchaseRequestAward::MODE_FORCED_SUPPLIER),
                'nullable',
                'string',
                'max:5000',
            ],
            'allow_partial' => ['nullable', 'boolean'],
            'replace_existing' => ['nullable', 'boolean'],
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

