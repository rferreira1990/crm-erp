<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $unitId = is_object($unit) ? $unit->id : $unit;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'code')
                    ->ignore($unitId),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('units', 'name')
                    ->ignore($unitId),
            ],
            'factor' => ['required', 'numeric', 'min:0.001'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->code)),
            'name' => trim((string) $this->name),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
