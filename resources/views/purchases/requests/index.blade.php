@extends('layouts.admin')

@section('title', 'Pedidos de Cotacao')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0">Pedidos de Cotacao (RFQ)</h2>

    @can('purchases.create')
        <a href="{{ route('purchase-requests.create') }}" class="btn btn-primary">
            Novo RFQ
        </a>
    @endcan
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card shadow-sm">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase-requests.index') }}" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Pesquisar</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        value="{{ $filters['search'] }}"
                        placeholder="Codigo, RFQ, obra ou notas"
                    >
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label">Estado</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($statuses as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" @selected($filters['status'] === $statusKey)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="deadline_from" class="form-label">Prazo de</label>
                    <input type="date" name="deadline_from" id="deadline_from" class="form-control" value="{{ $filters['deadline_from'] }}">
                </div>

                <div class="col-md-2">
                    <label for="deadline_to" class="form-label">Prazo ate</label>
                    <input type="date" name="deadline_to" id="deadline_to" class="form-control" value="{{ $filters['deadline_to'] }}">
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    <a href="{{ route('purchase-requests.index') }}" class="btn btn-outline-secondary w-100">Limpar</a>
                </div>
            </div>
        </form>

        @if ($purchaseRequests->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>RFQ</th>
                            <th>Obra</th>
                            <th>Estado</th>
                            <th>Prazo propostas</th>
                            <th class="text-center">Linhas</th>
                            <th class="text-center">Propostas</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseRequests as $rfq)
                            <tr>
                                <td>{{ $rfq->code }}</td>
                                <td>
                                    <a href="{{ route('purchase-requests.show', $rfq) }}"><strong>{{ $rfq->title }}</strong></a>
                                </td>
                                <td>
                                    @if ($rfq->work)
                                        <span>{{ $rfq->work->code }}</span>
                                        <div class="small text-muted">{{ $rfq->work->name }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @php($statusLabel = $statuses[$rfq->status] ?? $rfq->status)
                                    <span class="badge {{ $rfq->status === 'closed' ? 'bg-success' : ($rfq->status === 'cancelled' ? 'bg-secondary' : ($rfq->status === 'sent' ? 'bg-primary' : 'bg-warning text-dark')) }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>{{ $rfq->deadline_at?->format('d/m/Y') ?: '-' }}</td>
                                <td class="text-center">{{ $rfq->items_count }}</td>
                                <td class="text-center">{{ $rfq->quotes_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('purchase-requests.show', $rfq) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                    @can('purchases.update')
                                        @if ($rfq->isEditable())
                                            <a href="{{ route('purchase-requests.edit', $rfq) }}" class="btn btn-sm btn-primary">Editar</a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $purchaseRequests->links() }}
            </div>
        @else
            <div class="text-muted">Nao foram encontrados pedidos de cotacao com os filtros aplicados.</div>
        @endif
    </div>
</div>
@endsection
