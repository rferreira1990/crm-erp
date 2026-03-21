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
            'discount_percent' => $this->filled('discount_percent') ? $this->input('discount_percent') : 0,
        ]);
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'gt:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
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
            'discount_percent.numeric' => 'O desconto deve ser numérico.',
            'discount_percent.min' => 'O desconto não pode ser negativo.',
            'discount_percent.max' => 'O desconto não pode ser superior a 100%.',
        ];
    }

    /**
     * Dados validados normalizados.
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'quantity' => (float) $data['quantity'],
            'discount_percent' => (float) ($data['discount_percent'] ?? 0),
        ];
    }
}
