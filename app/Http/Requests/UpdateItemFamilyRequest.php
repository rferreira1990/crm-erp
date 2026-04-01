<?php

namespace App\Http\Requests;

use App\Models\ItemFamily;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateItemFamilyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $itemFamily = $this->route('item_family');
        $parentId = $this->normalizedParentId();

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('item_families', 'name')
                    ->ignore($itemFamily?->id)
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $itemFamily = $this->route('item_family');
            if (! $itemFamily instanceof ItemFamily) {
                return;
            }

            $parentId = $this->normalizedParentId();
            if ($parentId === null) {
                return;
            }

            if ((int) $itemFamily->id === $parentId) {
                $validator->errors()->add('parent_id', 'A familia pai nao pode ser a propria familia.');
                return;
            }

            if (in_array($parentId, $itemFamily->descendantIds(), true)) {
                $validator->errors()->add('parent_id', 'A familia pai selecionada e descendente desta familia (ciclo invalido).');
            }
        });
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