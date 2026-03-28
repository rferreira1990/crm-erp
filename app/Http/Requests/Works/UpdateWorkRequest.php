<?php

namespace App\Http\Requests\Works;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWorkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalize($this->input('name')),
            'work_type' => $this->normalize($this->input('work_type')),
            'location' => $this->normalize($this->input('location')),
            'postal_code' => $this->normalize($this->input('postal_code')),
            'city' => $this->normalize($this->input('city')),
            'description' => $this->normalize($this->input('description')),
            'internal_notes' => $this->normalize($this->input('internal_notes')),
            'other_costs' => $this->normalizeDecimal($this->input('other_costs')),
            'budget_id' => $this->normalizeInteger($this->input('budget_id')),
            'technical_manager_id' => $this->normalizeInteger($this->input('technical_manager_id')),
            'customer_id' => $this->normalizeInteger($this->input('customer_id')),
            'team' => array_values(array_filter(
                (array) $this->input('team', []),
                fn ($value) => trim((string) $value) !== ''
            )),
        ]);
    }

    public function rules(): array
    {
        $customerId = $this->input('customer_id');

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id'),
            ],
            'budget_id' => [
                'nullable',
                'integer',
                Rule::exists('budgets', 'id')->where(function ($query) use ($customerId) {
                    if ($customerId) {
                        $query->where('customer_id', $customerId);
                    }
                }),
            ],
            'technical_manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
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
            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'other_costs' => ['nullable', 'numeric', 'min:0'],
            'team' => ['nullable', 'array'],
            'team.*' => ['integer', 'distinct', Rule::exists('users', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
                        'O responsavel tecnico selecionado nao e valido para a obra.'
                    );
                }
            }

            foreach ((array) $this->input('team', []) as $userId) {
                if (! in_array((int) $userId, $validUserIds, true)) {
                    $validator->errors()->add(
                        'team',
                        'A equipa contem utilizadores invalidos para esta obra.'
                    );
                    break;
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'E obrigatorio selecionar um cliente.',
            'customer_id.exists' => 'O cliente selecionado nao e valido.',
            'budget_id.exists' => 'O orcamento selecionado nao e valido para o cliente escolhido.',
            'name.required' => 'O nome da obra e obrigatorio.',
            'name.max' => 'O nome da obra nao pode ter mais de 255 caracteres.',
            'work_type.max' => 'O tipo de obra nao pode ter mais de 100 caracteres.',
            'location.max' => 'O local nao pode ter mais de 255 caracteres.',
            'postal_code.max' => 'O codigo postal nao pode ter mais de 20 caracteres.',
            'city.max' => 'A cidade nao pode ter mais de 120 caracteres.',
            'end_date_planned.after_or_equal' => 'A data de fim prevista deve ser igual ou posterior a data de inicio prevista.',
            'end_date_actual.after_or_equal' => 'A data de fim real deve ser igual ou posterior a data de inicio real.',
            'other_costs.min' => 'Os outros custos nao podem ser negativos.',
            'team.array' => 'A equipa associada e invalida.',
            'team.*.integer' => 'Um dos elementos da equipa nao e valido.',
            'team.*.distinct' => 'Existem utilizadores repetidos na equipa.',
            'team.*.exists' => 'Um dos utilizadores selecionados para a equipa nao e valido.',
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

    private function normalizeDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }
}
