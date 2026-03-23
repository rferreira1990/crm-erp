<div class="row g-3">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Dados do orçamento</strong>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="customer_search" class="form-label">Pesquisar cliente</label>
                        <input
                            type="text"
                            id="customer_search"
                            class="form-control"
                            placeholder="Pesquisar por nome, ID, código ou NIF"
                        >
                        <div class="form-text">
                            Podes pesquisar por nome, ID, código ou NIF.
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label for="customer_id" class="form-label">Cliente *</label>

                        <select
                            name="customer_id"
                            id="customer_id"
                            class="form-select @error('customer_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Selecionar cliente</option>

                            @foreach ($customers as $customer)
                                <option
                                    value="{{ $customer->id }}"
                                    data-search="{{ mb_strtolower(trim(($customer->name ?? '') . ' ' . ($customer->id ?? '') . ' ' . ($customer->code ?? '') . ' ' . ($customer->nif ?? ''))) }}"
                                    {{ (string) old('customer_id', $budget->customer_id) === (string) $customer->id ? 'selected' : '' }}
                                >
                                    #{{ $customer->id }} - {{ $customer->code }} - {{ $customer->name }}{{ $customer->nif ? ' - NIF: ' . $customer->nif : '' }}
                                </option>
                            @endforeach
                        </select>

                        @error('customer_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="designation" class="form-label">Designação</label>

                        <input
                            type="text"
                            name="designation"
                            id="designation"
                            class="form-control @error('designation') is-invalid @enderror"
                            value="{{ old('designation', $budget->designation) }}"
                            maxlength="255"
                        >

                        @error('designation')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="budget_date" class="form-label">Data *</label>

                        <input
                            type="date"
                            name="budget_date"
                            id="budget_date"
                            class="form-control @error('budget_date') is-invalid @enderror"
                            value="{{ old('budget_date', optional($budget->budget_date)->format('Y-m-d') ?? now()->toDateString()) }}"
                            required
                        >

                        @error('budget_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="zone" class="form-label">Zona</label>

                        <input
                            type="text"
                            name="zone"
                            id="zone"
                            class="form-control @error('zone') is-invalid @enderror"
                            value="{{ old('zone', $budget->zone) }}"
                            maxlength="255"
                        >

                        @error('zone')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label for="project_name" class="form-label">Projeto</label>

                        <input
                            type="text"
                            name="project_name"
                            id="project_name"
                            class="form-control @error('project_name') is-invalid @enderror"
                            value="{{ old('project_name', $budget->project_name) }}"
                            maxlength="255"
                        >

                        @error('project_name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="notes" class="form-label">Notas</label>

                        <textarea
                            name="notes"
                            id="notes"
                            rows="4"
                            class="form-control @error('notes') is-invalid @enderror"
                        >{{ old('notes', $budget->notes) }}</textarea>

                        @error('notes')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <strong>Configuração</strong>
            </div>

            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Estado *</label>

                    <select
                        name="status"
                        id="status"
                        class="form-select @error('status') is-invalid @enderror"
                        required
                    >
                        <option value="draft" {{ old('status', $budget->status ?: 'draft') === 'draft' ? 'selected' : '' }}>
                            Rascunho
                        </option>
                        <option value="sent" {{ old('status', $budget->status) === 'sent' ? 'selected' : '' }}>
                            Enviado
                        </option>
                        <option value="approved" {{ old('status', $budget->status) === 'approved' ? 'selected' : '' }}>
                            Aprovado
                        </option>
                        <option value="rejected" {{ old('status', $budget->status) === 'rejected' ? 'selected' : '' }}>
                            Rejeitado
                        </option>
                    </select>

                    @error('status')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        {{ isset($isEdit) && $isEdit ? 'Guardar alterações' : 'Criar orçamento' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('customer_search');
    const customerSelect = document.getElementById('customer_id');

    if (!searchInput || !customerSelect) {
        return;
    }

    const normalize = (value) => {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    };

    const options = Array.from(customerSelect.options).filter(option => option.value !== '');

    const filterOptions = () => {
        const term = normalize(searchInput.value);

        options.forEach((option) => {
            const haystack = normalize(option.dataset.search || option.textContent);
            const matches = term === '' || haystack.includes(term);
            const keepVisible = matches || option.selected;

            option.hidden = !keepVisible;
            option.disabled = !keepVisible;
        });
    };

    searchInput.addEventListener('input', filterOptions);
    filterOptions();
});
</script>
@endpush
