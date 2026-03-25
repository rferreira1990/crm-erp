<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        $ownerId = $this->user()?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units', 'code')->where(function ($query) use ($ownerId) {
                    $query->where(function ($subQuery) use ($ownerId) {
                        $subQuery
                            ->whereNull('owner_id')
                            ->orWhere('owner_id', $ownerId);
                    });
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('units', 'name')->where(function ($query) use ($ownerId) {
                    $query->where(function ($subQuery) use ($ownerId) {
                        $subQuery
                            ->whereNull('owner_id')
                            ->orWhere('owner_id', $ownerId);
                    });
                }),
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
