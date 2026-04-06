@extends('layouts.admin')

@section('title', 'Detalhe Compra Direta')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Compra Direta {{ $directPurchase->document_number }}</h2>
        <div class="small text-muted">Lancada em {{ $directPurchase->purchase_date?->format('d/m/Y') ?: '-' }}</div>
    </div>

    <a href="{{ route('purchase-direct-purchases.index') }}" class="btn btn-outline-secondary">Voltar</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Cabecalho</h3>
                <span class="badge bg-success">{{ $directPurchase->statusLabel() }}</span>
            </header>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <strong>Fornecedor:</strong>
                        {{ $directPurchase->supplier?->code ? $directPurchase->supplier->code . ' - ' . $directPurchase->supplier->name : ($directPurchase->supplier?->name ?: '-') }}
                    </div>
                    <div class="col-md-3"><strong>Data:</strong> {{ $directPurchase->purchase_date?->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-3"><strong>Moeda:</strong> {{ $directPurchase->currency }}</div>
                    <div class="col-md-6"><strong>Referencia externa:</strong> {{ $directPurchase->external_reference ?: '-' }}</div>
                    <div class="col-md-3"><strong>Criado por:</strong> {{ $directPurchase->creator?->name ?: '-' }}</div>
                    <div class="col-md-3"><strong>Atualizado por:</strong> {{ $directPurchase->updater?->name ?: '-' }}</div>
                    <div class="col-12"><strong>Notas:</strong> {{ $directPurchase->notes ?: '-' }}</div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <section class="card h-100">
            <header class="card-header">
                <h3 class="card-title mb-0">Totais</h3>
            </header>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Qtd total</span>
                    <strong>{{ number_format((float) $directPurchase->totalQty(), 3, ',', '.') }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <strong>{{ number_format((float) $directPurchase->subtotal_amount, 2, ',', '.') }} {{ $directPurchase->currency }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>IVA</span>
                    <strong>{{ number_format((float) $directPurchase->tax_amount, 2, ',', '.') }} {{ $directPurchase->currency }}</strong>
                </div>
                <div class="d-flex justify-content-between border-top pt-2">
                    <span>Total</span>
                    <strong>{{ number_format((float) $directPurchase->total_amount, 2, ',', '.') }} {{ $directPurchase->currency }}</strong>
                </div>
            </div>
        </section>
    </div>
</div>

<section class="card">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Linhas</h3>
        <span class="badge bg-light text-dark border">{{ $directPurchase->items->count() }}</span>
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
                        <th class="text-end">IVA %</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">IVA</th>
                        <th class="text-end">Total</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($directPurchase->items as $line)
                        <tr>
                            <td>{{ $line->sort_order }}</td>
                            <td>{{ $line->item?->code ?: '-' }}</td>
                            <td>{{ $line->description_snapshot }}</td>
                            <td class="text-center">{{ $line->unit_snapshot ?: ($line->item?->unit?->code ?: '-') }}</td>
                            <td class="text-end">{{ number_format((float) $line->quantity, 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $line->unit_price, 4, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $line->vat_percent, 3, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $line->line_subtotal, 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((float) $line->line_vat_amount, 2, ',', '.') }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $line->line_total, 2, ',', '.') }}</td>
                            <td>{{ $line->notes ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

