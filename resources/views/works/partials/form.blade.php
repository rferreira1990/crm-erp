@php
    $selectedTeam = old(
        'team',
        isset($work) && $work ? $work->team->pluck('id')->map(fn ($id) => (string) $id)->all() : []
    );

    $selectedCustomerId = (string) old('customer_id', $work->customer_id ?? '');
    $selectedBudgetId = (string) old('budget_id', $work->budget_id ?? '');
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Existem erros no formulário:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="customer_id" class="form-label">Cliente <span class="text-danger">*</span></label>
        <select name="customer_id" id="customer_id" class="form-select" required>
            <option value="">Seleciona um cliente</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}"
                    {{ (string) old('customer_id', $work->customer_id ?? '') === (string) $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Nome da obra <span class="text-danger">*</span></label>
        <input
            type="text"
            name="name"
            id="name"
            class="form-control"
            maxlength="255"
            required
            value="{{ old('name', $work->name ?? '') }}"
        >
    </div>

    <div class="col-md-6 mb-3">
        <label for="work_type" class="form-label">Tipo de obra</label>
        <input
            type="text"
            name="work_type"
            id="work_type"
            class="form-control"
            maxlength="100"
            placeholder="Ex.: Instalação elétrica, manutenção, avaria..."
            value="{{ old('work_type', $work->work_type ?? '') }}"
        >
    </div>

    <div class="col-md-6 mb-3">
        <label for="technical_manager_id" class="form-label">Responsável técnico</label>
        <select name="technical_manager_id" id="technical_manager_id" class="form-select">
            <option value="">Seleciona um utilizador</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}"
                    {{ (string) old('technical_manager_id', $work->technical_manager_id ?? '') === (string) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label for="budget_id" class="form-label">Orçamento associado</label>
        <select
            name="budget_id"
            id="budget_id"
            class="form-select"
            data-selected-budget-id="{{ $selectedBudgetId }}"
            data-selected-customer-id="{{ $selectedCustomerId }}"
        >
            <option value="">Sem orçamento associado</option>
            @foreach ($budgets as $budget)
                <option
                    value="{{ $budget->id }}"
                    data-customer-id="{{ $budget->customer_id }}"
                    {{ $selectedBudgetId === (string) $budget->id ? 'selected' : '' }}
                >
                    {{ $budget->code }}{{ $budget->designation ? ' - ' . $budget->designation : '' }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Só aparecem orçamentos do cliente selecionado.</small>
    </div>

    <div class="col-md-6 mb-3">
        <label for="location" class="form-label">Local</label>
        <input
            type="text"
            name="location"
            id="location"
            class="form-control"
            maxlength="255"
            value="{{ old('location', $work->location ?? '') }}"
        >
    </div>

    <div class="col-md-3 mb-3">
        <label for="postal_code" class="form-label">Código Postal</label>
        <input
            type="text"
            name="postal_code"
            id="postal_code"
            class="form-control"
            maxlength="20"
            value="{{ old('postal_code', $work->postal_code ?? '') }}"
        >
    </div>

    <div class="col-md-3 mb-3">
        <label for="city" class="form-label">Cidade</label>
        <input
            type="text"
            name="city"
            id="city"
            class="form-control"
            maxlength="120"
            value="{{ old('city', $work->city ?? '') }}"
        >
    </div>

    <div class="col-md-3 mb-3">
        <label for="start_date_planned" class="form-label">Início previsto</label>
        <input
            type="date"
            name="start_date_planned"
            id="start_date_planned"
            class="form-control"
            value="{{ old('start_date_planned', isset($work->start_date_planned) ? $work->start_date_planned->format('Y-m-d') : '') }}"
        >
    </div>

    <div class="col-md-3 mb-3">
        <label for="end_date_planned" class="form-label">Fim previsto</label>
        <input
            type="date"
            name="end_date_planned"
            id="end_date_planned"
            class="form-control"
            value="{{ old('end_date_planned', isset($work->end_date_planned) ? $work->end_date_planned->format('Y-m-d') : '') }}"
        >
    </div>

    <div class="col-md-3 mb-3">
        <label for="start_date_actual" class="form-label">Início real</label>
        <input
            type="date"
            name="start_date_actual"
            id="start_date_actual"
            class="form-control"
            value="{{ old('start_date_actual', isset($work->start_date_actual) ? $work->start_date_actual->format('Y-m-d') : '') }}"
        >
    </div>

    <div class="col-md-3 mb-3">
        <label for="end_date_actual" class="form-label">Fim real</label>
        <input
            type="date"
            name="end_date_actual"
            id="end_date_actual"
            class="form-control"
            value="{{ old('end_date_actual', isset($work->end_date_actual) ? $work->end_date_actual->format('Y-m-d') : '') }}"
        >
    </div>

    <div class="col-12 mb-3">
        <label for="team" class="form-label">Equipa associada</label>
        <select name="team[]" id="team" class="form-select" multiple size="6">
            @foreach ($users as $user)
                <option value="{{ $user->id }}"
                    {{ in_array((string) $user->id, $selectedTeam, true) ? 'selected' : '' }}>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Podes selecionar vários utilizadores com Ctrl ou Cmd.</small>
    </div>

    <div class="col-12 mb-3">
        <label for="description" class="form-label">Descrição</label>
        <textarea
            name="description"
            id="description"
            rows="4"
            class="form-control"
        >{{ old('description', $work->description ?? '') }}</textarea>
    </div>

    <div class="col-12 mb-3">
        <label for="internal_notes" class="form-label">Notas internas</label>
        <textarea
            name="internal_notes"
            id="internal_notes"
            rows="4"
            class="form-control"
        >{{ old('internal_notes', $work->internal_notes ?? '') }}</textarea>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerSelect = document.getElementById('customer_id');
    const budgetSelect = document.getElementById('budget_id');

    if (!customerSelect || !budgetSelect) {
        return;
    }

    const budgetOptions = Array.from(budgetSelect.querySelectorAll('option'));
    const defaultOption = budgetOptions.find(option => option.value === '');

    function filterBudgets() {
        const selectedCustomerId = customerSelect.value;
        const selectedBudgetId = budgetSelect.dataset.selectedBudgetId || budgetSelect.value;

        budgetSelect.innerHTML = '';

        if (defaultOption) {
            budgetSelect.appendChild(defaultOption.cloneNode(true));
        }

        budgetOptions.forEach(function (option) {
            if (option.value === '') {
                return;
            }

            const optionCustomerId = option.dataset.customerId || '';

            if (selectedCustomerId !== '' && optionCustomerId === selectedCustomerId) {
                const clone = option.cloneNode(true);

                if (clone.value === selectedBudgetId) {
                    clone.selected = true;
                }

                budgetSelect.appendChild(clone);
            }
        });

        const hasSelectedBudget = Array.from(budgetSelect.options).some(function (option) {
            return option.value === selectedBudgetId;
        });

        if (!hasSelectedBudget) {
            budgetSelect.value = '';
        }
    }

    customerSelect.addEventListener('change', function () {
        budgetSelect.dataset.selectedBudgetId = '';
        filterBudgets();
    });

    filterBudgets();
});
</script>
@endpush
