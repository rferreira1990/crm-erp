<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $purchaseRequest->code }} - Adjudicacao</title>
    <style>
        @page { margin: 10mm 10mm 12mm 10mm; }

        body {
            margin: 0;
            padding: 0;
            background: #f3f6fb;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .shell {
            width: 100%;
            background: #f3f6fb;
            padding: 10px 0 0 0;
        }

        .container {
            width: 100%;
            max-width: 185mm;
            margin: 0 auto;
        }

        .header {
            background: #0f172a;
            border-radius: 12px 12px 0 0;
            padding: 12px 14px;
            color: #ffffff;
        }

        .header-table,
        .main-table,
        .summary-table,
        .items-table,
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .logo-cell {
            width: 54px;
            vertical-align: middle;
        }

        .logo-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #ffffff;
            text-align: center;
            line-height: 44px;
            color: #0f172a;
            font-size: 18px;
            font-weight: bold;
        }

        .logo-image {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: contain;
            background: #ffffff;
            padding: 4px;
            box-sizing: border-box;
        }

        .kicker {
            color: #93c5fd;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.7px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .title {
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .subtitle {
            color: #cbd5e1;
            font-size: 10px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #dbe3ef;
            border-top: none;
            border-radius: 0 0 12px 12px;
            padding: 14px;
        }

        .intro {
            font-size: 11px;
            line-height: 1.6;
            margin-bottom: 10px;
            color: #374151;
        }

        .meta-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
        }

        .box-title {
            font-size: 9px;
            color: #64748b;
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 7px;
        }

        .summary-table td {
            padding: 3px 0;
            border-bottom: 1px solid #e5eaf1;
            font-size: 10px;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .label {
            color: #64748b;
        }

        .value {
            text-align: right;
            font-weight: bold;
            color: #111827;
        }

        .section-title {
            margin-top: 12px;
            margin-bottom: 6px;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table th {
            background: #0f172a;
            color: #ffffff;
            padding: 6px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }

        .items-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 6px 6px;
            vertical-align: top;
            font-size: 9px;
            color: #1f2937;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #6b7280; }

        .notice {
            margin-top: 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 4px solid #2563eb;
            border-radius: 9px;
            padding: 9px;
            font-size: 10px;
            line-height: 1.5;
        }

        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>
<body>
@php
    $company = $companyProfile;
    $companyLogoPath = $company?->logo_path ? public_path('storage/' . ltrim($company->logo_path, '/')) : null;

    $supplierId = $supplier?->id;
    $awardDate = $award->decided_at?->format('d/m/Y H:i') ?: now()->format('d/m/Y H:i');
    $companyDisplayName = $company?->company_name ?: config('app.name');

    $awardItems = $award->items
        ->when($supplierId, fn ($collection) => $collection->where('supplier_id', (int) $supplierId))
        ->sortBy(fn ($line) => [(int) ($line->purchaseRequestItem?->sort_order ?? 999999), (int) $line->id])
        ->values();

    $preparedOrders = $award->preparedOrders
        ->when($supplierId, fn ($collection) => $collection->where('supplier_id', (int) $supplierId))
        ->values();
@endphp

<div class="shell">
    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if($companyLogoPath && file_exists($companyLogoPath))
                            <img src="{{ $companyLogoPath }}" alt="{{ $companyDisplayName }}" class="logo-image">
                        @else
                            <div class="logo-box">{{ mb_strtoupper(mb_substr($companyDisplayName, 0, 1)) }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="kicker">Documento tecnico de adjudicacao</div>
                        <div class="title">{{ $companyDisplayName }}</div>
                        <div class="subtitle">RFQ {{ $purchaseRequest->code }} | Decisao {{ $awardDate }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <div class="intro">
                Documento de adjudicacao emitido para o RFQ <strong>{{ $purchaseRequest->code }}</strong>,
                com base em comparacao de propostas sem IVA.
            </div>

            <table class="meta-grid">
                <tr>
                    <td style="padding-right: 5px;">
                        <div class="box">
                            <div class="box-title">Resumo da decisao</div>
                            <table class="summary-table">
                                <tr><td class="label">Modo de adjudicacao</td><td class="value">{{ $award->modeLabel() }}</td></tr>
                                <tr><td class="label">Data/hora</td><td class="value">{{ $awardDate }}</td></tr>
                                <tr><td class="label">Decidido por</td><td class="value">{{ $award->decidedBy?->name ?: '-' }}</td></tr>
                                <tr><td class="label">Estado</td><td class="value">{{ $award->status }}</td></tr>
                                <tr><td class="label">Linhas adjudicadas</td><td class="value">{{ $awardItems->count() }}</td></tr>
                                <tr><td class="label">Encomendas preparadas</td><td class="value">{{ $preparedOrders->count() }}</td></tr>
                            </table>
                        </div>
                    </td>
                    <td style="padding-left: 5px;">
                        <div class="box">
                            <div class="box-title">Entidades</div>
                            <table class="summary-table">
                                <tr>
                                    <td class="label">Empresa emissora</td>
                                    <td class="value" style="text-align: left;">{{ $companyDisplayName }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Fornecedor destino</td>
                                    <td class="value" style="text-align: left;">
                                        @if ($supplier)
                                            {{ $supplier->code ? $supplier->code . ' - ' . $supplier->name : $supplier->name }}
                                        @else
                                            Todos os fornecedores vencedores
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">Email fornecedor</td>
                                    <td class="value" style="text-align: left;">
                                        {{ $supplier?->habitual_order_email ?: $supplier?->email ?: '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">Email empresa</td>
                                    <td class="value" style="text-align: left;">{{ $company?->email ?: $company?->mail_from_address ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Telefone empresa</td>
                                    <td class="value" style="text-align: left;">{{ $company?->phone ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">NIF empresa</td>
                                    <td class="value" style="text-align: left;">{{ $company?->tax_number ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            @if (!empty($award->justification))
                <div class="notice">
                    <strong>Justificacao:</strong><br>
                    {{ $award->justification }}
                </div>
            @endif

            <div class="section-title">Resumo por fornecedor vencedor</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 40%;">Fornecedor</th>
                        <th style="width: 12%;" class="text-center">Linhas</th>
                        <th style="width: 18%;" class="text-right">Subtotal s/ IVA</th>
                        <th style="width: 10%;" class="text-center">Moeda</th>
                        <th style="width: 20%;">Cond. pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preparedOrders as $preparedOrder)
                        <tr>
                            <td>{{ $preparedOrder->supplier?->code ? $preparedOrder->supplier->code . ' - ' . $preparedOrder->supplier->name : ($preparedOrder->supplier?->name ?: '-') }}</td>
                            <td class="text-center">{{ $preparedOrder->items->count() }}</td>
                            <td class="text-right">{{ number_format((float) $preparedOrder->subtotal_amount, 2, ',', '.') }}</td>
                            <td class="text-center">{{ $preparedOrder->currency }}</td>
                            <td>{{ $preparedOrder->paymentTerm?->displayLabel() ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Sem encomendas preparadas.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="section-title">Linhas adjudicadas</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 12%;">Artigo</th>
                        <th style="width: 28%;">Descricao</th>
                        <th style="width: 8%;" class="text-right">Qtd</th>
                        <th style="width: 7%;" class="text-center">Un.</th>
                        <th style="width: 13%;" class="text-right">Unit. s/ IVA</th>
                        <th style="width: 8%;" class="text-right">Desc %</th>
                        <th style="width: 12%;" class="text-right">Total s/ IVA</th>
                        <th style="width: 6%;" class="text-center">Obs</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($awardItems as $line)
                        @php($requestItem = $line->purchaseRequestItem)
                        @php($qtyDiff = abs((float) ($line->awarded_qty ?? 0) - (float) ($requestItem?->qty ?? 0)) > 0.0005)
                        <tr>
                            <td>{{ $requestItem?->sort_order ?: '-' }}</td>
                            <td>
                                {{ $requestItem?->item?->code ?: 'MANUAL' }}
                                @if ($line->supplier_item_reference)
                                    <div class="muted">Ref: {{ $line->supplier_item_reference }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $requestItem?->description ?: '-' }}
                                @if ($line->notes)
                                    <div class="muted">{{ $line->notes }}</div>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format((float) $line->awarded_qty, 3, ',', '.') }}</td>
                            <td class="text-center">{{ $requestItem?->item?->unit?->code ?: ($requestItem?->unit_snapshot ?: '-') }}</td>
                            <td class="text-right">{{ number_format((float) $line->unit_price, 4, ',', '.') }}</td>
                            <td class="text-right">{{ $line->discount_percent !== null ? number_format((float) $line->discount_percent, 3, ',', '.') : '0,000' }}</td>
                            <td class="text-right">{{ $line->line_total !== null ? number_format((float) $line->line_total, 2, ',', '.') : '-' }}</td>
                            <td class="text-center">{{ $qtyDiff ? 'Qtd' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted">Sem linhas adjudicadas para os filtros selecionados.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="footer">
                Documento de adjudicacao | {{ $purchaseRequest->code }} | Gerado em {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
