@extends('layouts.admin')

@section('title', 'Contas a Receber')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Contas a Receber</h2>
        <div class="small text-muted">Documentos internos operacionais de debito a cliente</div>
    </div>

    @can('customers.edit')
        <a href="{{ route('customer-receivables.create') }}" class="btn btn-primary">Nova conta a receber</a>
    @endcan
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<section class="card mb-3">
    <header class="card-header">
        <h3 class="card-title mb-0">Filtros</h3>
    </header>

    <div class="card-body">
        <form method="GET" action="{{ route('customer-receivables.index') }}" class="row g-2">
            <div class="col-md-4">
                <label class="form-label" for="search">Pesquisar</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="{{ $filters['search'] }}"
                    placeholder="Documento, cliente, descricao..."
                >
            </div>

            <div class="col-md-2">
                <label class="form-label" for="customer_id">Cliente</label>
                <select id="customer_id" name="customer_id" class="form-select">
                    <option value="0">Todos</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((int) $filters['customer_id'] === (int) $customer->id)>
                            {{ $customer->code ? $customer->code . ' - ' . $customer->name : $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="status">Estado</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($statuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected($filters['status'] === $statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label" for="issue_from">Emissao de</label>
                <input type="date" id="issue_from" name="issue_from" class="form-control" value="{{ $filters['issue_from'] }}">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="issue_to">Emissao ate</label>
                <input type="date" id="issue_to" name="issue_to" class="form-control" value="{{ $filters['issue_to'] }}">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="due_from">Vencimento de</label>
                <input type="date" id="due_from" name="due_from" class="form-control" value="{{ $filters['due_from'] }}">
            </div>

            <div class="col-md-2">
                <label class="form-label" for="due_to">Vencimento ate</label>
                <input type="date" id="due_to" name="due_to" class="form-control" value="{{ $filters['due_to'] }}">
            </div>

            <div class="col-md-12 d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                <a href="{{ route('customer-receivables.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Lista de documentos</h3>
        <span class="badge bg-light text-dark border">{{ $receivables->total() }}</span>
    </header>

    <div class="card-body">
        @if ($receivables->isEmpty())
            <div class="text-muted">Sem contas a receber para os filtros selecionados.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Cliente</th>
                            <th>Emissao</th>
                            <th>Vencimento</th>
                            <th class="text-end">Valor</th>
                            <th>Estado</th>
                            <th>Conta corrente</th>
                            <th>Criado por</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($receivables as $receivable)
                            @php
                                $statusClass = match ($receivable->status) {
                                    \App\Models\CustomerReceivable::STATUS_DRAFT => 'bg-secondary',
                                    \App\Models\CustomerReceivable::STATUS_ISSUED => 'bg-primary',
                                    \App\Models\CustomerReceivable::STATUS_CLOSED => 'bg-success',
                                    default => 'bg-dark',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $receivable->document_number }}</div>
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($receivable->description, 70) }}</div>
                                </td>
                                <td>{{ $receivable->customer?->code ? $receivable->customer->code . ' - ' . $receivable->customer->name : ($receivable->customer?->name ?: '-') }}</td>
                                <td>{{ $receivable->issue_date?->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ $receivable->due_date?->format('d/m/Y') ?: '-' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $receivable->amount, 2, ',', '.') }} EUR</td>
                                <td><span class="badge {{ $statusClass }}">{{ $receivable->statusLabel() }}</span></td>
                                <td>
                                    @if ($receivable->accountEntry)
                                        <span class="badge bg-info text-dark">Automatico</span>
                                    @else
                                        <span class="badge bg-light text-dark border">-</span>
                                    @endif
                                </td>
                                <td>{{ $receivable->user?->name ?: '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('customer-receivables.show', $receivable) }}" class="btn btn-sm btn-outline-primary">Ver</a>

                                    @can('customers.edit')
                                        @if (! $receivable->isClosed())
                                            <a href="{{ route('customer-receivables.edit', $receivable) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $receivables->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
