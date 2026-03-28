@php
    $selectedCustomerId = old('customer_id', $work?->customer_id);
    $selectedBudgetId = old('budget_id', $work?->budget_id);
    $selectedTechnicalManagerId = old('technical_manager_id', $work?->technical_manager_id);
    $selectedTeam = collect(old('team', $work?->team?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="customer_id" class="form-label">Cliente <span class="text-danger">*</span></label>
        <select
            name="customer_id"
            id="customer_id"
            class="form-control @error('customer_id') is-invalid @enderror"
            required
        >
            <option value="">Selecionar...</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((int) $selectedCustomerId === (int) $customer->id)>
                    {{ $customer->name }}
                </option>
            @endforeach
        </select>
        @error('customer_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="budget_id" class="form-label">Orçamento associado</label>
        <select
            name="budget_id"
            id="budget_id"
            class="form-control @error('budget_id') is-invalid @enderror"
        >
            <option value="">Sem orçamento associado</option>
            @foreach ($budgets as $budget)
                <option
                    value="{{ $budget->id }}"
                    data-customer-id="{{ $budget->customer_id }}"
                    @selected((int) $selectedBudgetId === (int) $budget->id)
                >
                    {{ $budget->code }} - {{ $budget->designation ?: 'Sem designação' }}
                </option>
            @endforeach
        </select>
        @error('budget_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-8 mb-3">
        <label for="name" class="form-label">Nome da obra <span class="text-danger">*</span></label>
        <input
            type="text"
            name="name"
            id="name"
            value="{{ old('name', $work?->name) }}"
            class="form-control @error('name') is-invalid @enderror"
            required
        >
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="work_type" class="form-label">Tipo de obra</label>
        <input
            type="text"
            name="work_type"
            id="work_type"
            value="{{ old('work_type', $work?->work_type) }}"
            class="form-control @error('work_type') is-invalid @enderror"
        >
        @error('work_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="technical_manager_id" class="form-label">Responsável técnico</label>
        <select
            name="technical_manager_id"
            id="technical_manager_id"
            class="form-control @error('technical_manager_id') is-invalid @enderror"
        >
            <option value="">Selecionar...</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((int) $selectedTechnicalManagerId === (int) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('technical_manager_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="team" class="form-label">Equipa</label>
        <select
            name="team[]"
            id="team"
            class="form-control @error('team') is-invalid @enderror @error('team.*') is-invalid @enderror"
            multiple
            size="8"
        >
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(in_array((int) $user->id, $selectedTeam, true))>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        <small class="form-text text-muted">
            Usa Ctrl (ou Cmd) para selecionar vários utilizadores.
        </small>
        @error('team')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        @error('team.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="location" class="form-label">Local</label>
        <input
            type="text"
            name="location"
            id="location"
            value="{{ old('location', $work?->location) }}"
            class="form-control @error('location') is-invalid @enderror"
        >
        @error('location')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="postal_code" class="form-label">Código Postal</label>
        <input
            type="text"
            name="postal_code"
            id="postal_code"
            value="{{ old('postal_code', $work?->postal_code) }}"
            class="form-control @error('postal_code') is-invalid @enderror"
        >
        @error('postal_code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="city" class="form-label">Cidade</label>
        <input
            type="text"
            name="city"
            id="city"
            value="{{ old('city', $work?->city) }}"
            class="form-control @error('city') is-invalid @enderror"
        >
        @error('city')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="start_date_planned" class="form-label">Início previsto</label>
        <input
            type="date"
            name="start_date_planned"
            id="start_date_planned"
            value="{{ old('start_date_planned', optional($work?->start_date_planned)->format('Y-m-d')) }}"
            class="form-control @error('start_date_planned') is-invalid @enderror"
        >
        @error('start_date_planned')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="end_date_planned" class="form-label">Fim previsto</label>
        <input
            type="date"
            name="end_date_planned"
            id="end_date_planned"
            value="{{ old('end_date_planned', optional($work?->end_date_planned)->format('Y-m-d')) }}"
            class="form-control @error('end_date_planned') is-invalid @enderror"
        >
        @error('end_date_planned')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="start_date_actual" class="form-label">Início real</label>
        <input
            type="date"
            name="start_date_actual"
            id="start_date_actual"
            value="{{ old('start_date_actual', optional($work?->start_date_actual)->format('Y-m-d')) }}"
            class="form-control @error('start_date_actual') is-invalid @enderror"
        >
        @error('start_date_actual')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="end_date_actual" class="form-label">Fim real</label>
        <input
            type="date"
            name="end_date_actual"
            id="end_date_actual"
            value="{{ old('end_date_actual', optional($work?->end_date_actual)->format('Y-m-d')) }}"
            class="form-control @error('end_date_actual') is-invalid @enderror"
        >
        @error('end_date_actual')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="description" class="form-label">Descrição</label>
        <textarea
            name="description"
            id="description"
            rows="5"
            class="form-control @error('description') is-invalid @enderror"
        >{{ old('description', $work?->description) }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="internal_notes" class="form-label">Notas internas</label>
        <textarea
            name="internal_notes"
            id="internal_notes"
            rows="5"
            class="form-control @error('internal_notes') is-invalid @enderror"
        >{{ old('internal_notes', $work?->internal_notes) }}</textarea>
        @error('internal_notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
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

    function filterBudgetsByCustomer() {
        const customerId = customerSelect.value;
        const selectedBudget = budgetSelect.value;

        Array.from(budgetSelect.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            const optionCustomerId = option.dataset.customerId || '';
            const shouldShow = !customerId || optionCustomerId === customerId;

            option.hidden = !shouldShow;

            if (!shouldShow && option.value === selectedBudget) {
                budgetSelect.value = '';
            }
        });
    }

    customerSelect.addEventListener('change', filterBudgetsByCustomer);
    filterBudgetsByCustomer();
});
</script>
@endpush
