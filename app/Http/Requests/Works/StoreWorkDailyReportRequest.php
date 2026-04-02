<?php

namespace App\Http\Requests\Works;

use App\Models\Item;
use App\Models\Work;
use App\Models\WorkDailyReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkDailyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($row) {
                return [
                    'item_id' => $this->normalizeInteger($row['item_id'] ?? null),
                    'description_snapshot' => $this->normalizeString($row['description_snapshot'] ?? null),
                    'quantity' => $this->normalizeDecimal($row['quantity'] ?? null),
                    'unit_snapshot' => $this->normalizeString($row['unit_snapshot'] ?? null),
                ];
            })
            ->filter(function (array $row) {
                return $row['item_id'] !== null
                    || $row['description_snapshot'] !== null
                    || $row['quantity'] !== null
                    || $row['unit_snapshot'] !== null;
            })
            ->values()
            ->all();

        $this->merge([
            'report_date' => $this->normalizeString($this->input('report_date')),
            'day_status' => $this->normalizeString($this->input('day_status')),
            'work_summary' => $this->normalizeString($this->input('work_summary')),
            'hours_spent' => $this->normalizeDecimal($this->input('hours_spent')) ?? 0,
            'notes' => $this->normalizeString($this->input('notes')),
            'incidents' => $this->normalizeString($this->input('incidents')),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date'],
            'day_status' => ['required', Rule::in(array_keys(WorkDailyReport::statuses()))],
            'work_summary' => ['required', 'string', 'max:10000'],
            'hours_spent' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'incidents' => ['nullable', 'string', 'max:10000'],
            'items' => ['nullable', 'array', 'max:200'],
            'items.*.item_id' => ['nullable', 'integer', Rule::exists('items', 'id')],
            'items.*.description_snapshot' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'nullable', 'numeric', 'gt:0'],
            'items.*.unit_snapshot' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Work|null $work */
            $work = $this->route('work');
            $ownerId = (int) ($work?->owner_id ?? 0);

            $rows = collect($this->input('items', []));
            $itemIds = $rows
                ->pluck('item_id')
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $itemsById = Item::query()
                ->whereIn('id', $itemIds)
                ->get(['id', 'owner_id', 'is_active'])
                ->keyBy('id');

            foreach ($rows as $index => $row) {
                $itemId = isset($row['item_id']) ? (int) $row['item_id'] : null;
                $description = trim((string) ($row['description_snapshot'] ?? ''));
                $quantity = $row['quantity'] ?? null;

                if ($itemId === null && $description === '') {
                    $validator->errors()->add("items.{$index}.description_snapshot", 'Indica um artigo ou uma descricao.');
                }

                if ($quantity === null || (float) $quantity <= 0) {
                    $validator->errors()->add("items.{$index}.quantity", 'A quantidade deve ser maior que zero.');
                }

                if ($itemId !== null) {
                    $item = $itemsById->get($itemId);

                    if (! $item) {
                        $validator->errors()->add("items.{$index}.item_id", 'O artigo selecionado nao existe.');
                        continue;
                    }

                    $itemOwnerId = $item->owner_id !== null ? (int) $item->owner_id : null;
                    if ($ownerId > 0 && $itemOwnerId !== null && $itemOwnerId !== $ownerId) {
                        $validator->errors()->add("items.{$index}.item_id", 'O artigo selecionado nao pertence ao owner da obra.');
                    }

                    if (! (bool) $item->is_active) {
                        $validator->errors()->add("items.{$index}.item_id", 'O artigo selecionado esta inativo.');
                    }
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

    private function normalizeDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }
}

