<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxExemptionReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('tax_exemption_reasons', 'code')->ignore($this->route('tax_exemption_reason')),
            ],
            'description' => ['required', 'string', 'max:1000'],
            'invoice_note' => ['nullable', 'string', 'max:1000'],
            'legal_reference' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->code)),
            'description' => trim((string) $this->description),
            'invoice_note' => $this->filled('invoice_note') ? trim((string) $this->invoice_note) : null,
            'legal_reference' => $this->filled('legal_reference') ? trim((string) $this->legal_reference) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
