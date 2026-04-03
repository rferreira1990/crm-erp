@extends('layouts.admin')

@section('title', 'Rececao de Encomenda')

@section('content')
@php
    $canUpdatePurchase = auth()->user()?->can('purchases.update');
    $statusClass = match ($order->status) {
        \App\Models\PurchaseSupplierOrder::STATUS_RECEIVED => 'bg-success',
        \App\Models\PurchaseSupplierOrder::STATUS_PARTIALLY_RECEIVED => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Rececao de Encomenda</h2>
        <div class="text-muted">
            RFQ {{ $purchaseRequest->code }} | Encomenda #{{ $order->id }} | {{ $order->supplier?->name ?: '-' }}
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('purchase-requests.show', $purchaseRequest) }}" class="btn btn-outline-secondary">Voltar ao RFQ</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Resumo da encomenda</h3>
                <span class="badge {{ $statusClass }}">{{ $order->statusLabel() }}</span>
            </header>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4"><strong>Fornecedor:</strong> {{ $order->supplier?->code ? $order->supplier->code . ' - ' . $order->supplier->name : ($order->supplier?->name ?: '-') }}</div>
                    <div class="col-md-3"><strong>Qtd encomendada:</strong> {{ number_format((float) $order->totalOrderedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-3"><strong>Qtd recebida:</strong> {{ number_format((float) $order->totalReceivedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Pendente:</strong> {{ number_format((float) $order->totalPendingQty(), 3, ',', '.') }}</div>
                </div>
            </div>
        </section>
    </div>
</div>

@if ($canUpdatePurchase)
    <section class="card mb-3">
        <header class="card-header">
            <h3 class="card-title mb-0">Registar rececao</h3>
        </header>
        <div class="card-body">
            <form method="POST" action="{{ route('purchase-requests.supplier-orders.receipts.store', [$purchaseRequest, $order]) }}">
                @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="receipt_date" class="form-label">Data de rececao</label>
                    <input type="date" id="receipt_date" name="receipt_date" class="form-control @error('receipt_date') is-invalid @enderror" value="{{ old('receipt_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-9">
                    <label for="notes" class="form-label">Observacoes</label>
                    <input type="text" id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" maxlength="5000" value="{{ old('notes') }}">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Artigo</th>
                            <th>Descricao</th>
                            <th class="text-center">Un.</th>
                            <th class="text-end">Qtd encomendada</th>
                            <th class="text-end">Qtd recebida</th>
                            <th class="text-end">Qtd pendente</th>
                            <th class="text-end" style="width: 170px;">Receber agora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orderItems as $orderItem)
                            @php
                                $pendingQty = $orderItem->pendingQty();
                                $quantityField = 'quantities.' . $orderItem->id;
                            @endphp
                            <tr class="{{ $pendingQty <= 0 ? 'table-success' : '' }}">
                                <td>{{ $orderItem->sort_order }}</td>
                                <td>{{ $orderItem->item?->code ?: 'MANUAL' }}</td>
                                <td>
                                    {{ $orderItem->description }}
                                    @if (! $orderItem->item_id)
                                        <div class="small text-muted">Linha sem artigo. Nao gera movimento de stock.</div>
                                    @elseif (! $orderItem->item?->tracks_stock)
                                        <div class="small text-muted">Artigo sem controlo de stock.</div>
                                    @endif
                                </td>
                                <td class="text-center">{{ $orderItem->item?->unit?->code ?: $orderItem->unit_snapshot ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) $orderItem->qty, 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $orderItem->received_qty, 3, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float) $pendingQty, 3, ',', '.') }}</td>
                                <td>
                                    <input
                                        type="number"
                                        step="0.001"
                                        min="0"
                                        max="{{ number_format((float) $pendingQty, 3, '.', '') }}"
                                        name="quantities[{{ $orderItem->id }}]"
                                        class="form-control text-end @error($quantityField) is-invalid @enderror"
                                        value="{{ old('quantities.' . $orderItem->id) }}"
                                        @disabled($pendingQty <= 0)
                                    >
                                    @error($quantityField)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">Confirmar rececao</button>
                </div>
            </form>
        </div>
    </section>
@endif

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Historico de rececoes</h3>
        <span class="badge bg-light text-dark border">{{ $order->receipts->count() }}</span>
    </header>
    <div class="card-body">
        @if ($order->receipts->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Data</th>
                            <th class="text-end">Qtd recebida</th>
                            <th>Utilizador</th>
                            <th>Observacoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->receipts as $receipt)
                            <tr>
                                <td>{{ $receipt->receipt_number }}</td>
                                <td>{{ $receipt->receipt_date?->format('d/m/Y') ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) $receipt->totalReceivedQty(), 3, ',', '.') }}</td>
                                <td>{{ $receipt->user?->name ?: '-' }}</td>
                                <td>{{ $receipt->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">Ainda sem rececoes registadas para esta encomenda.</div>
        @endif
    </div>
</section>
@endsection
