@extends('layouts.admin')

@section('title', 'Comparacao de Propostas RFQ')

@section('content')
@php
    $statusBadgeClass = match ($purchaseRequest->status) {
        'closed' => 'bg-success',
        'cancelled' => 'bg-secondary',
        'sent' => 'bg-primary',
        default => 'bg-warning text-dark',
    };
@endphp

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h2 class="mb-0">Comparacao de propostas - {{ $purchaseRequest->code }}</h2>
        <div class="small text-muted">
            {{ $purchaseRequest->title }}
            @if ($purchaseRequest->work?->code)
                | Obra {{ $purchaseRequest->work->code }} - {{ $purchaseRequest->work->name }}
            @endif
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @can('purchases.view')
            <a href="{{ route('purchase-requests.comparison.pdf', $purchaseRequest) }}" class="btn btn-outline-primary" target="_blank">PDF comparativo</a>
        @endcan
        @can('purchases.update')
            @if ($purchaseRequest->isEditable())
                <a href="{{ route('purchase-requests.show', $purchaseRequest) }}#registar-proposta" class="btn btn-primary">Registar proposta</a>
            @endif
        @endcan
        <a href="{{ route('purchase-requests.show', $purchaseRequest) }}" class="btn btn-light border">Voltar ao RFQ</a>
    </div>
</div>

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<section class="card mb-3">
    <header class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Resumo por fornecedor</h3>
        <div class="d-flex align-items-center gap-2">
            <span class="badge {{ $statusBadgeClass }}">{{ $purchaseRequest->statusLabel() }}</span>
            <span class="badge bg-light text-dark border">{{ $comparisonQuotes->count() }} proposta(s)</span>
        </div>
    </header>
    <div class="card-body">
        @if ($comparisonQuotes->isEmpty())
            <div class="text-muted">Ainda sem propostas recebidas para este RFQ.</div>
        @else
            @if ($bestVsSecondTotalPercent !== null && $comparisonQuotes->count() > 1)
                <div class="alert alert-info py-2">
                    A melhor proposta global esta {{ number_format((float) $bestVsSecondTotalPercent, 2, ',', '.') }}% abaixo da segunda melhor.
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th>Ref.</th>
                            <th class="text-end">Total s/ IVA</th>
                            <th class="text-center">Lead</th>
                            <th class="text-center">Cotadas</th>
                            <th class="text-center">Faltam</th>
                            <th>Diferenca preco</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">PDF</th>
                            @can('purchases.update')
                                <th class="text-end">Acoes</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonQuotes as $quote)
                            @php
                                $quoteId = (int) $quote->id;
                                $summary = $summaryByQuoteId[$quoteId] ?? ['quoted_lines_count' => 0, 'missing_lines_count' => 0];
                                $comparisonTotal = (float) ($quote->comparison_total_amount ?? $quote->total_amount);
                                $totalComparison = $totalComparisonByQuoteId[$quoteId] ?? ['delta_percent_vs_best' => null, 'best_cheaper_percent' => null];
                                $isBestPrice = (int) $bestPriceQuoteId === $quoteId;
                                $isBestLead = (int) $bestLeadQuoteId === $quoteId;
                                $isSelected = (int) $selectedQuoteId === $quoteId;
                                $quoteStatusClass = $quote->status === 'selected' ? 'bg-success' : ($quote->status === 'rejected' ? 'bg-secondary' : 'bg-primary');
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $quote->supplier_name_snapshot }}</strong>
                                    @if ($quote->supplier?->code)
                                        <div class="small text-muted">{{ $quote->supplier->code }}</div>
                                    @endif
                                </td>
                                <td>{{ $quote->supplier_quote_reference ?: '-' }}</td>
                                <td class="text-end">{{ number_format($comparisonTotal, 2, ',', '.') }} {{ $quote->currency }}</td>
                                <td class="text-center">{{ $quote->lead_time_days !== null ? $quote->lead_time_days . ' dias' : '-' }}</td>
                                <td class="text-center">{{ $summary['quoted_lines_count'] }}</td>
                                <td class="text-center">{{ $summary['missing_lines_count'] }}</td>
                                <td>
                                    @if ($isBestPrice)
                                        <span class="badge bg-success">Melhor proposta</span>
                                        @if ($bestVsSecondTotalPercent !== null && $comparisonQuotes->count() > 1)
                                            <div class="small text-muted mt-1">{{ number_format((float) $bestVsSecondTotalPercent, 2, ',', '.') }}% abaixo da 2a melhor</div>
                                        @endif
                                    @elseif ($totalComparison['delta_percent_vs_best'] !== null)
                                        <span class="badge bg-light text-dark border">{{ number_format((float) $totalComparison['delta_percent_vs_best'], 2, ',', '.') }}% acima da melhor</span>
                                        @if ($totalComparison['best_cheaper_percent'] !== null)
                                            <div class="small text-muted mt-1">Melhor proposta: {{ number_format((float) $totalComparison['best_cheaper_percent'], 2, ',', '.') }}% mais barata</div>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                    @if ($isBestLead)
                                        <div class="mt-1"><span class="badge bg-warning text-dark">Lead mais curto</span></div>
                                    @endif
                                    @if ($isSelected)
                                        <div class="mt-1"><span class="badge bg-success">Selecionada</span></div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $quoteStatusClass }}">{{ $quoteStatuses[$quote->status] ?? $quote->status }}</span>
                                </td>
                                <td class="text-center">
                                    @if ($quote->quote_pdf_path)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchase-requests.quotes.pdf', [$purchaseRequest, $quote]) }}" target="_blank">Ver PDF</a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @can('purchases.update')
                                    <td class="text-end">
                                        @if ($purchaseRequest->isEditable())
                                            <a href="{{ route('purchase-requests.quotes.edit', [$purchaseRequest, $quote]) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                            @if (! $isSelected)
                                                <form method="POST" action="{{ route('purchase-requests.quotes.select', [$purchaseRequest, $quote]) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Selecionar</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('purchase-requests.quotes.destroy', [$purchaseRequest, $quote]) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remover proposta deste fornecedor?');">Remover</button>
                                            </form>
                                        @else
                                            <span class="text-muted">RFQ fechado</span>
                                        @endif
                                    </td>
                                @endcan
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
        <h3 class="card-title mb-0">Comparacao por linha de artigo</h3>
        <span class="badge bg-light text-dark border">{{ $comparisonRows->count() }} linha(s)</span>
    </header>
    <div class="card-body">
        @if ($comparisonQuotes->isEmpty())
            <div class="text-muted">Sem propostas para comparar.</div>
        @else
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Artigo</th>
                            <th>Descricao</th>
                            <th class="text-end">Qtd pedida</th>
                            <th class="text-center">Un.</th>
                            @foreach ($comparisonQuotes as $quote)
                                <th>{{ $quote->supplier_name_snapshot }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparisonRows as $row)
                            @php($requestItem = $row['request_item'])
                            <tr>
                                <td>{{ $requestItem->item?->code ?: 'MANUAL' }}</td>
                                <td>
                                    {{ $requestItem->description }}
                                    @if ($requestItem->notes)
                                        <div class="small text-muted mt-1">{{ $requestItem->notes }}</div>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format((float) $requestItem->qty, 3, ',', '.') }}</td>
                                <td class="text-center">{{ $requestItem->item?->unit?->code ?: $requestItem->unit_snapshot ?: '-' }}</td>
                                @foreach ($row['cells'] as $cell)
                                    @php($quoteItem = $cell['quote_item'])
                                    <td class="{{ $cell['is_missing'] ? 'table-danger' : '' }} {{ $cell['qty_divergent'] ? 'table-warning' : '' }}">
                                        @if ($cell['is_missing'])
                                            <span class="badge bg-danger">Nao cotado</span>
                                        @else
                                            <div class="small"><strong>Ref:</strong> {{ $quoteItem->supplier_item_reference ?: '-' }}</div>
                                            <div class="small"><strong>Qtd:</strong> {{ $quoteItem->quoted_qty !== null ? number_format((float) $quoteItem->quoted_qty, 3, ',', '.') : '-' }}</div>
                                            <div class="small"><strong>Unit. s/ IVA:</strong> {{ $quoteItem->unit_price !== null ? number_format((float) $quoteItem->unit_price, 4, ',', '.') : '-' }}</div>
                                            <div class="small"><strong>Desc %:</strong> {{ $quoteItem->discount_percent !== null ? number_format((float) $quoteItem->discount_percent, 3, ',', '.') : '0,000' }}</div>
                                            <div class="small"><strong>Total s/ IVA:</strong> {{ $quoteItem->line_total !== null ? number_format((float) $quoteItem->line_total, 2, ',', '.') : '-' }}</div>
                                            <div class="mt-1">
                                                @if ($cell['is_best_price'])
                                                    <span class="badge bg-success">Melhor preco</span>
                                                    @if (($row['best_vs_second_unit_price_percent'] ?? null) !== null)
                                                        <div class="small text-muted mt-1">{{ number_format((float) $row['best_vs_second_unit_price_percent'], 2, ',', '.') }}% abaixo do 2o melhor</div>
                                                    @endif
                                                @elseif (($cell['unit_price_diff_percent_vs_best'] ?? null) !== null)
                                                    <span class="badge bg-light text-dark border">{{ number_format((float) $cell['unit_price_diff_percent_vs_best'], 2, ',', '.') }}% acima do melhor</span>
                                                @endif
                                                @if ($cell['qty_divergent'])
                                                    <span class="badge bg-dark">Qtd divergente</span>
                                                @endif
                                            </div>
                                            @if ($quoteItem->notes)
                                                <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($quoteItem->notes, 90) }}</div>
                                            @endif
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection
