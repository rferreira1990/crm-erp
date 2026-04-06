<?php

namespace App\Http\Requests\Purchases;

use App\Models\Item;
use App\Models\Supplier;
use App\Models\TaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseDirectPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($row) {
                return [
                    'item_id' => $this->normalizeInteger($row['item_id'] ?? null),
                    'description_snapshot' => $this->normalizeString($row['description_snapshot'] ?? null),
                    'unit_snapshot' => $this->normalizeString($row['unit_snapshot'] ?? null),
                    'quantity' => $this->normalizeDecimal($row['quantity'] ?? null, 3),
                    'unit_price' => $this->normalizeDecimal($row['unit_price'] ?? null, 4),
                    'vat_rate_id' => $this->normalizeInteger($row['vat_rate_id'] ?? null),
                    'notes' => $this->normalizeString($row['notes'] ?? null),
                ];
            })
            ->filter(function (array $row): bool {
                return ($row['item_id'] ?? null) !== null
                    || ($row['description_snapshot'] ?? null) !== null
                    || ($row['quantity'] ?? null) !== null
                    || ($row['unit_price'] ?? null) !== null;
            })
            ->values()
            ->all();

        $this->merge([
            'supplier_id' => $this->normalizeInteger($this->input('supplier_id')),
            'purchase_date' => $this->normalizeString($this->input('purchase_date')),
            'external_reference' => $this->normalizeString($this->input('external_reference')),
            'currency' => strtoupper((string) $this->normalizeString($this->input('currency')) ?: 'EUR'),
            'notes' => $this->normalizeString($this->input('notes')),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'purchase_date' => ['required', 'date'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'currency' => ['required', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:300'],
            'items.*.item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'items.*.description_snapshot' => ['required', 'string', 'max:255'],
            'items.*.unit_snapshot' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.vat_rate_id' => ['required', 'integer', Rule::exists('tax_rates', 'id')],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ownerId = (int) Auth::id();

            $supplierId = (int) ($this->input('supplier_id') ?? 0);
            if ($supplierId > 0) {
                $supplierValid = Supplier::query()
                    ->whereKey($supplierId)
                    ->where('owner_id', $ownerId)
                    ->exists();

                if (! $supplierValid) {
                    $validator->errors()->add('supplier_id', 'Fornecedor invalido para o utilizador atual.');
                }
            }

            $itemIds = collect($this->input('items', []))
                ->pluck('item_id')
                ->filter(fn ($itemId) => (int) $itemId > 0)
                ->map(fn ($itemId) => (int) $itemId)
                ->unique()
                ->values();

            $taxRateIds = collect($this->input('items', []))
                ->pluck('vat_rate_id')
                ->filter(fn ($taxRateId) => (int) $taxRateId > 0)
                ->map(fn ($taxRateId) => (int) $taxRateId)
                ->unique()
                ->values();

            $validItemIds = Item::query()
                ->whereIn('id', $itemIds->all())
                ->where('is_active', true)
                ->where('type', '!=', 'service')
                ->where('tracks_stock', true)
                ->where(function ($query) use ($ownerId): void {
                    $query->where('owner_id', $ownerId)
                        ->orWhereNull('owner_id');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $validTaxRateIds = TaxRate::query()
                ->whereIn('id', $taxRateIds->all())
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $validItemMap = array_fill_keys($validItemIds, true);
            $validTaxRateMap = array_fill_keys($validTaxRateIds, true);

            foreach ($this->input('items', []) as $index => $row) {
                $itemId = (int) ($row['item_id'] ?? 0);
                if (! isset($validItemMap[$itemId])) {
                    $validator->errors()->add(
                        'items.' . $index . '.item_id',
                        'Artigo invalido para compra direta (tem de ser produto ativo com controlo de stock).'
                    );
                }

                $taxRateId = (int) ($row['vat_rate_id'] ?? 0);
                if (! isset($validTaxRateMap[$taxRateId])) {
                    $validator->errors()->add(
                        'items.' . $index . '.vat_rate_id',
                        'Taxa de IVA invalida ou inativa.'
                    );
                }
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

    private function normalizeDecimal(mixed $value, int $precision): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = str_replace(',', '.', $value);
        if (! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, $precision);
    }
}

