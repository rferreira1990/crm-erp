<?php

namespace App\Http\Requests\Users;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.edit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => strtolower(trim((string) $this->input('email'))),
            'job_title' => $this->normalize($this->input('job_title')),
            'hourly_cost' => $this->normalizeDecimal($this->input('hourly_cost')),
            'hourly_sale_price' => $this->normalizeDecimal($this->input('hourly_sale_price')),
            'is_labor_enabled' => $this->boolean('is_labor_enabled', true),
            'is_active' => $this->boolean('is_active', true),
            'roles' => array_values(array_filter((array) $this->input('roles', []))),
            'permissions' => array_values(array_filter((array) $this->input('permissions', []))),
        ]);
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'hourly_cost' => ['nullable', 'numeric', 'min:0'],
            'hourly_sale_price' => ['nullable', 'numeric', 'min:0'],
            'is_labor_enabled' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ];
    }

    private function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
