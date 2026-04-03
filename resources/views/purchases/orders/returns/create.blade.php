@extends('layouts.admin')

@section('title', 'Devolucao de Encomenda')

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
        <h2 class="mb-0">Devolucao a Fornecedor</h2>
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
    <div class="col-lg-10">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Resumo da encomenda</h3>
                <span class="badge {{ $statusClass }}">{{ $order->statusLabel() }}</span>
            </header>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-4"><strong>Fornecedor:</strong> {{ $order->supplier?->code ? $order->supplier->code . ' - ' . $order->supplier->name : ($order->supplier?->name ?: '-') }}</div>
                    <div class="col-md-2"><strong>Encomendada:</strong> {{ number_format((float) $order->totalOrderedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Recebida:</strong> {{ number_format((float) $order->totalReceivedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Devolvida:</strong> {{ number_format((float) $order->totalReturnedQty(), 3, ',', '.') }}</div>
                    <div class="col-md-2"><strong>Liquida:</strong> {{ number_format((float) $order->totalNetReceivedQty(), 3, ',', '.') }}</div>
                </div>
            </div>
        </section>
    </div>
</div>

@if ($canUpdatePurchase)
    <section class="card mb-3">
        <header class="card-header">
            <h3 class="card-title mb-0">Registar devolucao</h3>
        </header>
        <div class="card-body">
            <form method="POST" action="{{ route('purchase-requests.supplier-orders.returns.store', [$purchaseRequest, $order]) }}">
                @csrf

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="return_date" class="form-label">Data de devolucao</label>
                        <input
                            type="date"
                            id="return_date"
                            name="return_date"
                            class="form-control @error('return_date') is-invalid @enderror"
                            value="{{ old('return_date', now()->format('Y-m-d')) }}"
                            required
                        >
                    </div>
                    <div class="col-md-4">
                        <label for="purchase_supplier_order_receipt_id" class="form-label">Rececao de origem (opcional)</label>
                        <select
                            id="purchase_supplier_order_receipt_id"
                            name="purchase_supplier_order_receipt_id"
                            class="form-select @error('purchase_supplier_order_receipt_id') is-invalid @enderror"
                        >
                            <option value="">Sem referencia</option>
                            @foreach ($order->receipts as $receipt)
                                <option
                                    value="{{ $receipt->id }}"
                                    @selected((int) old('purchase_supplier_order_receipt_id', 0) === (int) $receipt->id)
                                >
                                    {{ $receipt->receipt_number }} - {{ $receipt->receipt_date?->format('d/m/Y') ?: '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="notes" class="form-label">Observacoes</label>
                        <input
                            type="text"
                            id="notes"
                            name="notes"
                            class="form-control @error('notes') is-invalid @enderror"
                            maxlength="5000"
                            value="{{ old('notes') }}"
                        >
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
                                <th class="text-end">Encomendada</th>
                                <th class="text-end">Recebida</th>
                                <th class="text-end">Ja devolvida</th>
                                <th class="text-end">Disponivel</th>
                                <th class="text-end" style="width: 140px;">Devolver agora</th>
                                <th style="width: 240px;">Motivo (opcional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orderItems as $orderItem)
                                @php
                                    $returnableQty = $orderItem->returnableQty();
                                    $quantityField = 'quantities.' . $orderItem->id;
                                    $reasonField = 'reasons.' . $orderItem->id;
                                @endphp
                                <tr class="{{ $returnableQty <= 0 ? 'table-success' : '' }}">
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
                                    <td class="text-end">{{ number_format((float) $orderItem->returned_qty, 3, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format((float) $returnableQty, 3, ',', '.') }}</td>
                                    <td>
                                        <input
                                            type="number"
                                            step="0.001"
                                            min="0"
                                            max="{{ number_format((float) $returnableQty, 3, '.', '') }}"
                                            name="quantities[{{ $orderItem->id }}]"
                                            class="form-control text-end @error($quantityField) is-invalid @enderror"
                                            value="{{ old('quantities.' . $orderItem->id) }}"
                                            @disabled($returnableQty <= 0)
                                        >
                                        @error($quantityField)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            name="reasons[{{ $orderItem->id }}]"
                                            class="form-control @error($reasonField) is-invalid @enderror"
                                            maxlength="255"
                                            value="{{ old('reasons.' . $orderItem->id) }}"
                                            placeholder="Ex: material danificado"
                                        >
                                        @error($reasonField)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-primary">Confirmar devolucao</button>
                </div>
            </form>
        </div>
    </section>
@endif

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Historico de devolucoes</h3>
        <span class="badge bg-light text-dark border">{{ $order->returns->count() }}</span>
    </header>
    <div class="card-body">
        @if ($order->returns->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Data</th>
                            <th class="text-end">Qtd devolvida</th>
                            <th>Rececao ref.</th>
                            <th>Utilizador</th>
                            <th>Observacoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->returns as $return)
                            <tr>
                                <td>{{ $return->return_number }}</td>
                                <td>{{ $return->return_date?->format('d/m/Y') ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) $return->totalReturnedQty(), 3, ',', '.') }}</td>
                                <td>{{ $return->linkedReceipt?->receipt_number ?: '-' }}</td>
                                <td>{{ $return->user?->name ?: '-' }}</td>
                                <td>{{ $return->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">Ainda sem devolucoes registadas para esta encomenda.</div>
        @endif
    </div>
</section>
@endsection

