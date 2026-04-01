<?php

namespace App\Http\Requests\Budgets;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('budgets.update') ?? false;
    }

    protected function getRedirectUrl(): string
    {
        $budget = $this->route('budget');

        if ($budget) {
            return route('budgets.show', $budget);
        }

        return parent::getRedirectUrl();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => $this->filled('quantity') ? $this->input('quantity') : 1,
            'unit_price' => $this->filled('unit_price') ? $this->input('unit_price') : 0,
            'discount_percent' => $this->filled('discount_percent') ? $this->input('discount_percent') : 0,
            'tax_rate_id' => $this->filled('tax_rate_id') ? $this->input('tax_rate_id') : null,
            'tax_exemption_reason_id' => $this->filled('tax_exemption_reason_id') ? $this->input('tax_exemption_reason_id') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_rate_id' => ['required', 'integer', 'exists:tax_rates,id'],
            'tax_exemption_reason_id' => ['nullable', 'integer', 'exists:tax_exemption_reasons,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.numeric' => 'A quantidade deve ser numérica.',
            'quantity.gt' => 'A quantidade tem de ser superior a zero.',

            'unit_price.required' => 'O preço unitário é obrigatório.',
            'unit_price.numeric' => 'O preço unitário deve ser numérico.',
            'unit_price.min' => 'O preço unitário não pode ser negativo.',

            'discount_percent.numeric' => 'O desconto deve ser numérico.',
            'discount_percent.min' => 'O desconto não pode ser negativo.',
            'discount_percent.max' => 'O desconto não pode ser superior a 100%.',

            'tax_rate_id.required' => 'A taxa de IVA é obrigatória.',
            'tax_rate_id.exists' => 'A taxa de IVA selecionada não existe.',

            'tax_exemption_reason_id.exists' => 'O motivo de isenção selecionado não existe.',

            'notes.string' => 'As observações devem ser texto.',
        ];
    }

    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'quantity' => round((float) $data['quantity'], 3),
            'unit_price' => round((float) $data['unit_price'], 2),
            'discount_percent' => round((float) ($data['discount_percent'] ?? 0), 2),
            'tax_rate_id' => (int) $data['tax_rate_id'],
            'tax_exemption_reason_id' => !empty($data['tax_exemption_reason_id']) ? (int) $data['tax_exemption_reason_id'] : null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
