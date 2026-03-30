@extends('layouts.admin')

@section('title', 'OrÃ§amentos')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">OrÃ§amentos</h2>

    @can('budgets.create')
        <a href="{{ route('budgets.create') }}" class="btn btn-primary">
            Novo OrÃ§amento
        </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('budgets.index') }}" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="CÃ³digo ou nome do cliente"
                    >
                </div>

                <div class="col-md-2 mb-3">
                    <label for="status" class="form-label">Estado</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="draft" {{ ($filters['status'] ?? '') === 'draft' ? 'selected' : '' }}>Rascunho</option>
                        <option value="created" {{ ($filters['status'] ?? '') === 'created' ? 'selected' : '' }}>Criado</option>
                        <option value="sent" {{ ($filters['status'] ?? '') === 'sent' ? 'selected' : '' }}>Enviado</option>
                        <option value="waiting_response" {{ ($filters['status'] ?? '') === 'waiting_response' ? 'selected' : '' }}>Aguarda resposta</option>
                        <option value="accepted" {{ ($filters['status'] ?? '') === 'accepted' ? 'selected' : '' }}>Aceite</option>
                        <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>NÃ£o aceite</option>
                    </select>
                </div>

                <div class="col-md-3 mb-3">
                    <label for="root_budget_id" class="form-label">Orcamento base</label>
                    <select name="root_budget_id" id="root_budget_id" class="form-select">
                        <option value="">Todos</option>
                        @foreach(($rootBudgetOptions ?? collect()) as $rootBudget)
                            <option
                                value="{{ $rootBudget->id }}"
                                {{ (int) ($filters['root_budget_id'] ?? 0) === (int) $rootBudget->id ? 'selected' : '' }}
                            >
                                {{ $rootBudget->code }}{{ !empty($rootBudget->customer?->name) ? ' - ' . $rootBudget->customer->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-3">
                    <label for="date_from" class="form-label">Data inÃ­cio</label>
                    <input
                        type="date"
                        name="date_from"
                        id="date_from"
                        class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"
                    >
                </div>

                <div class="col-md-2 mb-3">
                    <label for="date_to" class="form-label">Data fim</label>
                    <input
                        type="date"
                        name="date_to"
                        id="date_to"
                        class="form-control"
                        value="{{ $filters['date_to'] ?? '' }}"
                    >
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    Filtrar
                </button>

                <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary btn-sm">
                    Limpar filtros
                </a>
            </div>
        </form>

        @if ($budgets->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>CÃ³digo</th>
                            <th>VersÃ£o</th>
                            <th>Orcamento Base</th>
                            <th>Mais Recente</th>
                            <th>Cliente</th>
                            <th>Estado</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            <th>Data</th>
                            <th>AÃ§Ãµes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($budgets as $budget)
                            <tr>
                                <td>
                                    <a href="{{ route('budgets.show', $budget) }}">
                                        <strong>{{ $budget->code }}</strong>
                                    </a>
                                </td>

                                <td>{{ $budget->customer->name ?? 'â€”' }}</td>

                                <td>
                                    @if ($budget->status === 'draft')
                                        <span class="badge bg-secondary">Rascunho</span>
                                    @elseif ($budget->status === 'created')
                                        <span class="badge bg-primary">Criado</span>
                                    @elseif ($budget->status === 'sent')
                                        <span class="badge bg-info">Enviado</span>
                                    @elseif ($budget->status === 'waiting_response')
                                        <span class="badge bg-warning text-dark">Aguarda resposta</span>
                                    @elseif ($budget->status === 'accepted')
                                        <span class="badge bg-success">Aceite</span>
                                    @elseif ($budget->status === 'rejected')
                                        <span class="badge bg-danger">NÃ£o aceite</span>
                                    @else
                                        <span class="badge bg-dark">{{ $budget->status }}</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) $budget->subtotal, 2, ',', '.') }} â‚¬
                                </td>

                                <td class="text-end">
                                    {{ number_format((float) $budget->tax_total, 2, ',', '.') }} â‚¬
                                </td>

                                <td class="text-end">
                                    <strong>{{ number_format((float) $budget->total, 2, ',', '.') }} â‚¬</strong>
                                </td>

                                <td>
                                    {{ $budget->budget_date?->format('d/m/Y') ?? 'â€”' }}
                                </td>

                                <td>
                                    <a href="{{ route('budgets.show', $budget) }}" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $budgets->links() }}
            </div>
        @else
            <div class="text-muted">
                NÃ£o foram encontrados orÃ§amentos com os filtros aplicados.
            </div>
        @endif
    </div>
</div>
@endsection

