<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('items.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'type' => ['required', Rule::in(['product', 'service'])],
            'description' => ['nullable', 'string'],

            'family_id' => ['nullable', 'exists:item_families,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],

            'barcode' => ['nullable', 'string', 'max:100'],
            'supplier_reference' => ['nullable', 'string', 'max:120'],

            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'max_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'tracks_stock' => ['nullable', 'boolean'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'stock_alert' => ['nullable', 'boolean'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');

        $this->merge([
            'name' => trim((string) $this->name),
            'short_name' => $this->filled('short_name') ? trim((string) $this->short_name) : null,
            'description' => $this->filled('description') ? trim((string) $this->description) : null,
            'barcode' => $this->filled('barcode') ? trim((string) $this->barcode) : null,
            'supplier_reference' => $this->filled('supplier_reference') ? trim((string) $this->supplier_reference) : null,

            'tracks_stock' => $type === 'service' ? false : $this->boolean('tracks_stock'),
            'stock_alert' => $type === 'service' ? false : $this->boolean('stock_alert'),
            'is_active' => $this->boolean('is_active'),

            'cost_price' => $this->filled('cost_price') ? $this->cost_price : 0,
            'max_discount_percent' => $this->filled('max_discount_percent') ? $this->max_discount_percent : null,
        ]);

        if ($type === 'service') {
            $this->merge([
                'min_stock' => 0,
                'max_stock' => null,
            ]);
        } else {
            $this->merge([
                'min_stock' => $this->filled('min_stock') ? $this->min_stock : 0,
                'max_stock' => $this->filled('max_stock') ? $this->max_stock : null,
            ]);
        }
    }
}
