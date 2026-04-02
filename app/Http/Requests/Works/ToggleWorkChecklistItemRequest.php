<?php

namespace App\Http\Requests\Works;

use Illuminate\Foundation\Http\FormRequest;

class ToggleWorkChecklistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_completed' => $this->boolean('is_completed'),
        ]);
    }

    public function rules(): array
    {
        return [
            'is_completed' => ['required', 'boolean'],
        ];
    }
}

