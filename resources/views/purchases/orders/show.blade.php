@extends('layouts.admin')

@section('title', 'Detalhe da Encomenda')

@section('content')
@php
    $canUpdatePurchase = auth()->user()?->can('purchases.update');
    $statusClass = match ($order->status) {
        \App\Models\PurchaseSupplierOrder::STATUS_RECEIVED => 'bg-success',
        \App\Models\PurchaseSupplierOrder::STATUS_PARTIALLY_RECEIVED => 'bg-warning text-dark',
        default => 'bg-secondary',
    };

    $pdfRoute = $order->isDirect()
        ? route('purchase-orders.pdf', $order)
        : route('purchase-requests.supplier-orders.pdf', [$order->purchaseRequest, $order]);

    $receiptCreateRoute = $order->isDirect()
        ? route('purchase-orders.receipts.create', $order)
        : route('purchase-requests.supplier-orders.receipts.create', [$order->purchaseRequest, $order]);

    $returnCreateRoute = $order->isDirect()
        ? route('purchase-orders.returns.create', $order)
        : route('purchase-requests.supplier-orders.returns.create', [$order->purchaseRequest, $order]);

    $canEditDirectOrder = $canUpdatePurchase
        && $order->isDirect()
        && $order->receipts->isEmpty()
        && $order->returns->isEmpty();
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Encomenda #{{ $order->id }}</h2>
        <div class="text-muted">{{ $order->sourceLabel() }}</div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Voltar</a>
        <a href="{{ $pdfRoute }}" target="_blank" class="btn btn-outline-primary">PDF</a>
        @if ($canEditDirectOrder)
            <a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-primary">Editar</a>
        @endif
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-9">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Resumo da encomenda</h3>
                <span class="badge {{ $statusClass }}">{{ $order->statusLabel() }}</span>
            </header>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4">
                        <strong>Fornecedor:</strong>
                        {{ $order->supplier?->code ? $order->supplier->code . ' - ' . $order->supplier->name : ($order->supplier?->name ?: '-') }}
                    </div>
                    <div class="col-md-2"><strong>Data:</strong> {{ $order->prepared_at?->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-2"><strong>Moeda:</strong> {{ $order->currency }}</div>
                    <div class="col-md-4"><strong>Cond. pagamento:</strong> {{ $order->paymentTerm?->displayLabel() ?: '-' }}</div>

                    <div class="col-md-2"><strong>Linhas:</strong> {{ $order->items->count() }}</div>
                    <div class="col-md-2"><strong>Encomendada:</strong> {{ number_format((float) $order->totalOrderedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Recebida:</strong> {{ number_format((float) $order->totalReceivedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Devolvida:</strong> {{ number_format((float) $order->totalReturnedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Liquida:</strong> {{ number_format((float) $order->totalNetReceivedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Pendente:</strong> {{ number_format((float) $order->totalPendingQty(), 3, ',', '.') }}</div>

                    <div class="col-md-4"><strong>Subtotal s/ IVA:</strong> {{ number_format((float) $order->subtotal_amount, 2, ',', '.') }} {{ $order->currency }}</div>
                    <div class="col-md-4"><strong>Preparada por:</strong> {{ $order->preparedBy?->name ?: '-' }}</div>

                    @if ($order->isFromRfq() && $order->purchaseRequest)
                        <div class="col-md-4">
                            <strong>RFQ origem:</strong>
                            <a href="{{ route('purchase-requests.show', $order->purchaseRequest) }}">{{ $order->purchaseRequest->code }}</a>
                        </div>
                    @endif

                    <div class="col-12"><strong>Notas:</strong> {{ $order->notes ?: '-' }}</div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-3">
        <section class="card h-100">
            <header class="card-header">
                <h3 class="card-title mb-0">Operacoes</h3>
            </header>
            <div class="card-body d-grid gap-2">
                <a href="{{ $receiptCreateRoute }}" class="btn btn-outline-primary">Rececoes</a>
                <a href="{{ $returnCreateRoute }}" class="btn btn-outline-danger">Devolucoes</a>
            </div>
        </section>
    </div>
</div>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Linhas da encomenda</h3>
        <span class="badge bg-light text-dark border">{{ $order->items->count() }}</span>
    </header>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Artigo</th>
                        <th>Descricao</th>
                        <th class="text-center">Un.</th>
                        <th class="text-end">Qtd</th>
                        <th class="text-end">Preco un.</th>
                        <th class="text-end">Desc %</th>
                        <th class="text-end">Total</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $line)
                        <tr>
                            <td>{{ $line->sort_order }}</td>
                            <td>{{ $line->item?->code ?: 'MANUAL' }}</td>
                            <td>{{ $line->description }}</td>
                            <td class="text-center">{{ $line->item?->unit?->code ?: $line->unit_snapshot ?: '-' }}</td>
                            <td class="text-end">{{ number_format((float) $line->qty, 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $line->unit_price, 4, ',', '.') }}</td>
                            <td class="text-end">{{ $line->discount_percent !== null ? number_format((float) $line->discount_percent, 3, ',', '.') : '0,000' }}</td>
                            <td class="text-end">{{ number_format((float) ($line->line_total ?? 0), 2, ',', '.') }}</td>
                            <td>{{ $line->notes ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Resumo operacional</h3>
    </header>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3"><strong>Rececoes:</strong> {{ $order->receipts->count() }}</div>
            <div class="col-md-3"><strong>Devolucoes:</strong> {{ $order->returns->count() }}</div>
            <div class="col-md-3"><strong>Ultima rececao:</strong> {{ optional($order->receipts->first()?->receipt_date)->format('d/m/Y') ?: '-' }}</div>
            <div class="col-md-3"><strong>Ultima devolucao:</strong> {{ optional($order->returns->first()?->return_date)->format('d/m/Y') ?: '-' }}</div>
        </div>
    </div>
</section>
@endsection
