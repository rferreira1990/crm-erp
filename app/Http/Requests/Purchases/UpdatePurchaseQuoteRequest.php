<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseQuote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePurchaseQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($row, $index) {
                return [
                    'purchase_request_item_id' => $this->normalizeInteger($row['purchase_request_item_id'] ?? $index),
                    'quoted_qty' => $this->normalizeDecimal($row['quoted_qty'] ?? null),
                    'unit_price' => $this->normalizeDecimal($row['unit_price'] ?? null),
                    'discount_percent' => $this->normalizeDecimal($row['discount_percent'] ?? null),
                    'line_total' => $this->normalizeDecimal($row['line_total'] ?? null),
                    'lead_time_days' => $this->normalizeInteger($row['lead_time_days'] ?? null),
                    'notes' => $this->normalizeString($row['notes'] ?? null),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'supplier_id' => $this->normalizeInteger($this->input('supplier_id')),
            'lead_time_days' => $this->normalizeInteger($this->input('lead_time_days')),
            'payment_term_snapshot' => $this->normalizeString($this->input('payment_term_snapshot')),
            'valid_until' => $this->normalizeString($this->input('valid_until')),
            'total_amount' => $this->normalizeDecimal($this->input('total_amount')),
            'currency' => strtoupper($this->normalizeString($this->input('currency')) ?: 'EUR'),
            'status' => $this->normalizeString($this->input('status')) ?: PurchaseQuote::STATUS_RECEIVED,
            'notes' => $this->normalizeString($this->input('notes')),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'payment_term_snapshot' => ['nullable', 'string', 'max:120'],
            'valid_until' => ['nullable', 'date'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(array_keys(PurchaseQuote::statuses()))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_request_item_id' => ['required', 'integer', Rule::exists('purchase_request_items', 'id')],
            'items.*.quoted_qty' => ['nullable', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.line_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $purchaseRequest = $this->route('purchaseRequest');
            $quote = $this->route('quote');

            if (! $purchaseRequest || ! $quote) {
                return;
            }

            $supplierId = (int) ($this->input('supplier_id') ?? 0);
            if ($supplierId > 0) {
                $exists = PurchaseQuote::query()
                    ->where('purchase_request_id', $purchaseRequest->id)
                    ->where('supplier_id', $supplierId)
                    ->whereKeyNot($quote->id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('supplier_id', 'Ja existe proposta deste fornecedor para este RFQ.');
                }
            }

            $requestItemIds = $purchaseRequest->items()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $requestItemIdMap = array_flip($requestItemIds);

            $hasLineContent = false;

            foreach ((array) $this->input('items', []) as $index => $item) {
                $requestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

                if ($requestItemId <= 0 || ! array_key_exists($requestItemId, $requestItemIdMap)) {
                    $validator->errors()->add("items.$index.purchase_request_item_id", 'Linha invalida para este RFQ.');
                    continue;
                }

                $hasContent = ($item['quoted_qty'] ?? null) !== null
                    || ($item['unit_price'] ?? null) !== null
                    || ($item['discount_percent'] ?? null) !== null
                    || ($item['line_total'] ?? null) !== null
                    || ($item['lead_time_days'] ?? null) !== null
                    || ($item['notes'] ?? null) !== null;

                if ($hasContent) {
                    $hasLineContent = true;
                }
            }

            if (! $hasLineContent) {
                $validator->errors()->add('items', 'Indique pelo menos uma linha cotada.');
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
