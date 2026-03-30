<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $isActive = $this->has('is_active')
            ? $this->boolean('is_active')
            : true;

        $this->merge([
            'code' => $this->normalizeUpperNoSpaces($this->input('code')),
            'name' => $this->normalize($this->input('name')),
            'tax_number' => $this->onlyDigits($this->input('tax_number')),
            'email' => $this->normalizeEmail($this->input('email')),
            'phone' => $this->normalize($this->input('phone')),
            'mobile' => $this->normalize($this->input('mobile')),
            'contact_person' => $this->normalize($this->input('contact_person')),
            'website' => $this->normalize($this->input('website')),
            'external_reference' => $this->normalize($this->input('external_reference')),
            'address' => $this->normalize($this->input('address')),
            'postal_code' => $this->normalize($this->input('postal_code')),
            'city' => $this->normalize($this->input('city')),
            'country' => $this->normalize($this->input('country')) ?: 'Portugal',
            'payment_term_id' => $this->normalizeInteger($this->input('payment_term_id')),
            'default_tax_rate_id' => $this->normalizeInteger($this->input('default_tax_rate_id')),
            'default_discount_percent' => $this->normalizeDecimal($this->input('default_discount_percent')) ?? 0,
            'lead_time_days' => $this->normalizeInteger($this->input('lead_time_days')),
            'minimum_order_value' => $this->normalizeDecimal($this->input('minimum_order_value')),
            'free_shipping_threshold' => $this->normalizeDecimal($this->input('free_shipping_threshold')),
            'preferred_payment_method' => $this->normalize($this->input('preferred_payment_method')),
            'default_notes_for_purchases' => $this->normalize($this->input('default_notes_for_purchases')),
            'delivery_instructions' => $this->normalize($this->input('delivery_instructions')),
            'habitual_order_email' => $this->normalizeEmail($this->input('habitual_order_email')),
            'preferred_contact_method' => $this->normalizeLower($this->input('preferred_contact_method')),
            'notes' => $this->normalize($this->input('notes')),
            'is_active' => $isActive,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'tax_number' => ['nullable', 'digits:9'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'website' => ['nullable', 'string', 'max:255'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'payment_term_id' => ['nullable', 'integer', Rule::exists('payment_terms', 'id')],
            'default_tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')],
            'default_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'preferred_payment_method' => ['nullable', 'string', 'max:100'],
            'default_notes_for_purchases' => ['nullable', 'string'],
            'delivery_instructions' => ['nullable', 'string'],
            'habitual_order_email' => ['nullable', 'email', 'max:150'],
            'preferred_contact_method' => ['nullable', Rule::in(['email', 'phone', 'mobile'])],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'codigo',
            'name' => 'nome',
            'tax_number' => 'NIF',
            'email' => 'email',
            'phone' => 'telefone',
            'mobile' => 'telemovel',
            'contact_person' => 'pessoa de contacto',
            'website' => 'website',
            'external_reference' => 'referencia externa',
            'address' => 'morada',
            'postal_code' => 'codigo postal',
            'city' => 'cidade',
            'country' => 'pais',
            'payment_term_id' => 'condicao de pagamento',
            'default_tax_rate_id' => 'taxa de IVA por defeito',
            'default_discount_percent' => 'desconto por defeito',
            'lead_time_days' => 'prazo medio de entrega',
            'minimum_order_value' => 'valor minimo de encomenda',
            'free_shipping_threshold' => 'valor minimo para portes gratis',
            'preferred_payment_method' => 'metodo de pagamento preferido',
            'default_notes_for_purchases' => 'notas por defeito para compras',
            'delivery_instructions' => 'instrucoes de entrega',
            'habitual_order_email' => 'email habitual de encomenda',
            'preferred_contact_method' => 'metodo de contacto preferido',
            'notes' => 'notas',
            'is_active' => 'ativo',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $taxNumber = $this->input('tax_number');

            if ($taxNumber !== null && $taxNumber !== '' && ! $this->isValidPortugueseTaxNumber($taxNumber)) {
                $validator->errors()->add('tax_number', 'O NIF indicado nao e valido.');
            }
        });
    }

    private function isValidPortugueseTaxNumber(string $taxNumber): bool
    {
        if (! preg_match('/^[0-9]{9}$/', $taxNumber)) {
            return false;
        }

        $firstDigit = (int) $taxNumber[0];

        if (! in_array($firstDigit, [1, 2, 3, 5, 6, 8, 9], true)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += ((int) $taxNumber[$i]) * (9 - $i);
        }

        $checkDigit = 11 - ($sum % 11);
        $checkDigit = $checkDigit >= 10 ? 0 : $checkDigit;

        return $checkDigit === (int) $taxNumber[8];
    }

    private function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtolower($value);
    }

    private function normalizeLower(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtolower($value);
    }

    private function normalizeUpperNoSpaces(mixed $value): ?string
    {
        $value = preg_replace('/\s+/', '', strtoupper(trim((string) $value)));

        return $value === '' ? null : $value;
    }

    private function onlyDigits(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }
}

