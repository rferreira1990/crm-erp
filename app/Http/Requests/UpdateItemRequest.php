<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('items.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');

        $tracksStock = filter_var($this->input('tracks_stock', false), FILTER_VALIDATE_BOOLEAN);
        $stockAlert = filter_var($this->input('stock_alert', false), FILTER_VALIDATE_BOOLEAN);

        if ($type === 'service') {
            $tracksStock = false;
            $stockAlert = false;

            $this->merge([
                'tracks_stock' => false,
                'stock_alert' => false,
                'min_stock' => 0,
                'max_stock' => null,
            ]);

            return;
        }

        $this->merge([
            'tracks_stock' => $tracksStock,
            'stock_alert' => $stockAlert,
            'min_stock' => $this->filled('min_stock') ? $this->input('min_stock') : 0,
            'max_stock' => $this->filled('max_stock') ? $this->input('max_stock') : null,
        ]);
    }

    public function rules(): array
    {
        $itemId = $this->route('item')->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],

            'type' => ['required', Rule::in(['product', 'service'])],

            'family_id' => ['nullable', 'exists:item_families,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'tax_rate_id' => ['required', 'exists:tax_rates,id'],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items', 'barcode')->ignore($itemId),
            ],
            'supplier_reference' => ['nullable', 'string', 'max:100'],

            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'max_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'tracks_stock' => ['nullable', 'boolean'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'stock_alert' => ['nullable', 'boolean'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'type.required' => 'O tipo é obrigatório.',
            'unit_id.required' => 'A unidade é obrigatória.',
            'tax_rate_id.required' => 'A taxa de IVA é obrigatória.',
            'barcode.unique' => 'O código de barras já está a ser usado por outro artigo.',
        ];
    }

    public function validatedData(): array
    {
        $data = $this->validated();

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['tracks_stock'] = (bool) ($data['tracks_stock'] ?? false);
        $data['stock_alert'] = (bool) ($data['stock_alert'] ?? false);

        $data['cost_price'] = $data['cost_price'] ?? 0;
        $data['sale_price'] = $data['sale_price'] ?? 0;
        $data['max_discount_percent'] = $data['max_discount_percent'] ?? 0;
        $data['min_stock'] = $data['min_stock'] ?? 0;
        $data['max_stock'] = $data['max_stock'] ?? null;

        if (($data['type'] ?? null) === 'service') {
            $data['tracks_stock'] = false;
            $data['stock_alert'] = false;
            $data['min_stock'] = 0;
            $data['max_stock'] = null;
        }

        return $data;
    }
}
