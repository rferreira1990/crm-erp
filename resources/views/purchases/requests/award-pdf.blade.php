<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $purchaseRequest->code }} - Adjudicacao</title>
    <style>
        @page { margin: 14mm 14mm 22mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 0; }
        .document { width: 100%; }
        .header { margin-bottom: 12px; }
        .logo-box { height: 80px; width: 170px; border: 2px solid #1f6fa6; color: #1f6fa6; text-align: center; font-weight: bold; font-size: 22px; line-height: 80px; }
        .logo-image { max-width: 170px; max-height: 80px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .meta-table th { background: #1f6fa6; color: #fff; padding: 7px 8px; text-align: left; font-weight: bold; }
        .meta-table td { padding: 7px 8px; border-bottom: 1px solid #e7e7e7; }
        .section-title { margin-top: 14px; margin-bottom: 6px; font-size: 12px; font-weight: bold; color: #1f6fa6; text-transform: uppercase; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .items-table th { background: #1f6fa6; color: #fff; padding: 7px 8px; text-align: left; font-weight: bold; }
        .items-table td { padding: 7px 8px; border-bottom: 1px solid #ececec; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 9px; font-size: 9px; font-weight: bold; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .footer-bar { position: fixed; left: 0; right: 0; bottom: 0; width: 100%; background: #1f6fa6; color: #fff; padding: 8px 12px; font-size: 10px; box-sizing: border-box; }
    </style>
</head>
<body>
    @php
        $company = $companyProfile;
        $companyLogoPath = $company?->logo_path ? public_path('storage/' . $company->logo_path) : null;

        $supplierId = $supplier?->id;

        $awardItems = $award->items
            ->when($supplierId, fn ($collection) => $collection->where('supplier_id', (int) $supplierId))
            ->sortBy(fn ($line) => (int) ($line->purchaseRequestItem?->sort_order ?? 999999))
            ->values();

        $preparedOrders = $award->preparedOrders
            ->when($supplierId, fn ($collection) => $collection->where('supplier_id', (int) $supplierId))
            ->values();
    @endphp

    <div class="document">
        <div class="header">
            @if($companyLogoPath && file_exists($companyLogoPath))
                <img src="{{ $companyLogoPath }}" alt="Logotipo" class="logo-image">
            @else
                <div class="logo-box">LOGO</div>
            @endif
        </div>

        <table class="meta-table">
            <thead>
                <tr>
                    <th style="width: 20%;">RFQ</th>
                    <th style="width: 20%;">Data decisao</th>
                    <th style="width: 20%;">Modo</th>
                    <th style="width: 20%;">Decidido por</th>
                    <th style="width: 20%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $purchaseRequest->code }}</td>
                    <td>{{ $award->decided_at?->format('d/m/Y H:i') ?: '-' }}</td>
                    <td>{{ $award->modeLabel() }}</td>
                    <td>{{ $award->decidedBy?->name ?: '-' }}</td>
                    <td>{{ $award->status }}</td>
                </tr>
            </tbody>
        </table>

        <table class="meta-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Empresa emissora</th>
                    <th style="width: 35%;">Fornecedor destino</th>
                    <th style="width: 15%;">Encomendas</th>
                    <th style="width: 15%;">Linhas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $company?->company_name ?: '-' }}</strong><br>
                        @if($company?->email){{ $company->email }}<br>@endif
                        @if($company?->phone){{ $company->phone }}@endif
                    </td>
                    <td>
                        @if($supplier)
                            <strong>{{ $supplier->code ? $supplier->code . ' - ' . $supplier->name : $supplier->name }}</strong><br>
                            {{ $supplier->habitual_order_email ?: $supplier->email ?: '-' }}
                        @else
                            Todos os fornecedores vencedores
                        @endif
                    </td>
                    <td class="text-center">{{ $preparedOrders->count() }}</td>
                    <td class="text-center">{{ $awardItems->count() }}</td>
                </tr>
            </tbody>
        </table>

        @if(!empty($award->justification))
            <div class="section-title">Justificacao</div>
            <div>{{ $award->justification }}</div>
        @endif

        <div class="section-title">Resumo por fornecedor</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Fornecedor</th>
                    <th class="text-center">Linhas</th>
                    <th class="text-right">Subtotal s/ IVA</th>
                    <th class="text-center">Moeda</th>
                    <th>Cond. pagamento</th>
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
                    <tr>
                        <td colspan="5" class="muted">Sem encomendas preparadas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="section-title">Linhas adjudicadas</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 9%;">#</th>
                    <th style="width: 12%;">Artigo</th>
                    <th style="width: 24%;">Descricao</th>
                    <th style="width: 8%;" class="text-right">Qtd</th>
                    <th style="width: 8%;" class="text-center">Un.</th>
                    <th style="width: 14%;" class="text-right">Unit. s/ IVA</th>
                    <th style="width: 8%;" class="text-right">Desc %</th>
                    <th style="width: 17%;" class="text-right">Total s/ IVA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($awardItems as $line)
                    @php($requestItem = $line->purchaseRequestItem)
                    <tr>
                        <td>{{ $requestItem?->sort_order ?: '-' }}</td>
                        <td>
                            {{ $requestItem?->item?->code ?: 'MANUAL' }}
                            @if($line->supplier_item_reference)
                                <div class="muted">Ref: {{ $line->supplier_item_reference }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $requestItem?->description ?: '-' }}
                            @if($line->notes)
                                <div class="muted">{{ $line->notes }}</div>
                            @endif
                            @if((float) ($line->awarded_qty ?? 0) !== (float) ($requestItem?->qty ?? 0))
                                <div><span class="badge badge-warning">Qtd divergente</span></div>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format((float) $line->awarded_qty, 3, ',', '.') }}</td>
                        <td class="text-center">{{ $requestItem?->item?->unit?->code ?: ($requestItem?->unit_snapshot ?: '-') }}</td>
                        <td class="text-right">{{ number_format((float) $line->unit_price, 4, ',', '.') }}</td>
                        <td class="text-right">{{ $line->discount_percent !== null ? number_format((float) $line->discount_percent, 3, ',', '.') : '0,000' }}</td>
                        <td class="text-right">{{ $line->line_total !== null ? number_format((float) $line->line_total, 2, ',', '.') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="muted">Sem linhas adjudicadas para os filtros selecionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer-bar">
        Documento de adjudicacao - {{ $purchaseRequest->code }} - Gerado em {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>

