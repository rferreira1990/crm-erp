<?php

namespace App\Http\Requests\WorkChecklistTemplates;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkChecklistTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $rawItems = $this->input('items', []);
        $items = is_array($rawItems) ? $rawItems : [];

        $normalizedItems = collect($items)
            ->map(function (mixed $item): array {
                $item = is_array($item) ? $item : [];

                return [
                    'description' => trim((string) ($item['description'] ?? '')),
                    'is_required' => filter_var($item['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'sort_order' => isset($item['sort_order']) && $item['sort_order'] !== ''
                        ? (int) $item['sort_order']
                        : 0,
                ];
            })
            ->filter(fn (array $item): bool => $item['description'] !== '')
            ->values()
            ->all();

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'description' => $this->normalizeNullableString($this->input('description')),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
            'items' => $normalizedItems,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.is_required' => ['required', 'boolean'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
