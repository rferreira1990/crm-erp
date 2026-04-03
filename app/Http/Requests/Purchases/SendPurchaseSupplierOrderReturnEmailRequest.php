<?php

namespace App\Http\Requests\Purchases;

use Illuminate\Foundation\Http\FormRequest;

class SendPurchaseSupplierOrderReturnEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchases.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'recipient_name' => $this->normalizeNullableString($this->input('recipient_name')),
            'recipient_email' => trim((string) $this->input('recipient_email')),
            'cc_email' => $this->normalizeNullableString($this->input('cc_email')),
            'bcc_email' => $this->normalizeNullableString($this->input('bcc_email')),
            'subject' => trim((string) $this->input('subject')),
            'email_notes' => $this->normalizeNullableString($this->input('email_notes')),
            'is_resend' => filter_var($this->input('is_resend', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'recipient_name' => ['nullable', 'string', 'max:150'],
            'recipient_email' => ['required', 'email', 'max:150'],
            'cc_email' => ['nullable', 'email', 'max:150'],
            'bcc_email' => ['nullable', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:190'],
            'email_notes' => ['nullable', 'string', 'max:5000'],
            'is_resend' => ['boolean'],
        ];
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

