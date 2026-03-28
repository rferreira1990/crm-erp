<?php

namespace App\Http\Requests\Stock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreManualStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('stock.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'item_id' => $this->normalizeInteger($this->input('item_id')),
            'movement_type' => $this->normalizeString($this->input('movement_type')),
            'direction' => $this->normalizeString($this->input('direction')),
            'quantity' => $this->normalizeDecimal($this->input('quantity')),
            'occurred_at' => $this->normalizeString($this->input('occurred_at')),
            'notes' => $this->normalizeString($this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', Rule::exists('items', 'id')],
            'movement_type' => ['required', Rule::in(['manual_entry', 'manual_exit', 'manual_adjustment'])],
            'direction' => ['required', Rule::in(['in', 'out', 'adjustment'])],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'occurred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $movementType = $this->input('movement_type');
            $direction = $this->input('direction');
            $quantity = (float) ($this->input('quantity') ?? 0);

            $expectedDirection = match ($movementType) {
                'manual_entry' => 'in',
                'manual_exit' => 'out',
                default => 'adjustment',
            };

            if ($direction !== $expectedDirection) {
                $validator->errors()->add('direction', 'A direcao nao corresponde ao tipo de movimento manual.');
            }

            if (in_array($direction, ['in', 'out'], true) && $quantity <= 0) {
                $validator->errors()->add('quantity', 'Para entrada/saida, a quantidade deve ser superior a zero.');
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
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }
}
