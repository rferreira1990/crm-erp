<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseQuote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'supplier_id' => $this->normalizeInteger($this->input('supplier_id')),
            'lead_time_days' => $this->normalizeInteger($this->input('lead_time_days')),
            'payment_term_snapshot' => $this->normalizeString($this->input('payment_term_snapshot')),
            'valid_until' => $this->normalizeString($this->input('valid_until')),
            'total_amount' => $this->normalizeDecimal($this->input('total_amount')),
            'currency' => strtoupper($this->normalizeString($this->input('currency')) ?: 'EUR'),
            'status' => $this->normalizeString($this->input('status')) ?: PurchaseQuote::STATUS_RECEIVED,
            'notes' => $this->normalizeString($this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'payment_term_snapshot' => ['nullable', 'string', 'max:120'],
            'valid_until' => ['nullable', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(array_keys(PurchaseQuote::statuses()))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $purchaseRequest = $this->route('purchaseRequest');

            if (! $purchaseRequest) {
                return;
            }

            $supplierId = (int) ($this->input('supplier_id') ?? 0);
            if ($supplierId <= 0) {
                return;
            }

            $exists = PurchaseQuote::query()
                ->where('purchase_request_id', $purchaseRequest->id)
                ->where('supplier_id', $supplierId)
                ->exists();

            if ($exists) {
                $validator->errors()->add('supplier_id', 'Ja existe proposta deste fornecedor para este RFQ.');
            }
        });
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

    private function normalizeDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }
}

