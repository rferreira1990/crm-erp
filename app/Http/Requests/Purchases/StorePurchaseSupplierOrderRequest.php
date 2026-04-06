<?php

namespace App\Http\Requests\Purchases;

use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseSupplierOrderRequest extends FormRequest
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
                    'description' => $this->normalizeString($row['description'] ?? null),
                    'qty' => $this->normalizeDecimal($row['qty'] ?? null, 3),
                    'unit_snapshot' => $this->normalizeString($row['unit_snapshot'] ?? null),
                    'unit_price' => $this->normalizeDecimal($row['unit_price'] ?? null, 4),
                    'discount_percent' => $this->normalizeDecimal($row['discount_percent'] ?? null, 3),
                    'notes' => $this->normalizeString($row['notes'] ?? null),
                ];
            })
            ->filter(function (array $row): bool {
                return ($row['item_id'] ?? null) !== null
                    || ($row['description'] ?? null) !== null
                    || ($row['qty'] ?? null) !== null
                    || ($row['unit_price'] ?? null) !== null;
            })
            ->values()
            ->all();

        $this->merge([
            'supplier_id' => $this->normalizeInteger($this->input('supplier_id')),
            'payment_term_id' => $this->normalizeInteger($this->input('payment_term_id')),
            'currency' => strtoupper((string) $this->normalizeString($this->input('currency')) ?: 'EUR'),
            'prepared_at' => $this->normalizeString($this->input('prepared_at')),
            'notes' => $this->normalizeString($this->input('notes')),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'payment_term_id' => ['nullable', 'integer', Rule::exists('payment_terms', 'id')],
            'currency' => ['required', 'string', 'size:3'],
            'prepared_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:300'],
            'items.*.item_id' => ['nullable', 'integer', Rule::exists('items', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_snapshot' => ['nullable', 'string', 'max:100'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
                ->filter(fn ($id) => (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($itemIds->isEmpty()) {
                return;
            }

            $validItemIds = Item::query()
                ->whereIn('id', $itemIds->all())
                ->where('type', '!=', 'service')
                ->where(function ($query) use ($ownerId) {
                    $query->where('owner_id', $ownerId)
                        ->orWhereNull('owner_id');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $validItemMap = array_fill_keys($validItemIds, true);

            foreach ($this->input('items', []) as $index => $row) {
                $itemId = (int) ($row['item_id'] ?? 0);
                if ($itemId <= 0) {
                    continue;
                }

                if (! isset($validItemMap[$itemId])) {
                    $validator->errors()->add('items.' . $index . '.item_id', 'Artigo invalido para este utilizador.');
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

