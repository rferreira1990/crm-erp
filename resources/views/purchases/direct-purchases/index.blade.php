@extends('layouts.admin')

@section('title', 'Compras Diretas')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Compras Diretas</h2>
        <div class="small text-muted">Registo imediato de compras com entrada automatica em stock</div>
    </div>

    @can('purchases.create')
        <a href="{{ route('purchase-direct-purchases.create') }}" class="btn btn-primary">Registar compra direta</a>
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
        <form method="GET" action="{{ route('purchase-direct-purchases.index') }}" class="row g-2">
            <div class="col-md-4">
                <label class="form-label" for="search">Pesquisar</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    class="form-control"
                    value="{{ $filters['search'] }}"
                    placeholder="Documento, fornecedor, referencia..."
                >
            </div>

            <div class="col-md-3">
                <label class="form-label" for="supplier_id">Fornecedor</label>
                <select id="supplier_id" name="supplier_id" class="form-select">
                    <option value="0">Todos</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((int) $filters['supplier_id'] === (int) $supplier->id)>
                            {{ $supplier->code ? $supplier->code . ' - ' . $supplier->name : $supplier->name }}
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

            <div class="col-md-1">
                <label class="form-label" for="purchase_from">Data de</label>
                <input type="date" id="purchase_from" name="purchase_from" class="form-control" value="{{ $filters['purchase_from'] }}">
            </div>

            <div class="col-md-1">
                <label class="form-label" for="purchase_to">Data ate</label>
                <input type="date" id="purchase_to" name="purchase_to" class="form-control" value="{{ $filters['purchase_to'] }}">
            </div>

            <div class="col-md-12 d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                <a href="{{ route('purchase-direct-purchases.index') }}" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</section>

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Lista de compras</h3>
        <span class="badge bg-light text-dark border">{{ $directPurchases->total() }}</span>
    </header>
    <div class="card-body">
        @if ($directPurchases->isEmpty())
            <div class="text-muted">Sem compras diretas para os filtros selecionados.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Data</th>
                            <th>Fornecedor</th>
                            <th class="text-center">Linhas</th>
                            <th class="text-end">Qtd total</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($directPurchases as $purchase)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $purchase->document_number }}</div>
                                    <div class="small text-muted">{{ $purchase->external_reference ?: '-' }}</div>
                                </td>
                                <td>{{ $purchase->purchase_date?->format('d/m/Y') ?: '-' }}</td>
                                <td>{{ $purchase->supplier?->code ? $purchase->supplier->code . ' - ' . $purchase->supplier->name : ($purchase->supplier?->name ?: '-') }}</td>
                                <td class="text-center">{{ $purchase->items_count }}</td>
                                <td class="text-end">{{ number_format((float) ($purchase->total_qty ?? 0), 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $purchase->subtotal_amount, 2, ',', '.') }} {{ $purchase->currency }}</td>
                                <td class="text-end">{{ number_format((float) $purchase->tax_amount, 2, ',', '.') }} {{ $purchase->currency }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $purchase->total_amount, 2, ',', '.') }} {{ $purchase->currency }}</td>
                                <td class="text-center"><span class="badge bg-success">{{ $purchase->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('purchase-direct-purchases.show', $purchase) }}" class="btn btn-sm btn-outline-primary">Detalhe</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $directPurchases->links() }}
            </div>
        @endif
    </div>
</section>
@endsection

