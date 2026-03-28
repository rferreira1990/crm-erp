<?php

namespace App\Http\Requests\Budgets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('budgets.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'designation' => $this->normalize($this->input('designation')),
            'zone' => $this->normalize($this->input('zone')),
            'project_name' => $this->normalize($this->input('project_name')),
            'notes' => $this->normalize($this->input('notes')),
            'external_reference' => $this->normalize($this->input('external_reference')),
            'budget_date' => $this->filled('budget_date') ? $this->input('budget_date') : now()->toDateString(),
            'payment_term_id' => $this->normalizeInteger($this->input('payment_term_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id'),
            ],
            'payment_term_id' => [
                'nullable',
                'integer',
                Rule::exists('payment_terms', 'id'),
            ],
            'designation' => ['nullable', 'string', 'max:255'],
            'budget_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:budget_date'],
            'zone' => ['nullable', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'É obrigatório selecionar um cliente.',
            'customer_id.exists' => 'O cliente selecionado não é válido.',
            'payment_term_id.exists' => 'A condição de pagamento selecionada não é válida.',
            'budget_date.required' => 'A data do orçamento é obrigatória.',
            'budget_date.date' => 'A data do orçamento é inválida.',
            'valid_until.date' => 'A validade é inválida.',
            'valid_until.after_or_equal' => 'A validade deve ser igual ou posterior à data do orçamento.',
            'designation.max' => 'A designação não pode ter mais de 255 caracteres.',
            'zone.max' => 'A zona não pode ter mais de 255 caracteres.',
            'project_name.max' => 'O projeto não pode ter mais de 255 caracteres.',
            'external_reference.max' => 'A referência externa não pode ter mais de 255 caracteres.',
        ];
    }

    private function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }
}
