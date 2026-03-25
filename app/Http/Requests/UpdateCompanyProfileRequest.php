<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['nullable', 'string', 'max:150'],
            'address_line_1' => ['nullable', 'string', 'max:150'],
            'address_line_2' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:4'],
            'postal_code_suffix' => ['nullable', 'string', 'max:3'],
            'postal_designation' => ['nullable', 'string', 'max:100'],
            'country_code' => ['nullable', 'string', 'max:5'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'fax' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'url', 'max:200'],
            'share_capital' => ['nullable', 'numeric', 'min:0'],
            'registry_office' => ['nullable', 'string', 'max:150'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'bank_iban' => ['nullable', 'string', 'max:50'],
            'bank_bic_swift' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_name' => $this->normalize($this->company_name),
            'address_line_1' => $this->normalize($this->address_line_1),
            'address_line_2' => $this->normalize($this->address_line_2),
            'city' => $this->normalize($this->city),
            'district' => $this->normalize($this->district),
            'postal_code' => $this->onlyDigits($this->postal_code),
            'postal_code_suffix' => $this->onlyDigits($this->postal_code_suffix),
            'postal_designation' => $this->normalize($this->postal_designation),
            'country_code' => strtoupper($this->normalize($this->country_code) ?? 'PT'),
            'tax_number' => $this->onlyDigits($this->tax_number),
            'phone' => $this->normalize($this->phone),
            'fax' => $this->normalize($this->fax),
            'contact_person' => $this->normalize($this->contact_person),
            'email' => $this->normalizeEmail($this->email),
            'website' => $this->normalize($this->website),
            'registry_office' => $this->normalize($this->registry_office),
            'bank_name' => $this->normalize($this->bank_name),
            'bank_iban' => strtoupper(str_replace(' ', '', (string) $this->bank_iban)),
            'bank_bic_swift' => strtoupper(str_replace(' ', '', (string) $this->bank_bic_swift)),
            'remove_logo' => $this->boolean('remove_logo'),
        ]);
    }

    private function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : strtolower($value);
    }

    private function onlyDigits(mixed $value): ?string
    {
        $value = preg_replace('/\D+/', '', (string) $value);
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
