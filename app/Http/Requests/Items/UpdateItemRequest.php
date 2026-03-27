<?php

namespace App\Http\Requests\Items;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('items.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $type = $this->input('type');
        $tracksStock = $this->boolean('tracks_stock');
        $stockAlert = $this->boolean('stock_alert');

        if ($type === 'service') {
            $this->merge([
                'tracks_stock' => false,
                'stock_alert' => false,
                'min_stock' => 0,
                'max_stock' => null,
            ]);
            return;
        }

        if (! $tracksStock) {
            $this->merge([
                'tracks_stock' => false,
                'stock_alert' => false,
                'min_stock' => 0,
                'max_stock' => null,
            ]);
            return;
        }

        $this->merge([
            'tracks_stock' => true,
            'stock_alert' => $stockAlert,
            'min_stock' => $this->filled('min_stock') ? $this->input('min_stock') : 0,
            'max_stock' => $this->filled('max_stock') ? $this->input('max_stock') : null,
        ]);
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $ownerId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],

            'type' => ['required', Rule::in(['product', 'service'])],

            'family_id' => [
                'nullable',
                'integer',
                Rule::exists('item_families', 'id')->where(function ($q) use ($ownerId, $item) {
                    $q->where('owner_id', $ownerId)
                      ->where(function ($sub) use ($item) {
                          $sub->where('is_active', true);
                          if ($item?->family_id) {
                              $sub->orWhere('id', $item->family_id);
                          }
                      });
                }),
            ],

            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')->where(function ($q) use ($ownerId, $item) {
                    $q->where('owner_id', $ownerId)
                      ->where(function ($sub) use ($item) {
                          $sub->where('is_active', true);
                          if ($item?->brand_id) {
                              $sub->orWhere('id', $item->brand_id);
                          }
                      });
                }),
            ],

            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where(fn($q) => $q->where('owner_id', $ownerId)),
            ],

            'tax_rate_id' => [
                'required',
                'integer',
                Rule::exists('tax_rates', 'id')->where(fn($q) => $q->where('owner_id', $ownerId)),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items', 'barcode')->ignore($item?->id),
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('type') !== 'product' || ! $this->boolean('tracks_stock')) {
                return;
            }

            $min = (float) ($this->input('min_stock') ?? 0);
            $max = $this->filled('max_stock') ? (float) $this->input('max_stock') : null;

            if ($max !== null && $max < $min) {
                $validator->errors()->add('max_stock', 'O stock máximo tem de ser >= stock mínimo.');
            }
        });
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

        if (($data['type'] ?? null) === 'service' || ! $data['tracks_stock']) {
            $data['tracks_stock'] = false;
            $data['stock_alert'] = false;
            $data['min_stock'] = 0;
            $data['max_stock'] = null;
        }

        return $data;
    }
}
