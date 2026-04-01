<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $purchaseRequest->code }} - Adjudicacao</title>
    <style>
        @page { margin: 14mm 14mm 28mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 0; }
        .document { width: 100%; position: relative; }
        .content-layer { position: relative; z-index: 1; }
        .small-top-line { width: 100%; margin-bottom: 10px; }
        .top-grid { width: 100%; margin-bottom: 18px; }
        .top-left { width: 50%; vertical-align: top; }
        .logo-box { height: 95px; width: 180px; border: 2px solid #1f6fa6; color: #1f6fa6; text-align: center; font-weight: bold; font-size: 24px; line-height: 95px; margin-top: 10px; }
        .logo-image { max-width: 180px; max-height: 95px; margin-top: 10px; }
        .company-block, .supplier-block { font-size: 11px; line-height: 1.45; margin-top: 6px; }
        .supplier-block .title { color: #666; margin-bottom: 3px; }
        .items-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .items-table thead th { background: #1f6fa6; color: #fff; font-weight: bold; padding: 8px; text-align: left; border: none; }
        .items-table tbody td { padding: 7px 8px; vertical-align: top; border-bottom: 1px solid #e7e7e7; }
        .items-table tr { page-break-inside: avoid; }
        .items-table tr.items-filler-row td { height: 12px; padding: 0; border-bottom: none; }
        .notes-section { margin-top: 12px; font-size: 10px; line-height: 1.45; page-break-inside: avoid; }
        .notice-box { margin-top: 18px; font-size: 10px; line-height: 1.5; color: #444; border-top: 1px solid #e7e7e7; padding-top: 10px; }
        .footer-bar { position: fixed; left: 0; right: 0; bottom: 0; width: 100%; background: #1f6fa6; color: #fff; padding: 10px 12px; font-size: 10px; box-sizing: border-box; z-index: 2; }
        .footer-col { width: 24%; display: inline-block; vertical-align: top; }
        .footer-col strong { display: block; margin-bottom: 2px; }
        .page-number { float: right; text-align: right; }
        .no-lines { text-align: center; color: #666; padding: 18px 0; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #666; }
    </style>
</head>
<body>
    @php
        $company = $companyProfile;
        $companyLogoPath = $company?->logo_path ? public_path('storage/' . ltrim($company->logo_path, '/')) : null;

        $companyName = $company?->company_name;
        $companyAddressLine1 = $company?->address_line_1;
        $companyAddressLine2 = $company?->address_line_2;
        $companyPostalCode = $company?->postal_code;
        $companyPostalCodeSuffix = $company?->postal_code_suffix;
        $companyPostalDesignation = $company?->postal_designation;
        $companyCity = $company?->city;
        $companyCountryCode = $company?->country_code ?: 'PT';
        $companyTaxNumber = $company?->tax_number;
        $companyPhone = $company?->phone;
        $companyEmail = $company?->email ?: $company?->mail_from_address;

        $awardDate = $award->decided_at?->format('d/m/Y H:i') ?: now()->format('d/m/Y H:i');
        $supplierId = $supplier?->id;

        $awardItems = $award->items
            ->when($supplierId, fn ($collection) => $collection->where('supplier_id', (int) $supplierId))
            ->sortBy(fn ($line) => [(int) ($line->purchaseRequestItem?->sort_order ?? 999999), (int) $line->id])
            ->values();

        $preparedOrders = $award->preparedOrders
            ->when($supplierId, fn ($collection) => $collection->where('supplier_id', (int) $supplierId))
            ->values();

        $documentSupplier = $supplier ?: $preparedOrders->pluck('supplier')->filter()->first();
        $supplierName = $documentSupplier?->name ?: 'Fornecedor nao definido';
        $supplierCode = $documentSupplier?->code;
        $supplierAddress = $documentSupplier?->address;
        $supplierPostalCode = $documentSupplier?->postal_code;
        $supplierCity = $documentSupplier?->city;
        $supplierCountry = $documentSupplier?->country ?: 'Portugal';
        $supplierTaxNumber = $documentSupplier?->tax_number;
        $supplierEmail = $documentSupplier?->habitual_order_email ?: $documentSupplier?->email;

        $currencyBySupplierId = $preparedOrders
            ->mapWithKeys(function ($preparedOrder) {
                return [(int) $preparedOrder->supplier_id => strtoupper((string) ($preparedOrder->currency ?: 'EUR'))];
            })
            ->all();

        $formatMoney = function (?float $amount, ?string $currency = 'EUR', int $decimals = 2): string {
            if ($amount === null) {
                return '-';
            }

            $currencyCode = strtoupper((string) ($currency ?: 'EUR'));

            if ($currencyCode === 'EUR') {
                return number_format($amount, $decimals, ',', '.') . ' €';
            }

            return number_format($amount, $decimals, ',', '.') . ' ' . $currencyCode;
        };

        $minRows = 8;
        $fillerRows = max(0, $minRows - $awardItems->count());
    @endphp

    <div class="document">
        <div class="content-layer">
            <div class="small-top-line">
                @if($companyLogoPath && file_exists($companyLogoPath))
                    <img src="{{ $companyLogoPath }}" alt="Logotipo" class="logo-image">
                @else
                    <div class="logo-box">LOGO</div>
                @endif
            </div>

            <table class="top-grid" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="top-left">
                        <div class="company-block">
                            <strong>{{ $companyName ?: '-' }}</strong><br>
                            @if($companyAddressLine1){{ $companyAddressLine1 }}<br>@endif
                            @if($companyAddressLine2){{ $companyAddressLine2 }}<br>@endif
                            @if($companyPostalCode || $companyPostalCodeSuffix || $companyPostalDesignation || $companyCity)
                                {{ trim(($companyPostalCode ?: '') . (!empty($companyPostalCodeSuffix) ? '-' . $companyPostalCodeSuffix : '') . ' ' . ($companyPostalDesignation ?: $companyCity ?: '')) }}<br>
                            @endif
                            {{ $companyCountryCode === 'PT' ? 'Portugal' : $companyCountryCode }}<br>
                            @if($companyTaxNumber)NIF {{ $companyTaxNumber }}<br>@endif
                            @if($companyPhone)Tel. {{ $companyPhone }}<br>@endif
                            @if($companyEmail){{ $companyEmail }}<br>@endif
                        </div>
                    </td>
                    <td class="top-left">
                        <div class="supplier-block">
                            <div class="title">Exmo.(s) Sr.(s)</div>
                            <strong>{{ $supplierName }}</strong><br>
                            @if($supplierAddress){{ $supplierAddress }}<br>@endif
                            @if($supplierPostalCode || $supplierCity){{ trim(($supplierPostalCode ?: '') . ' ' . ($supplierCity ?: '')) }}<br>@endif
                            @if($supplierCountry){{ $supplierCountry }}<br>@endif
                            @if($supplierTaxNumber)V/Contribuinte {{ $supplierTaxNumber }}<br>@endif
                            @if($supplierEmail){{ $supplierEmail }}@endif
                        </div>
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 24%;">Documento</th>
                        <th style="width: 20%;">RFQ N.</th>
                        <th style="width: 28%;">Data decisao</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Encomenda</td>
                        <td>{{ $purchaseRequest->code }}</td>
                        <td>{{ $awardDate }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 18%;" class="text-center">Linhas</th>
                        <th style="width: 20%;" class="text-center">Subtotal s/ IVA</th>
                        <th style="width: 10%;" class="text-center">Moeda</th>
                        <th style="width: 32%;">Cond. pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($preparedOrders as $preparedOrder)
                        <tr>
                            <td class="text-center">{{ $preparedOrder->items->count() }}</td>
                            <td class="text-center">{{ $formatMoney((float) $preparedOrder->subtotal_amount, $preparedOrder->currency, 2) }}</td>
                            <td class="text-center">{{ $preparedOrder->currency }}</td>
                            <td class="text-center">{{ $preparedOrder->paymentTerm?->displayLabel() ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="no-lines">Sem encomendas preparadas.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 14%;">Artigo</th>
                        <th style="width: 30%;">Descricao</th>
                        <th style="width: 8%;" class="text-right">Qtd.</th>
                        <th style="width: 7%;" class="text-center">Un.</th>
                        <th style="width: 10%;" class="text-right">Unit. s/ IVA</th>
                        <th style="width: 7%;" class="text-right">Desc %</th>
                        <th style="width: 16%;" class="text-right">Total s/ IVA</th>
                        <th style="width: 2%;" class="text-center">Obs</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($awardItems as $line)
                        @php($requestItem = $line->purchaseRequestItem)
                        @php($qtyDiff = abs((float) ($line->awarded_qty ?? 0) - (float) ($requestItem?->qty ?? 0)) > 0.0005)
                        @php($lineCurrency = $currencyBySupplierId[(int) ($line->supplier_id ?? 0)] ?? 'EUR')
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
                            </td>
                            <td class="text-right">{{ number_format((float) $line->awarded_qty, 2, ',', '.') }}</td>
                            <td class="text-center">{{ $requestItem?->item?->unit?->code ?: ($requestItem?->unit_snapshot ?: '-') }}</td>
                            <td class="text-right">{{ $line->unit_price !== null ? $formatMoney((float) $line->unit_price, $lineCurrency, 2) : '-' }}</td>
                            <td class="text-right">{{ $line->discount_percent !== null ? number_format((float) $line->discount_percent, 0, ',', '.') : '0,000' }}</td>
                            <td class="text-right">{{ $line->line_total !== null ? $formatMoney((float) $line->line_total, $lineCurrency, 2) : '-' }}</td>
                            <td class="text-center">{{ $qtyDiff ? 'Qtd' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="no-lines">Sem linhas adjudicadas para os filtros selecionados.</td></tr>
                    @endforelse

                    @for($i = 0; $i < $fillerRows; $i++)
                        <tr class="items-filler-row"><td colspan="9"></td></tr>
                    @endfor
                </tbody>
            </table>

            @if(!empty($award->justification))
                <div class="notes-section"><strong>Justificacao:</strong><br>{{ $award->justification }}</div>
            @endif

            <div class="notice-box">Este documento constitui uma proposta de adjudicação e deve ser considerado como confidencial e destinado exclusivamente aos destinatários identificados.</div>

            <div class="footer-bar">
                <div class="footer-col"><strong>Telefone</strong>{{ $companyPhone ?: '-' }}</div>
                <div class="footer-col"><strong>Email</strong>{{ $companyEmail ?: '-' }}</div>
                <div class="footer-col"><strong>N/Contribuinte</strong>{{ $companyTaxNumber ?: '-' }}</div>
                <div class="footer-col page-number">Pag 1/1</div>
            </div>
        </div>
    </div>
</body>
</html>
