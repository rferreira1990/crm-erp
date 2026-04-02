<?php

namespace App\Http\Requests\Works;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ApplyWorkChecklistTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'template_id' => $this->input('template_id'),
        ]);
    }

    public function rules(): array
    {
        $ownerId = (int) (Auth::id() ?? 0);

        return [
            'template_id' => [
                'required',
                'integer',
                Rule::exists('work_checklist_templates', 'id')
                    ->where('owner_id', $ownerId)
                    ->where('is_active', true),
            ],
        ];
    }
}
