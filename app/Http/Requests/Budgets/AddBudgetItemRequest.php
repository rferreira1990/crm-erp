<?php

namespace App\Http\Requests\Budgets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddBudgetItemRequest extends FormRequest
{
    /**
     * Autorização para adicionar linhas ao orçamento.
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
        $taxExemptionReasonId = $this->input('tax_exemption_reason_id');

        $this->merge([
            'quantity' => $this->filled('quantity') ? $this->input('quantity') : 1,
            'discount_percent' => $this->filled('discount_percent') ? $this->input('discount_percent') : 0,
            'tax_exemption_reason_id' => $taxExemptionReasonId !== '' && $taxExemptionReasonId !== null
                ? $taxExemptionReasonId
                : null,
        ]);
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            'item_id' => [
                'required',
                'integer',
                Rule::exists('items', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_rate_id' => ['nullable', 'integer', Rule::exists('tax_rates', 'id')],
            'tax_exemption_reason_id' => ['nullable', 'integer', Rule::exists('tax_exemption_reasons', 'id')],
        ];
    }

    /**
     * Mensagens personalizadas.
     */
    public function messages(): array
    {
        return [
            'item_id.required' => 'É obrigatório selecionar um artigo.',
            'item_id.exists' => 'O artigo selecionado não é válido.',
            'quantity.required' => 'A quantidade é obrigatória.',
            'quantity.numeric' => 'A quantidade deve ser numérica.',
            'quantity.gt' => 'A quantidade tem de ser superior a zero.',
            'discount_percent.numeric' => 'O desconto deve ser numérico.',
            'discount_percent.min' => 'O desconto não pode ser negativo.',
            'discount_percent.max' => 'O desconto não pode ser superior a 100%.',
            'tax_rate_id.integer' => 'A taxa de IVA selecionada é inválida.',
            'tax_rate_id.exists' => 'A taxa de IVA selecionada não existe.',
            'tax_exemption_reason_id.integer' => 'O motivo de isenção selecionado é inválido.',
            'tax_exemption_reason_id.exists' => 'O motivo de isenção selecionado não existe.',
        ];
    }

    /**
     * Devolve os dados validados já normalizados.
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'item_id' => (int) $data['item_id'],
            'quantity' => (float) $data['quantity'],
            'discount_percent' => (float) ($data['discount_percent'] ?? 0),
            'tax_rate_id' => isset($data['tax_rate_id']) ? (int) $data['tax_rate_id'] : null,
            'tax_exemption_reason_id' => isset($data['tax_exemption_reason_id']) ? (int) $data['tax_exemption_reason_id'] : null,
        ];
    }
}
