<?php

namespace App\Http\Requests\Suppliers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalize($this->input('name')),
            'role' => $this->normalize($this->input('role')),
            'department' => $this->normalize($this->input('department')),
            'email' => $this->normalizeEmail($this->input('email')),
            'phone' => $this->normalize($this->input('phone')),
            'mobile' => $this->normalize($this->input('mobile')),
            'notes' => $this->normalize($this->input('notes')),
            'is_primary' => $this->boolean('is_primary'),
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'role' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'is_primary' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome do contacto',
            'role' => 'funcao',
            'department' => 'departamento',
            'email' => 'email',
            'phone' => 'telefone',
            'mobile' => 'telemovel',
            'notes' => 'notas',
            'is_primary' => 'contacto principal',
            'is_active' => 'ativo',
        ];
    }

    private function normalize(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtolower($value);
    }
}

