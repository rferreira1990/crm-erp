<?php

namespace App\Http\Requests\Budgets;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('budgets.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'designation' => $this->filled('designation') ? trim((string) $this->input('designation')) : null,
            'zone' => $this->filled('zone') ? trim((string) $this->input('zone')) : null,
            'project_name' => $this->filled('project_name') ? trim((string) $this->input('project_name')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'budget_date' => $this->filled('budget_date') ? $this->input('budget_date') : now()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'designation' => ['nullable', 'string', 'max:255'],
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
            'budget_date.required' => 'A data do orçamento é obrigatória.',
            'budget_date.date' => 'A data do orçamento é inválida.',
            'designation.max' => 'A designação não pode ter mais de 255 caracteres.',
            'zone.max' => 'A zona não pode ter mais de 255 caracteres.',
            'project_name.max' => 'O projeto não pode ter mais de 255 caracteres.',
        ];
    }
}
