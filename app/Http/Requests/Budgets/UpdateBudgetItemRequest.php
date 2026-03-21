<?php

namespace App\Http\Requests\Budgets;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetItemRequest extends FormRequest
{
    /**
     * Autorização para editar linhas do orçamento.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('budgets.update') ?? false;
    }

    /**
     * Preparação de dados antes da validação.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'quantity' => $this->filled('quantity') ? $this->input('quantity') : 1,
            'unit_price' => $this->filled('unit_price') ? $this->input('unit_price') : 0,
            'discount_percent' => $this->filled('discount_percent') ? $this->input('discount_percent') : 0,
            'tax_percent' => $this->filled('tax_percent') ? $this->input('tax_percent') : 0,
            'tax_exemption_reason' => $this->filled('tax_exemption_reason') ? trim((string) $this->input('tax_exemption_reason')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_exemption_reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Mensagens personalizadas.
     */
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

            'tax_percent.required' => 'A taxa de IVA é obrigatória.',
            'tax_percent.numeric' => 'A taxa de IVA deve ser numérica.',
            'tax_percent.min' => 'A taxa de IVA não pode ser negativa.',
            'tax_percent.max' => 'A taxa de IVA não pode ser superior a 100%.',

            'tax_exemption_reason.string' => 'O motivo de isenção deve ser texto.',
            'tax_exemption_reason.max' => 'O motivo de isenção não pode ter mais de 255 caracteres.',

            'notes.string' => 'As observações devem ser texto.',
        ];
    }

    /**
     * Dados validados normalizados.
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'quantity' => round((float) $data['quantity'], 3),
            'unit_price' => round((float) $data['unit_price'], 2),
            'discount_percent' => round((float) ($data['discount_percent'] ?? 0), 2),
            'tax_percent' => round((float) ($data['tax_percent'] ?? 0), 2),
            'tax_exemption_reason' => $data['tax_exemption_reason'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];
    }
}
