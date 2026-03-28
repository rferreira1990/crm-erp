<?php

namespace App\Http\Requests\Works;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'qty' => $this->filled('qty') ? $this->input('qty') : 1,
            'unit_cost' => $this->filled('unit_cost') ? $this->input('unit_cost') : null,
            'apply_stock_movement' => $this->boolean('apply_stock_movement'),
            'notes' => $this->normalize($this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'qty' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'apply_stock_movement' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->boolean('apply_stock_movement')) {
                return;
            }

            $itemId = (int) ($this->input('item_id') ?? 0);
            if ($itemId <= 0) {
                return;
            }

            $item = Item::query()->find($itemId);
            if (! $item || ! $item->tracks_stock) {
                $validator->errors()->add('apply_stock_movement', 'O artigo selecionado nao permite movimento de stock.');
            }
        });
    }

    private function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
