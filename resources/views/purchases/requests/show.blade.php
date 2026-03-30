@extends('layouts.admin')

@section('title', 'Detalhe do RFQ')

@section('content')
@php
    $statusBadgeClass = match ($purchaseRequest->status) {
        'closed' => 'bg-success',
        'cancelled' => 'bg-secondary',
        'sent' => 'bg-primary',
        default => 'bg-warning text-dark',
    };

    $nextStatuses = collect($statuses)->filter(fn ($label, $statusKey) => $purchaseRequest->canChangeTo($statusKey));
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">{{ $purchaseRequest->code }} - {{ $purchaseRequest->title }}</h2>
        <div class="small text-muted">Pedido de cotacao / compras</div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('purchase-requests.index') }}" class="btn btn-light border">Voltar</a>
        @can('purchases.update')
            @if ($purchaseRequest->isEditable())
                <a href="{{ route('purchase-requests.edit', $purchaseRequest) }}" class="btn btn-primary">Editar RFQ</a>
            @endif
        @endcan
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
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
    <div class="col-lg-7">
        <section class="card h-100">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Dados do RFQ</h3>
                <span class="badge {{ $statusBadgeClass }}">{{ $statuses[$purchaseRequest->status] ?? $purchaseRequest->status }}</span>
            </header>
            <div class="card-body">
                <div class="row mb-2">
                    <div class="col-md-6"><strong>Codigo:</strong> {{ $purchaseRequest->code }}</div>
                    <div class="col-md-6"><strong>Obra:</strong> {{ $purchaseRequest->work?->code ? $purchaseRequest->work->code . ' - ' . $purchaseRequest->work->name : '-' }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-6"><strong>Necessario ate:</strong> {{ $purchaseRequest->needed_at?->format('d/m/Y') ?: '-' }}</div>
                    <div class="col-md-6"><strong>Prazo propostas:</strong> {{ $purchaseRequest->deadline_at?->format('d/m/Y') ?: '-' }}</div>
                </div>

                <div class="row mb-2">
                    <div class="col-md-6"><strong>Criado por:</strong> {{ $purchaseRequest->creator?->name ?: '-' }}</div>
                    <div class="col-md-6"><strong>Atualizado por:</strong> {{ $purchaseRequest->updater?->name ?: '-' }}</div>
                </div>

                <div class="mb-0">
                    <strong>Notas:</strong>
                    <div class="text-muted mt-1">{{ $purchaseRequest->notes ?: '-' }}</div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="card h-100">
            <header class="card-header">
                <h3 class="card-title mb-0">Acoes</h3>
            </header>
            <div class="card-body">
                @can('purchases.update')
                    @if ($nextStatuses->isNotEmpty())
                        <form method="POST" action="{{ route('purchase-requests.change-status', $purchaseRequest) }}" class="mb-3">
                            @csrf
                            @method('PATCH')
                            <label for="status" class="form-label">Alterar estado</label>
                            <div class="d-flex gap-2">
                                <select name="status" id="status" class="form-select">
                                    @foreach ($nextStatuses as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary">Atualizar</button>
                            </div>
                        </form>
                    @endif
                @endcan

                @can('purchases.delete')
                    @if (in_array($purchaseRequest->status, ['draft', 'cancelled'], true))
                        <form method="POST" action="{{ route('purchase-requests.destroy', $purchaseRequest) }}">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="btn btn-outline-danger"
                                onclick="return confirm('Remover este RFQ?');"
                            >
                                Remover RFQ
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        </section>
    </div>
</div>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Linhas do pedido de cotacao</h3>
        <span class="badge bg-light text-dark border">{{ $purchaseRequest->items->count() }}</span>
    </header>
    <div class="card-body">
        @if ($purchaseRequest->items->isEmpty())
            <div class="text-muted">Sem linhas no pedido.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Artigo</th>
                            <th>Descricao</th>
                            <th class="text-end">Qtd</th>
                            <th>Unidade</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseRequest->items as $line)
                            <tr>
                                <td>{{ $line->sort_order }}</td>
                                <td>
                                    @if ($line->item)
                                        {{ $line->item->code }}
                                        <div class="small text-muted">{{ $line->item->name }}</div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $line->description }}</td>
                                <td class="text-end">{{ number_format((float) $line->qty, 3, ',', '.') }}</td>
                                <td>{{ $line->unit_snapshot ?: '-' }}</td>
                                <td>{{ $line->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Comparacao de propostas</h3>
        <span class="badge bg-light text-dark border">{{ $comparisonQuotes->count() }}</span>
    </header>
    <div class="card-body">
        @if ($comparisonQuotes->isEmpty())
            <div class="text-muted">Ainda sem propostas registadas para este RFQ.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Lead time</th>
                            <th class="text-center">Validade</th>
                            <th class="text-center">Estado</th>
                            <th>Indicadores</th>
                            @can('purchases.update')
                                <th class="text-end">Acoes</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonQuotes as $quote)
                            @php
                                $isBestPrice = (int) $bestPriceQuoteId === (int) $quote->id;
                                $isBestLead = (int) $bestLeadQuoteId === (int) $quote->id;
                                $isSelected = (int) $selectedQuoteId === (int) $quote->id;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $quote->supplier_name_snapshot }}</strong>
                                    @if ($quote->supplier?->code)
                                        <div class="small text-muted">{{ $quote->supplier->code }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{ number_format((float) $quote->total_amount, 2, ',', '.') }} {{ $quote->currency }}
                                </td>
                                <td class="text-center">{{ $quote->lead_time_days !== null ? $quote->lead_time_days . ' dias' : '-' }}</td>
                                <td class="text-center">{{ $quote->valid_until?->format('d/m/Y') ?: '-' }}</td>
                                <td class="text-center">
                                    @php
                                        $quoteStatusClass = $quote->status === 'selected'
                                            ? 'bg-success'
                                            : ($quote->status === 'rejected' ? 'bg-secondary' : 'bg-primary');
                                    @endphp
                                    <span class="badge {{ $quoteStatusClass }}">{{ $quoteStatuses[$quote->status] ?? $quote->status }}</span>
                                </td>
                                <td>
                                    @if ($isBestPrice)
                                        <span class="badge bg-info text-dark">Mais barata</span>
                                    @endif
                                    @if ($isBestLead)
                                        <span class="badge bg-warning text-dark">Mais rapida</span>
                                    @endif
                                    @if ($isSelected)
                                        <span class="badge bg-success">Selecionada</span>
                                    @endif
                                </td>
                                @can('purchases.update')
                                    <td class="text-end">
                                        @if ($purchaseRequest->isEditable() && ! $isSelected)
                                            <form method="POST" action="{{ route('purchase-requests.quotes.select', [$purchaseRequest, $quote]) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-success">Selecionar</button>
                                            </form>
                                        @endif

                                        @if ($purchaseRequest->isEditable())
                                            <form method="POST" action="{{ route('purchase-requests.quotes.destroy', [$purchaseRequest, $quote]) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Remover proposta deste fornecedor?');"
                                                >
                                                    Remover
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                @endcan
                            </tr>
                            @can('purchases.update')
                                @if ($purchaseRequest->isEditable())
                                    <tr>
                                        <td colspan="7">
                                            <details>
                                                <summary>Editar proposta de {{ $quote->supplier_name_snapshot }}</summary>
                                                <form method="POST" action="{{ route('purchase-requests.quotes.update', [$purchaseRequest, $quote]) }}" class="mt-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="row g-2">
                                                        <div class="col-md-3">
                                                            <label class="form-label">Fornecedor</label>
                                                            <select name="supplier_id" class="form-select" required>
                                                                @foreach ($suppliers as $supplier)
                                                                    <option value="{{ $supplier->id }}" @selected((int) $quote->supplier_id === (int) $supplier->id)>
                                                                        {{ $supplier->code }} - {{ $supplier->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label">Total</label>
                                                            <input type="number" name="total_amount" class="form-control" step="0.01" min="0" value="{{ number_format((float) $quote->total_amount, 2, '.', '') }}" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label">Moeda</label>
                                                            <input type="text" name="currency" class="form-control" maxlength="3" value="{{ $quote->currency }}" required>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label">Lead time (dias)</label>
                                                            <input type="number" name="lead_time_days" class="form-control" min="0" value="{{ $quote->lead_time_days }}">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Valida ate</label>
                                                            <input type="date" name="valid_until" class="form-control" value="{{ $quote->valid_until?->toDateString() }}">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Condicao de pagamento</label>
                                                            <input type="text" name="payment_term_snapshot" class="form-control" maxlength="120" value="{{ $quote->payment_term_snapshot }}">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Estado da proposta</label>
                                                            <select name="status" class="form-select" required>
                                                                @foreach ($quoteStatuses as $quoteStatusKey => $quoteStatusLabel)
                                                                    <option value="{{ $quoteStatusKey }}" @selected($quote->status === $quoteStatusKey)>{{ $quoteStatusLabel }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">Notas</label>
                                                            <input type="text" name="notes" class="form-control" maxlength="5000" value="{{ $quote->notes }}">
                                                        </div>
                                                        <div class="col-md-12 d-flex justify-content-end">
                                                            <button type="submit" class="btn btn-sm btn-outline-primary">Guardar alteracoes</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </details>
                                        </td>
                                    </tr>
                                @endif
                            @endcan
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@can('purchases.update')
    @if ($purchaseRequest->isEditable())
        <section class="card">
            <header class="card-header">
                <h3 class="card-title mb-0">Registar proposta de fornecedor</h3>
            </header>
            <div class="card-body">
                <form method="POST" action="{{ route('purchase-requests.quotes.store', $purchaseRequest) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="supplier_id" class="form-label">Fornecedor</label>
                            <select name="supplier_id" id="supplier_id" class="form-select" required>
                                <option value="">Selecionar...</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected((int) old('supplier_id') === (int) $supplier->id)>
                                        {{ $supplier->code }} - {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="total_amount" class="form-label">Total</label>
                            <input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" min="0" value="{{ old('total_amount') }}" required>
                        </div>

                        <div class="col-md-2">
                            <label for="currency" class="form-label">Moeda</label>
                            <input type="text" name="currency" id="currency" class="form-control" maxlength="3" value="{{ old('currency', 'EUR') }}" required>
                        </div>

                        <div class="col-md-2">
                            <label for="lead_time_days" class="form-label">Lead time (dias)</label>
                            <input type="number" name="lead_time_days" id="lead_time_days" class="form-control" min="0" value="{{ old('lead_time_days') }}">
                        </div>

                        <div class="col-md-2">
                            <label for="valid_until" class="form-label">Valida ate</label>
                            <input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until') }}">
                        </div>

                        <div class="col-md-4">
                            <label for="payment_term_snapshot" class="form-label">Condicao de pagamento</label>
                            <input type="text" name="payment_term_snapshot" id="payment_term_snapshot" class="form-control" maxlength="120" value="{{ old('payment_term_snapshot') }}">
                        </div>

                        <div class="col-md-2">
                            <label for="quote_status" class="form-label">Estado</label>
                            <select name="status" id="quote_status" class="form-select" required>
                                @foreach ($quoteStatuses as $quoteStatusKey => $quoteStatusLabel)
                                    <option value="{{ $quoteStatusKey }}" @selected(old('status', 'received') === $quoteStatusKey)>{{ $quoteStatusLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="notes" class="form-label">Notas</label>
                            <input type="text" name="notes" id="notes" class="form-control" maxlength="5000" value="{{ old('notes') }}">
                        </div>

                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Guardar proposta</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
    @endif
@endcan
@endsection

