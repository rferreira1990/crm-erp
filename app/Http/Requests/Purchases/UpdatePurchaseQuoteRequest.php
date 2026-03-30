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
                    'supplier_item_reference' => $this->normalizeString($row['supplier_item_reference'] ?? null),
                    'quoted_qty' => $this->normalizeDecimal($row['quoted_qty'] ?? null),
                    'unit_price' => $this->normalizeDecimal($row['unit_price'] ?? null),
                    'discount_percent' => $this->normalizeDecimal($row['discount_percent'] ?? null),
                    'notes' => $this->normalizeString($row['notes'] ?? null),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'supplier_id' => $this->normalizeInteger($this->input('supplier_id')),
            'supplier_quote_reference' => $this->normalizeString($this->input('supplier_quote_reference')),
            'payment_term_id' => $this->normalizeInteger($this->input('payment_term_id')),
            'lead_time_days' => $this->normalizeInteger($this->input('lead_time_days')),
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
            'supplier_quote_reference' => ['nullable', 'string', 'max:120'],
            'payment_term_id' => ['nullable', 'integer', Rule::exists('payment_terms', 'id')],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['required', Rule::in(array_keys(PurchaseQuote::statuses()))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'quote_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_request_item_id' => ['required', 'integer', Rule::exists('purchase_request_items', 'id')],
            'items.*.supplier_item_reference' => ['nullable', 'string', 'max:120'],
            'items.*.quoted_qty' => ['nullable', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
            $hasAtLeastOnePricedLine = false;

            foreach ((array) $this->input('items', []) as $index => $item) {
                $requestItemId = (int) ($item['purchase_request_item_id'] ?? 0);

                if ($requestItemId <= 0 || ! array_key_exists($requestItemId, $requestItemIdMap)) {
                    $validator->errors()->add("items.$index.purchase_request_item_id", 'Linha invalida para este RFQ.');
                    continue;
                }

                if (($item['unit_price'] ?? null) !== null) {
                    $hasAtLeastOnePricedLine = true;
                }
            }

            if (! $hasAtLeastOnePricedLine) {
                $validator->errors()->add('items', 'Indique preco unitario sem IVA em pelo menos uma linha.');
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

