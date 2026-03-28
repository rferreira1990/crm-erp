<?php

namespace App\Http\Requests\Works;

use App\Models\User;
use App\Models\WorkExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWorkExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('works.update') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => trim((string) $this->input('type')),
            'description' => $this->normalize($this->input('description')),
            'user_id' => $this->normalizeInteger($this->input('user_id')),
            'supplier_name' => $this->normalize($this->input('supplier_name')),
            'receipt_number' => $this->normalize($this->input('receipt_number')),
            'qty' => $this->normalizeDecimal($this->input('qty')),
            'unit_cost' => $this->normalizeDecimal($this->input('unit_cost')),
            'total_cost' => $this->normalizeDecimal($this->input('total_cost')),
            'km' => $this->normalizeDecimal($this->input('km')),
            'from_location' => $this->normalize($this->input('from_location')),
            'to_location' => $this->normalize($this->input('to_location')),
            'notes' => $this->normalize($this->input('notes')),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(WorkExpense::types()))],
            'expense_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'qty' => ['nullable', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'km' => ['nullable', 'numeric', 'gt:0'],
            'from_location' => ['nullable', 'string', 'max:255'],
            'to_location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('type');
            $userId = $this->input('user_id');
            $qty = $this->input('qty');
            $unitCost = $this->input('unit_cost');
            $totalCost = $this->input('total_cost');
            $km = $this->input('km');
            $supplierName = $this->input('supplier_name');

            if ($userId) {
                $isAllowed = User::query()
                    ->assignableToWorks()
                    ->whereKey((int) $userId)
                    ->exists();

                if (! $isAllowed) {
                    $validator->errors()->add('user_id', 'O utilizador associado nao e valido para obras.');
                }
            }

            if ($type === WorkExpense::TYPE_TRAVEL_KM) {
                if (! $km) {
                    $validator->errors()->add('km', 'No tipo deslocacao km, os km sao obrigatorios.');
                }

                if ($unitCost === null) {
                    $validator->errors()->add('unit_cost', 'No tipo deslocacao km, o custo por km e obrigatorio.');
                }
            } elseif ($totalCost === null && ! ($qty && $unitCost !== null)) {
                $validator->errors()->add('total_cost', 'Indica custo total ou quantidade + custo unitario.');
            }

            if ($type === WorkExpense::TYPE_SUBCONTRACT && ! $supplierName) {
                $validator->errors()->add('supplier_name', 'No tipo subempreitada, o fornecedor e obrigatorio.');
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
