<?php

namespace App\Http\Requests\Works;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id'),
            ],

            'budget_id' => [
                'nullable',
                'integer',
                Rule::exists('budgets', 'id'),
            ],

            'name' => ['required', 'string', 'max:255'],

            'work_type' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:120'],

            'start_date_planned' => ['nullable', 'date'],
            'end_date_planned' => ['nullable', 'date', 'after_or_equal:start_date_planned'],
            'start_date_actual' => ['nullable', 'date'],
            'end_date_actual' => ['nullable', 'date', 'after_or_equal:start_date_actual'],

            'technical_manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],

            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'other_costs' => ['nullable', 'numeric', 'min:0'],

            'team' => ['nullable', 'array'],
            'team.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id'),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'budget_id' => 'orçamento',
            'name' => 'nome',
            'work_type' => 'tipo de obra',
            'location' => 'local',
            'postal_code' => 'código postal',
            'city' => 'cidade',
            'start_date_planned' => 'data de início prevista',
            'end_date_planned' => 'data de fim prevista',
            'start_date_actual' => 'data de início real',
            'end_date_actual' => 'data de fim real',
            'technical_manager_id' => 'responsável técnico',
            'description' => 'descrição',
            'internal_notes' => 'notas internas',
            'other_costs' => 'outros custos',
            'team' => 'equipa',
            'team.*' => 'elemento da equipa',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('budget_id') && $this->filled('customer_id')) {
                $budgetBelongsToCustomer = Budget::query()
                    ->whereKey((int) $this->input('budget_id'))
                    ->where('customer_id', (int) $this->input('customer_id'))
                    ->exists();

                if (! $budgetBelongsToCustomer) {
                    $validator->errors()->add(
                        'budget_id',
                        'O orçamento selecionado não pertence ao cliente escolhido.'
                    );
                }
            }

            $validUserIds = User::query()
                ->assignableToWorks()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $technicalManagerId = $this->input('technical_manager_id');

            if ($technicalManagerId !== null && $technicalManagerId !== '') {
                if (! in_array((int) $technicalManagerId, $validUserIds, true)) {
                    $validator->errors()->add(
                        'technical_manager_id',
                        'O responsável técnico selecionado não é válido para a obra.'
                    );
                }
            }

            foreach ((array) $this->input('team', []) as $userId) {
                if (! in_array((int) $userId, $validUserIds, true)) {
                    $validator->errors()->add(
                        'team',
                        'A equipa contém utilizadores inválidos para esta obra.'
                    );
                    break;
                }
            }
        });
    }
}
