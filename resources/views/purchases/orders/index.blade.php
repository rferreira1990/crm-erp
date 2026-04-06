@extends('layouts.admin')

@section('title', 'Encomendas a Fornecedor')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Encomendas a Fornecedor</h2>
        <div class="small text-muted">RFQ + diretas no mesmo modulo</div>
    </div>

    @can('purchases.create')
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">Nova encomenda direta</a>
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
        <form method="GET" action="{{ route('purchase-orders.index') }}" class="row g-2">
            <div class="col-md-3">
                <label class="form-label" for="search">Pesquisar</label>
                <input type="text" id="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Fornecedor, RFQ, notas...">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="status">Estado</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($orderStatuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected($filters['status'] === $statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="source_type">Origem</label>
                <select id="source_type" name="source_type" class="form-select">
                    <option value="">Todas</option>
                    @foreach ($sourceTypes as $sourceKey => $sourceLabel)
                        <option value="{{ $sourceKey }}" @selected($filters['source_type'] === $sourceKey)>{{ $sourceLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="supplier_id">Fornecedor</label>
                <select id="supplier_id" name="supplier_id" class="form-select">
                    <option value="0">Todos</option>
                    @foreach ($supplierOptions as $supplier)
                        <option value="{{ $supplier->id }}" @selected((int) $filters['supplier_id'] === (int) $supplier->id)>
                            {{ $supplier->code ? $supplier->code . ' - ' . $supplier->name : $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="prepared_from">Data de</label>
                <input type="date" id="prepared_from" name="prepared_from" class="form-control" value="{{ $filters['prepared_from'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="prepared_to">Data ate</label>
                <input type="date" id="prepared_to" name="prepared_to" class="form-control" value="{{ $filters['prepared_to'] }}">
            </div>
            <div class="col-md-10 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Lista de encomendas</h3>
        <span class="badge bg-light text-dark border">{{ $orders->total() }}</span>
    </header>
    <div class="card-body">
        @if ($orders->isEmpty())
            <div class="text-muted">Sem encomendas para os filtros selecionados.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data</th>
                            <th>Origem</th>
                            <th>Fornecedor</th>
                            <th class="text-center">Linhas</th>
                            <th class="text-end">Encomendada</th>
                            <th class="text-end">Recebida</th>
                            <th class="text-end">Devolvida</th>
                            <th class="text-end">Pendente</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            @php
                                $statusClass = match ($order->status) {
                                    \App\Models\PurchaseSupplierOrder::STATUS_RECEIVED => 'bg-success',
                                    \App\Models\PurchaseSupplierOrder::STATUS_PARTIALLY_RECEIVED => 'bg-warning text-dark',
                                    default => 'bg-secondary',
                                };
                                $pdfRoute = $order->isDirect()
                                    ? route('purchase-orders.pdf', $order)
                                    : route('purchase-requests.supplier-orders.pdf', [$order->purchaseRequest, $order]);
                            @endphp
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>{{ $order->prepared_at?->format('d/m/Y') ?: '-' }}</td>
                                <td>
                                    <span class="badge {{ $order->isDirect() ? 'bg-primary' : 'bg-info text-dark' }}">{{ $order->sourceLabel() }}</span>
                                    @if ($order->isFromRfq() && $order->purchaseRequest)
                                        <div class="small mt-1">
                                            <a href="{{ route('purchase-requests.show', $order->purchaseRequest) }}">{{ $order->purchaseRequest->code }}</a>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $order->supplier?->code ? $order->supplier->code . ' - ' . $order->supplier->name : ($order->supplier?->name ?: '-') }}</td>
                                <td class="text-center">{{ $order->items_count }}</td>
                                <td class="text-end">{{ number_format((float) $order->totalOrderedQty(), 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $order->totalReceivedQty(), 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $order->totalReturnedQty(), 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $order->totalPendingQty(), 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $order->subtotal_amount, 2, ',', '.') }} {{ $order->currency }}</td>
                                <td class="text-center"><span class="badge {{ $statusClass }}">{{ $order->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Detalhe</a>
                                    <a href="{{ $pdfRoute }}" target="_blank" class="btn btn-sm btn-outline-secondary">PDF</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
