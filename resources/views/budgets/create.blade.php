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
                        </div><br>
                         <div class="col-md-8">
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

    <div
        id="budget-customer-search-config"
        data-customer-options='@json($customerOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'
    ></div>
@endsection

@push('scripts')
    <script src="{{ asset('porto/js/pages/budget-customer-search.js') }}"></script>
@endpush
