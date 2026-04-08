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
        $manualLines = collect($this->input('manual_lines', []))
            ->map(function (mixed $line): array {
                $row = is_array($line) ? $line : [];

                return [
                    'purchase_request_item_id' => $this->normalizeInteger($row['purchase_request_item_id'] ?? null),
                    'supplier_id' => $this->normalizeInteger($row['supplier_id'] ?? null),
                    'awarded_qty' => $this->normalizeDecimal($row['awarded_qty'] ?? null),
                ];
            })
            ->values()
            ->all();

        $this->merge([
            'mode' => trim((string) $this->input('mode')),
            'forced_supplier_id' => $this->normalizeInteger($this->input('forced_supplier_id')),
            'justification' => $this->normalizeString($this->input('justification')),
            'allow_partial' => $this->boolean('allow_partial'),
            'replace_existing' => $this->boolean('replace_existing'),
            'manual_lines' => $manualLines,
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
            'manual_lines' => [
                Rule::requiredIf(fn () => $this->input('mode') === PurchaseRequestAward::MODE_MANUAL_PARTIAL),
                'array',
            ],
            'manual_lines.*.purchase_request_item_id' => ['nullable', 'integer'],
            'manual_lines.*.supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'manual_lines.*.awarded_qty' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('mode') !== PurchaseRequestAward::MODE_MANUAL_PARTIAL) {
                return;
            }

            $purchaseRequest = $this->route('purchaseRequest');
            if (! $purchaseRequest) {
                return;
            }

            $purchaseRequest->loadMissing([
                'items:id,purchase_request_id,qty',
                'quotes:id,purchase_request_id,supplier_id,status',
                'quotes.items:id,purchase_quote_id,purchase_request_item_id,quoted_qty,unit_price',
            ]);

            $eligibleQuotes = $purchaseRequest->quotes
                ->filter(fn ($quote) => in_array((string) $quote->status, ['received', 'selected'], true))
                ->values();

            $requestItemsById = $purchaseRequest->items->keyBy(fn ($item) => (int) $item->id);
            $quoteBySupplierId = $eligibleQuotes->keyBy(fn ($quote) => (int) $quote->supplier_id);
            $normalizedLines = collect($this->input('manual_lines', []));

            $hasAtLeastOneLine = false;

            foreach ($normalizedLines as $index => $line) {
                $requestItemId = (int) ($line['purchase_request_item_id'] ?? 0);
                $supplierId = (int) ($line['supplier_id'] ?? 0);
                $awardedQty = $line['awarded_qty'] !== null ? (float) $line['awarded_qty'] : null;

                if ($supplierId <= 0 && ($awardedQty === null || $awardedQty <= 0)) {
                    continue;
                }

                if ($requestItemId <= 0 || ! $requestItemsById->has($requestItemId)) {
                    $validator->errors()->add(
                        "manual_lines.$index.purchase_request_item_id",
                        'Linha de RFQ invalida para adjudicacao manual.'
                    );
                    continue;
                }

                if ($supplierId <= 0) {
                    $validator->errors()->add(
                        "manual_lines.$index.supplier_id",
                        'Seleciona um fornecedor para esta linha.'
                    );
                    continue;
                }

                if ($awardedQty === null || $awardedQty <= 0) {
                    $validator->errors()->add(
                        "manual_lines.$index.awarded_qty",
                        'Indica uma quantidade valida para esta linha.'
                    );
                    continue;
                }

                $requestQty = (float) ($requestItemsById->get($requestItemId)?->qty ?? 0);
                if ($awardedQty > $requestQty + 0.0005) {
                    $validator->errors()->add(
                        "manual_lines.$index.awarded_qty",
                        'A quantidade nao pode ser superior ao pedido da linha.'
                    );
                }

                $quote = $quoteBySupplierId->get($supplierId);
                if (! $quote) {
                    $validator->errors()->add(
                        "manual_lines.$index.supplier_id",
                        'O fornecedor selecionado nao tem proposta valida para este RFQ.'
                    );
                    continue;
                }

                $quoteItem = $quote->items->firstWhere('purchase_request_item_id', $requestItemId);
                if (! $quoteItem || $quoteItem->unit_price === null) {
                    $validator->errors()->add(
                        "manual_lines.$index.supplier_id",
                        'O fornecedor selecionado nao cotou esta linha.'
                    );
                    continue;
                }

                $quoteMaxQty = $quoteItem->quoted_qty !== null
                    ? (float) $quoteItem->quoted_qty
                    : $requestQty;

                if ($quoteMaxQty > 0 && $awardedQty > $quoteMaxQty + 0.0005) {
                    $validator->errors()->add(
                        "manual_lines.$index.awarded_qty",
                        'A quantidade nao pode exceder a quantidade cotada pelo fornecedor.'
                    );
                }

                $hasAtLeastOneLine = true;
            }

            if (! $hasAtLeastOneLine) {
                $validator->errors()->add(
                    'manual_lines',
                    'Preenche pelo menos uma linha com fornecedor e quantidade para encomenda parcial.'
                );
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
        $value = str_replace(',', '.', trim((string) $value));
        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
