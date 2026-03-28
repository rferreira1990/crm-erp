<?php

namespace App\Http\Requests\Works;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => $this->normalize($this->input('description')),
            'assigned_user_id' => $this->normalizeInteger($this->input('assigned_user_id')),
            'sort_order' => $this->normalizeInteger($this->input('sort_order')),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(array_keys(WorkTask::statuses()))],
            'assigned_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'planned_date' => ['nullable', 'date'],
            'planned_start_time' => ['nullable', 'date_format:H:i'],
            'planned_end_time' => ['nullable', 'date_format:H:i'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $assignedUserId = $this->input('assigned_user_id');

            if ($assignedUserId) {
                $isAssignable = User::query()
                    ->assignableToWorks()
                    ->whereKey((int) $assignedUserId)
                    ->exists();

                if (! $isAssignable) {
                    $validator->errors()->add('assigned_user_id', 'O utilizador selecionado nao e valido para tarefas de obras.');
                }
            }

            $start = $this->input('planned_start_time');
            $end = $this->input('planned_end_time');

            if ($start && $end && $end <= $start) {
                $validator->errors()->add('planned_end_time', 'A hora fim prevista deve ser posterior a hora inicio prevista.');
            }
        });
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
