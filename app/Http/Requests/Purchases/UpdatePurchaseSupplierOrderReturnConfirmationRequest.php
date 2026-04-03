<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseSupplierOrderReturn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseSupplierOrderReturnConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'supplier_confirmation_status' => trim((string) $this->input('supplier_confirmation_status')),
            'confirmation_notes' => $this->normalizeNullableString($this->input('confirmation_notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_confirmation_status' => [
                'required',
                Rule::in(array_keys(PurchaseSupplierOrderReturn::confirmationStatuses())),
            ],
            'confirmation_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

