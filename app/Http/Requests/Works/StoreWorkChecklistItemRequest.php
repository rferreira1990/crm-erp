<?php

namespace App\Http\Requests\Works;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'item_description' => trim((string) $this->input('item_description')),
            'item_is_required' => $this->boolean('item_is_required'),
        ]);
    }

    public function rules(): array
    {
        return [
            'item_description' => ['required', 'string', 'max:500'],
            'item_is_required' => ['required', 'boolean'],
        ];
    }
}
