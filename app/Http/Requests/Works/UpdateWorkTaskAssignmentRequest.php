<?php

namespace App\Http\Requests\Works;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWorkTaskAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->normalizeInteger($this->input('user_id')),
            'role_snapshot' => $this->normalize($this->input('role_snapshot')),
            'hourly_cost_snapshot' => $this->normalizeDecimal($this->input('hourly_cost_snapshot')),
            'hourly_sale_price_snapshot' => $this->normalizeDecimal($this->input('hourly_sale_price_snapshot')),
            'worked_hours' => $this->normalizeDecimal($this->input('worked_hours')),
            'worked_minutes' => $this->normalizeInteger($this->input('worked_minutes')),
            'notes' => $this->normalize($this->input('notes')),
        ]);

        if (! $this->input('worked_minutes') && $this->input('worked_hours')) {
            $this->merge([
                'worked_minutes' => max(1, (int) round((float) $this->input('worked_hours') * 60)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'role_snapshot' => ['nullable', 'string', 'max:120'],
            'hourly_cost_snapshot' => ['required', 'numeric', 'min:0'],
            'hourly_sale_price_snapshot' => ['nullable', 'numeric', 'min:0'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'worked_hours' => ['nullable', 'numeric', 'min:0.01', 'max:24'],
            'worked_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $userId = $this->input('user_id');
            $start = $this->input('start_time');
            $end = $this->input('end_time');
            $workedHours = $this->input('worked_hours');
            $workedMinutes = $this->input('worked_minutes');

            if ($userId) {
                $isAllowed = User::query()
                    ->assignableToWorkLabor()
                    ->whereKey((int) $userId)
                    ->exists();

                if (! $isAllowed) {
                    $validator->errors()->add('user_id', 'O utilizador selecionado nao e valido para mao de obra de obras.');
                }
            }

            if (($start && ! $end) || (! $start && $end)) {
                $validator->errors()->add('start_time', 'Indica inicio e fim, ou apenas horas trabalhadas.');
            }

            if ($start && $end && $end <= $start) {
                $validator->errors()->add('end_time', 'A hora fim deve ser posterior a hora inicio.');
            }

            if (! ($start && $end) && ! $workedMinutes && ! $workedHours) {
                $validator->errors()->add('worked_hours', 'Indica horas trabalhadas quando nao defines inicio/fim.');
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

    private function normalizeDecimal(mixed $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }
}
