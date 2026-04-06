@php
    $selectedCustomerId = old('customer_id', $receivable->customer_id);
    $selectedStatus = old('status', $receivable->status ?? \App\Models\CustomerReceivable::STATUS_DRAFT);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="customer_id" class="form-label">Cliente</label>
        <select id="customer_id" name="customer_id" class="form-select" required>
            <option value="">Selecionar cliente...</option>
            @foreach ($customers as $customer)
                <option
                    value="{{ $customer->id }}"
                    data-payment-terms-days="{{ (int) ($customer->payment_terms_days ?? 0) }}"
                    @selected((int) $selectedCustomerId === (int) $customer->id)
                >
                    {{ $customer->code ? $customer->code . ' - ' . $customer->name : $customer->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-2">
        <label for="issue_date" class="form-label">Data emissao</label>
        <input
            type="date"
            id="issue_date"
            name="issue_date"
            class="form-control"
            value="{{ old('issue_date', optional($receivable->issue_date)->toDateString() ?? now()->toDateString()) }}"
            required
        >
    </div>

    <div class="col-md-2">
        <label for="due_date" class="form-label">Data vencimento</label>
        <input
            type="date"
            id="due_date"
            name="due_date"
            class="form-control"
            value="{{ old('due_date', optional($receivable->due_date)->toDateString() ?? now()->toDateString()) }}"
            required
        >
    </div>

    <div class="col-md-2">
        <label for="amount" class="form-label">Valor</label>
        <input
            type="number"
            id="amount"
            name="amount"
            class="form-control"
            min="0.01"
            max="999999999999.99"
            step="0.01"
            value="{{ old('amount', isset($receivable->amount) ? number_format((float) $receivable->amount, 2, '.', '') : '') }}"
            required
        >
    </div>

    @if (isset($creatableStatuses) && is_array($creatableStatuses))
        <div class="col-md-3">
            <label for="status" class="form-label">Estado inicial</label>
            <select id="status" name="status" class="form-select" required>
                @foreach ($creatableStatuses as $statusKey => $statusLabel)
                    <option value="{{ $statusKey }}" @selected($selectedStatus === $statusKey)>{{ $statusLabel }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-md-{{ isset($creatableStatuses) ? '9' : '12' }}">
        <label for="description" class="form-label">Descricao</label>
        <input
            type="text"
            id="description"
            name="description"
            class="form-control"
            maxlength="255"
            value="{{ old('description', $receivable->description) }}"
            placeholder="Ex: Servicos executados em obra X"
            required
        >
    </div>

    <div class="col-md-6">
        <label for="reference_type" class="form-label">Tipo referencia (opcional)</label>
        <input
            type="text"
            id="reference_type"
            name="reference_type"
            class="form-control"
            maxlength="100"
            value="{{ old('reference_type', $receivable->reference_type) }}"
            placeholder="work, budget, documento interno..."
        >
    </div>

    <div class="col-md-6">
        <label for="reference_id" class="form-label">ID referencia (opcional)</label>
        <input
            type="number"
            id="reference_id"
            name="reference_id"
            class="form-control"
            min="1"
            step="1"
            value="{{ old('reference_id', $receivable->reference_id) }}"
        >
    </div>

    <div class="col-12">
        <label for="notes" class="form-label">Notas</label>
        <textarea id="notes" name="notes" rows="3" class="form-control">{{ old('notes', $receivable->notes) }}</textarea>
        <div class="form-text">Documento interno operacional. Nao gera faturacao AT.</div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const customerSelect = document.getElementById('customer_id');
        const issueDateInput = document.getElementById('issue_date');
        const dueDateInput = document.getElementById('due_date');

        if (!customerSelect || !issueDateInput || !dueDateInput) {
            return;
        }

        const dueDateTouched = () => dueDateInput.dataset.touched === '1';

        dueDateInput.addEventListener('change', function () {
            dueDateInput.dataset.touched = '1';
        });

        customerSelect.addEventListener('change', function () {
            if (dueDateTouched()) {
                return;
            }

            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
            if (!selectedOption) {
                return;
            }

            const days = Number(selectedOption.dataset.paymentTermsDays || 0);
            if (Number.isNaN(days)) {
                return;
            }

            const baseDate = issueDateInput.value ? new Date(issueDateInput.value + 'T00:00:00') : new Date();
            if (Number.isNaN(baseDate.getTime())) {
                return;
            }

            baseDate.setDate(baseDate.getDate() + Math.max(0, days));
            const yyyy = baseDate.getFullYear();
            const mm = String(baseDate.getMonth() + 1).padStart(2, '0');
            const dd = String(baseDate.getDate()).padStart(2, '0');

            dueDateInput.value = `${yyyy}-${mm}-${dd}`;
        });
    });
</script>
