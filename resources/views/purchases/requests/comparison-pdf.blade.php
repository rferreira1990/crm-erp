<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $purchaseRequest->code }} - Comparacao</title>
    <style>
        @page { margin: 12mm 12mm 20mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 0; }
        .header { margin-bottom: 10px; }
        .title { font-size: 18px; font-weight: 700; color: #1f6fa6; margin: 0 0 2px 0; }
        .subtitle { font-size: 11px; color: #555; margin: 0; }
        .meta { margin-top: 6px; font-size: 10px; color: #444; }
        .box { border: 1px solid #e2e2e2; padding: 6px 8px; margin-bottom: 10px; }
        .box-title { font-size: 11px; font-weight: 700; margin: 0 0 6px 0; color: #1f6fa6; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .table th { background: #1f6fa6; color: #fff; font-weight: 700; padding: 6px; border: 1px solid #dbe3ea; text-align: left; }
        .table td { border: 1px solid #e4e4e4; padding: 5px 6px; vertical-align: top; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #666; }
        .good { color: #0b7a3e; font-weight: 700; }
        .warn { color: #8a6d00; font-weight: 700; }
        .danger { color: #b00020; font-weight: 700; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 9px; color: #666; text-align: right; }
    </style>
</head>
<body>
@php
    $company = $companyProfile;
    $createdDate = $purchaseRequest->created_at?->format('d/m/Y H:i') ?: '-';
    $deadlineDate = $purchaseRequest->deadline_at?->format('d/m/Y') ?: '-';
@endphp

<div class="header">
    <h1 class="title">Resumo comparativo de propostas</h1>
    <p class="subtitle">RFQ {{ $purchaseRequest->code }} - {{ $purchaseRequest->title }}</p>
    <div class="meta">
        Gerado em {{ now()->format('d/m/Y H:i') }} | Criado em {{ $createdDate }} | Prazo propostas {{ $deadlineDate }}
        @if ($purchaseRequest->work?->code)
            | Obra {{ $purchaseRequest->work->code }} - {{ $purchaseRequest->work->name }}
        @endif
    </div>
    @if ($company?->company_name)
        <div class="meta">Empresa: {{ $company->company_name }}</div>
    @endif
</div>

<div class="box">
    <h2 class="box-title">Resumo global por fornecedor</h2>
    @if ($comparisonQuotes->isEmpty())
        <div class="muted">Sem propostas registadas.</div>
    @else
        @if ($bestVsSecondTotalPercent !== null && $comparisonQuotes->count() > 1)
            <div class="good" style="margin-bottom: 6px;">
                Melhor proposta global {{ number_format((float) $bestVsSecondTotalPercent, 2, ',', '.') }}% mais barata do que a segunda melhor.
            </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Fornecedor</th>
                    <th class="text-end">Total s/ IVA</th>
                    <th class="text-center">Lead</th>
                    <th class="text-center">Cotadas</th>
                    <th class="text-center">Faltam</th>
                    <th>Diferenca</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comparisonQuotes as $quote)
                    @php
                        $quoteId = (int) $quote->id;
                        $summary = $summaryByQuoteId[$quoteId] ?? ['quoted_lines_count' => 0, 'missing_lines_count' => 0];
                        $comparisonTotal = (float) ($quote->comparison_total_amount ?? $quote->total_amount);
                        $comparisonMeta = $totalComparisonByQuoteId[$quoteId] ?? ['delta_percent_vs_best' => null, 'best_cheaper_percent' => null];
                        $isBest = (int) $bestPriceQuoteId === $quoteId;
                        $isSelected = (int) $selectedQuoteId === $quoteId;
                    @endphp
                    <tr>
                        <td>
                            {{ $quote->supplier_name_snapshot }}
                            @if ($quote->supplier?->code)
                                <div class="muted">{{ $quote->supplier->code }}</div>
                            @endif
                        </td>
                        <td class="text-end">{{ number_format($comparisonTotal, 2, ',', '.') }} {{ $quote->currency }}</td>
                        <td class="text-center">{{ $quote->lead_time_days !== null ? $quote->lead_time_days . ' d' : '-' }}</td>
                        <td class="text-center">{{ $summary['quoted_lines_count'] }}</td>
                        <td class="text-center">{{ $summary['missing_lines_count'] }}</td>
                        <td>
                            @if ($isBest)
                                <span class="good">Melhor total</span>
                            @elseif ($comparisonMeta['delta_percent_vs_best'] !== null)
                                <span class="warn">{{ number_format((float) $comparisonMeta['delta_percent_vs_best'], 2, ',', '.') }}% acima da melhor</span>
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $quote->statusLabel() }}
                            @if ($isSelected)
                                <div class="good">Selecionada</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="box">
    <h2 class="box-title">Resumo por linha (melhor proposta)</h2>
    @if ($comparisonRows->isEmpty())
        <div class="muted">Sem linhas para comparar.</div>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 12%;">Artigo</th>
                    <th style="width: 28%;">Descricao</th>
                    <th style="width: 8%;" class="text-end">Qtd</th>
                    <th style="width: 7%;" class="text-center">Un.</th>
                    <th style="width: 20%;">Fornecedor vencedor</th>
                    <th style="width: 10%;" class="text-end">Preco un.</th>
                    <th style="width: 10%;" class="text-end">Dif. vs 2o</th>
                    <th style="width: 5%;" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comparisonRows as $row)
                    @php
                        $requestItem = $row['request_item'];
                        $bestCell = collect($row['cells'])->first(fn ($cell) => ($cell['is_best_price'] ?? false) && !($cell['is_missing'] ?? false));
                    @endphp
                    <tr>
                        <td>{{ $requestItem->item?->code ?: 'MANUAL' }}</td>
                        <td>{{ $requestItem->description }}</td>
                        <td class="text-end">{{ number_format((float) $requestItem->qty, 3, ',', '.') }}</td>
                        <td class="text-center">{{ $requestItem->item?->unit?->code ?: $requestItem->unit_snapshot ?: '-' }}</td>
                        @if ($bestCell)
                            @php
                                $bestQuoteItem = $bestCell['quote_item'];
                                $bestQuote = $bestCell['quote'];
                                $bestDiff = $row['best_vs_second_unit_price_percent'] ?? null;
                            @endphp
                            <td>{{ $bestQuote->supplier_name_snapshot }}</td>
                            <td class="text-end">{{ $bestQuoteItem->unit_price !== null ? number_format((float) $bestQuoteItem->unit_price, 4, ',', '.') : '-' }}</td>
                            <td class="text-end">
                                @if ($bestDiff !== null)
                                    <span class="good">{{ number_format((float) $bestDiff, 2, ',', '.') }}%</span>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($bestCell['qty_divergent'] ?? false)
                                    <span class="warn">Qtd diff</span>
                                @else
                                    <span class="good">OK</span>
                                @endif
                            </td>
                        @else
                            <td class="muted">Sem proposta valida</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-center"><span class="danger">Nao cotado</span></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="footer">
    {{ $purchaseRequest->code }} - Comparacao de propostas
</div>
</body>
</html>
