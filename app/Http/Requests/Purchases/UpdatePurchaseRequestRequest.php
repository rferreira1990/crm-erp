<?php

namespace App\Http\Requests\Purchases;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($row) {
                return [
                    'item_id' => $this->normalizeInteger($row['item_id'] ?? null),
                    'description' => $this->normalizeString($row['description'] ?? null),
                    'qty' => $this->normalizeDecimal($row['qty'] ?? null),
                    'unit_snapshot' => $this->normalizeString($row['unit_snapshot'] ?? null),
                    'notes' => $this->normalizeString($row['notes'] ?? null),
                ];
            })
            ->filter(function (array $row) {
                return ($row['description'] ?? null) !== null
                    || ($row['qty'] ?? null) !== null
                    || ($row['item_id'] ?? null) !== null;
            })
            ->values()
            ->all();

        $this->merge([
            'title' => $this->normalizeString($this->input('title')),
            'work_id' => $this->normalizeInteger($this->input('work_id')),
            'deadline_at' => $this->normalizeString($this->input('deadline_at')),
            'status' => $this->normalizeString($this->input('status')),
            'notes' => $this->normalizeString($this->input('notes')),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'work_id' => ['nullable', 'integer', Rule::exists('works', 'id')],
            'deadline_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(array_keys(PurchaseRequest::statuses()))],
            'notes' => ['nullable', 'string', 'max:10000'],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.item_id' => ['nullable', 'integer', Rule::exists('items', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_snapshot' => ['nullable', 'string', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
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
