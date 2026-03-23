<?php

namespace App\Http\Requests\Budgets;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('budgets.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'designation' => $this->filled('designation') ? trim((string) $this->input('designation')) : null,
            'zone' => $this->filled('zone') ? trim((string) $this->input('zone')) : null,
            'project_name' => $this->filled('project_name') ? trim((string) $this->input('project_name')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'status' => $this->filled('status') ? $this->input('status') : 'draft',
            'budget_date' => $this->filled('budget_date') ? $this->input('budget_date') : now()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'designation' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:draft,sent,approved,rejected'],
            'budget_date' => ['required', 'date'],
            'zone' => ['nullable', 'string', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'É obrigatório selecionar um cliente.',
            'customer_id.exists' => 'O cliente selecionado não existe.',
            'designation.max' => 'A designação não pode ter mais de 255 caracteres.',
            'status.required' => 'O estado é obrigatório.',
            'status.in' => 'O estado selecionado é inválido.',
            'budget_date.required' => 'A data do orçamento é obrigatória.',
            'budget_date.date' => 'A data do orçamento é inválida.',
            'zone.max' => 'A zona não pode ter mais de 255 caracteres.',
            'project_name.max' => 'O projeto não pode ter mais de 255 caracteres.',
        ];
    }
}
