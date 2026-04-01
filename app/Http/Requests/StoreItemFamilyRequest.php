<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $parentId = $this->normalizedParentId();

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('item_families', 'name')
                    ->where(function ($query) use ($parentId) {
                        if ($parentId === null) {
                            $query->whereNull('parent_id');
                        } else {
                            $query->where('parent_id', $parentId);
                        }
                    }),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('item_families', 'id'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->normalizedParentId(),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function validatedData(): array
    {
        $data = $this->validated();
        $data['parent_id'] = $data['parent_id'] ?? null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function normalizedParentId(): ?int
    {
        $value = trim((string) $this->input('parent_id'));
        if ($value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_INT) === false
            ? null
            : (int) $value;
    }
}
