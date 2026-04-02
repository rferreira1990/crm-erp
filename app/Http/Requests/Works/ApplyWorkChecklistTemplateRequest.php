<?php

namespace App\Http\Requests\Works;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyWorkChecklistTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'template_key' => trim((string) $this->input('template_key')),
        ]);
    }

    public function rules(): array
    {
        $templateKeys = array_keys(config('work_checklists.templates', []));

        return [
            'template_key' => ['required', 'string', Rule::in($templateKeys)],
        ];
    }
}

