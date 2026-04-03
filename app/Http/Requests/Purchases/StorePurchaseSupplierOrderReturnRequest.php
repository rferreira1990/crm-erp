<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseSupplierOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePurchaseSupplierOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $rawQuantities = $this->input('quantities', []);
        $quantities = is_array($rawQuantities) ? $rawQuantities : [];

        $normalizedQuantities = [];
        foreach ($quantities as $itemId => $value) {
            $lineId = (int) $itemId;
            if ($lineId <= 0) {
                continue;
            }

            $normalizedQuantities[$lineId] = $this->normalizeDecimal($value);
        }

        $rawReasons = $this->input('reasons', []);
        $reasons = is_array($rawReasons) ? $rawReasons : [];
        $normalizedReasons = [];
        foreach ($reasons as $itemId => $reason) {
            $lineId = (int) $itemId;
            if ($lineId <= 0) {
                continue;
            }

            $normalizedReasons[$lineId] = $this->normalizeNullableString($reason);
        }

        $receiptId = $this->input('purchase_supplier_order_receipt_id');
        $receiptId = is_numeric($receiptId) ? (int) $receiptId : null;

        $this->merge([
            'return_date' => trim((string) $this->input('return_date')),
            'purchase_supplier_order_receipt_id' => $receiptId > 0 ? $receiptId : null,
            'notes' => $this->normalizeNullableString($this->input('notes')),
            'quantities' => $normalizedQuantities,
            'reasons' => $normalizedReasons,
        ]);
    }

    public function rules(): array
    {
        return [
            'return_date' => ['required', 'date'],
            'purchase_supplier_order_receipt_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
            'reasons' => ['nullable', 'array'],
            'reasons.*' => ['nullable', 'string', 'max:255'],
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

            $order->loadMissing([
                'items',
                'receipts:id,purchase_supplier_order_id',
            ]);

            $itemsById = $order->items->keyBy('id');

            $receiptId = (int) ($this->input('purchase_supplier_order_receipt_id') ?? 0);
            if ($receiptId > 0 && ! $order->receipts->contains(fn ($receipt) => (int) $receipt->id === $receiptId)) {
                $validator->errors()->add(
                    'purchase_supplier_order_receipt_id',
                    'A rececao selecionada nao pertence a esta encomenda.'
                );
            }

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

                $returnableQty = $line->returnableQty();
                if ($quantity - $returnableQty > 0.0005) {
                    $validator->errors()->add(
                        'quantities.' . $lineId,
                        'A quantidade a devolver excede o disponivel para devolucao.'
                    );
                }
            }

            if (! $hasPositiveQuantity) {
                $validator->errors()->add('quantities', 'Indica pelo menos uma linha com quantidade a devolver.');
            }
        });
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || ! is_numeric($normalized)) {
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

