@extends('layouts.admin')

@section('title', 'Novo Orçamento')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Novo Orçamento</h2>

        <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
            Voltar
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">
                Não foi possível guardar o orçamento. Corrige os seguintes erros:
            </div>

            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('budgets.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Dados do orçamento</strong>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="customer_search" class="form-label">Cliente *</label>

                                <div class="position-relative">
                                    <input
                                        type="hidden"
                                        name="customer_id"
                                        id="customer_id"
                                        value="{{ old('customer_id') }}"
                                    >

                                    <input
                                        type="text"
                                        id="customer_search"
                                        class="form-control @error('customer_id') is-invalid @enderror"
                                        placeholder="Pesquisar por nome, ID, código ou NIF"
                                        autocomplete="off"
                                        value=""
                                    >

                                    <div
                                        id="customer_dropdown"
                                        class="list-group position-absolute w-100 shadow-sm d-none"
                                        style="z-index: 1050; max-height: 260px; overflow-y: auto;"
                                    ></div>
                                </div>

                                @error('customer_id')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="form-text">
                                    O orçamento é criado sempre em rascunho.
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="designation" class="form-label">Designação</label>

                                <input
                                    type="text"
                                    name="designation"
                                    id="designation"
                                    class="form-control @error('designation') is-invalid @enderror"
                                    value="{{ old('designation') }}"
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
                                    value="{{ old('budget_date', now()->toDateString()) }}"
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
                                    value="{{ old('zone') }}"
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
                                    value="{{ old('project_name') }}"
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
                                >{{ old('notes') }}</textarea>

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
                            <label class="form-label">Estado</label>
                            <input type="text" class="form-control" value="Rascunho" disabled>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                Criar orçamento
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @php
        $customerOptions = $customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'label' => '#' . $customer->id
                    . ' - ' . ($customer->code ?: '—')
                    . ' - ' . $customer->name
                    . ($customer->nif ? ' - NIF: ' . $customer->nif : ''),
                'search' => trim(
                    ($customer->name ?? '') . ' ' .
                    ($customer->id ?? '') . ' ' .
                    ($customer->code ?? '') . ' ' .
                    ($customer->nif ?? '')
                ),
            ];
        })->values();
    @endphp
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const hiddenInput = document.getElementById('customer_id');
    const searchInput = document.getElementById('customer_search');
    const dropdown = document.getElementById('customer_dropdown');

    if (!hiddenInput || !searchInput || !dropdown) {
        return;
    }

    const customers = @json($customerOptions);

    const normalize = (value) => {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    };

    const closeDropdown = () => {
        dropdown.classList.add('d-none');
        dropdown.innerHTML = '';
    };

    const selectCustomer = (customer) => {
        hiddenInput.value = customer.id;
        searchInput.value = customer.label;
        closeDropdown();
    };

    const renderDropdown = (items) => {
        dropdown.innerHTML = '';

        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'list-group-item text-muted';
            empty.textContent = 'Nenhum cliente encontrado.';
            dropdown.appendChild(empty);
            dropdown.classList.remove('d-none');
            return;
        }

        items.forEach((customer) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action';
            button.textContent = customer.label;

            button.addEventListener('click', function () {
                selectCustomer(customer);
            });

            dropdown.appendChild(button);
        });

        dropdown.classList.remove('d-none');
    };

    const filterCustomers = () => {
        const term = normalize(searchInput.value);

        if (term === '') {
            renderDropdown(customers.slice(0, 20));
            return;
        }

        const filtered = customers
            .filter(customer => normalize(customer.search).includes(term))
            .slice(0, 20);

        renderDropdown(filtered);
    };

    searchInput.addEventListener('focus', filterCustomers);

    searchInput.addEventListener('input', function () {
        hiddenInput.value = '';
        filterCustomers();
    });

    searchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDropdown();
        }
    });

    document.addEventListener('click', function (event) {
        const clickedInside = searchInput.contains(event.target) || dropdown.contains(event.target);

        if (!clickedInside) {
            closeDropdown();
        }
    });
});
</script>
@endpush
