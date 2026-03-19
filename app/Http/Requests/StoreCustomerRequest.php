<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determina se o utilizador está autorizado a fazer este pedido.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('customers.create') ?? false;
    }

    /**
     * Regras de validação para criação de cliente.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:private,company'],
            'nif' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'default_discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,prospect'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Preparação de dados antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'default_discount' => $this->input('default_discount', 0),
            'payment_terms_days' => $this->input('payment_terms_days', 0),
            'status' => $this->input('status', 'active'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
