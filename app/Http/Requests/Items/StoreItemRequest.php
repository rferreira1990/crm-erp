<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('items.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        $tracksStock = $this->boolean('tracks_stock');
        $stockAlert = $this->boolean('stock_alert');

        /*
        |--------------------------------------------------------------------------
        | Serviços nunca controlam stock
        |--------------------------------------------------------------------------
        */
        if ($type === 'service') {
            $this->merge([
                'tracks_stock' => false,
                'stock_alert' => false,
                'min_stock' => 0,
                'max_stock' => null,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Produtos sem controlo de stock não devem guardar regras de stock
        |--------------------------------------------------------------------------
        */
        if (! $tracksStock) {
            $this->merge([
                'tracks_stock' => false,
                'stock_alert' => false,
                'min_stock' => 0,
                'max_stock' => null,
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Produtos com controlo de stock
        |--------------------------------------------------------------------------
        */
        $this->merge([
            'tracks_stock' => true,
            'stock_alert' => $stockAlert,
            'min_stock' => $this->filled('min_stock') ? $this->input('min_stock') : 0,
            'max_stock' => $this->filled('max_stock') ? $this->input('max_stock') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],

            'type' => ['required', Rule::in(['product', 'service'])],

            'family_id' => ['nullable', 'exists:item_families,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'tax_rate_id' => ['required', 'exists:tax_rates,id'],

            'barcode' => ['nullable', 'string', 'max:100', 'unique:items,barcode'],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $tracksStock = $this->boolean('tracks_stock');

            if ($type !== 'product' || ! $tracksStock) {
                return;
            }

            $minStock = $this->filled('min_stock') ? (float) $this->input('min_stock') : 0;
            $maxStock = $this->filled('max_stock') ? (float) $this->input('max_stock') : null;

            if ($maxStock !== null && $maxStock < $minStock) {
                $validator->errors()->add(
                    'max_stock',
                    'O stock máximo tem de ser igual ou superior ao stock mínimo.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'type.required' => 'O tipo é obrigatório.',
            'unit_id.required' => 'A unidade é obrigatória.',
            'tax_rate_id.required' => 'A taxa de IVA é obrigatória.',
            'barcode.unique' => 'O código de barras já está a ser usado por outro artigo.',
            'min_stock.min' => 'O stock mínimo não pode ser negativo.',
            'max_stock.min' => 'O stock máximo não pode ser negativo.',
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

        /*
        |--------------------------------------------------------------------------
        | Serviços e produtos sem controlo de stock
        |--------------------------------------------------------------------------
        */
        if (
            ($data['type'] ?? null) === 'service' ||
            ! ($data['tracks_stock'] ?? false)
        ) {
            $data['tracks_stock'] = false;
            $data['stock_alert'] = false;
            $data['min_stock'] = 0;
            $data['max_stock'] = null;
        }

        return $data;
    }
}
