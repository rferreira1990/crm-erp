<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseSupplierOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePurchaseSupplierOrderReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $rawQuantities = $this->input('quantities', []);
        $quantities = is_array($rawQuantities) ? $rawQuantities : [];

        $normalized = [];

        foreach ($quantities as $itemId => $value) {
            $lineId = (int) $itemId;
            if ($lineId <= 0) {
                continue;
            }

            $number = $this->normalizeDecimal($value);
            $normalized[$lineId] = $number;
        }

        $this->merge([
            'receipt_date' => trim((string) $this->input('receipt_date')),
            'notes' => $this->normalizeNullableString($this->input('notes')),
            'quantities' => $normalized,
        ]);
    }

    public function rules(): array
    {
        return [
            'receipt_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var PurchaseSupplierOrder|null $order */
            $order = $this->route('order');

            if (! $order) {
                return;
            }

            $order->loadMissing('items');
            $itemsById = $order->items->keyBy('id');
            $quantities = $this->input('quantities', []);

            $hasPositiveQuantity = false;

            foreach ($quantities as $lineId => $quantityValue) {
                $lineId = (int) $lineId;
                $quantity = round((float) $quantityValue, 3);

                if ($quantity <= 0) {
                    continue;
                }

                $hasPositiveQuantity = true;

                $line = $itemsById->get($lineId);
                if (! $line) {
                    $validator->errors()->add('quantities.' . $lineId, 'Linha de encomenda invalida.');
                    continue;
                }

                $pendingQty = $line->pendingQty();
                if ($quantity - $pendingQty > 0.0005) {
                    $validator->errors()->add(
                        'quantities.' . $lineId,
                        'A quantidade a receber excede o pendente da linha.'
                    );
                }
            }

            if (! $hasPositiveQuantity) {
                $validator->errors()->add('quantities', 'Indica pelo menos uma linha com quantidade a receber.');
            }
        });
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 3);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
