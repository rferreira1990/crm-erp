<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $purchaseRequest->code }}</title>
    <style>
        @page {
            margin: 14mm 14mm 28mm 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 0;
            padding: 0;
        }

        .document {
            width: 100%;
            position: relative;
        }

        .draft-watermark {
            position: fixed;
            top: 42%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-32deg);
            font-size: 72px;
            font-weight: bold;
            color: rgba(180, 180, 180, 0.18);
            letter-spacing: 8px;
            z-index: 0;
            white-space: nowrap;
        }

        .content-layer {
            position: relative;
            z-index: 1;
        }

        .small-top-line {
            width: 100%;
            font-size: 9px;
            color: #6b6b6b;
            margin-bottom: 10px;
        }

        .clearfix::after {
            content: "";
            display: block;
            clear: both;
        }

        .top-grid {
            width: 100%;
            margin-bottom: 18px;
        }

        .top-left {
            width: 28%;
            vertical-align: top;
        }

        .logo-box {
            height: 95px;
            width: 180px;
            border: 2px solid #1f6fa6;
            color: #1f6fa6;
            text-align: center;
            font-weight: bold;
            font-size: 24px;
            line-height: 95px;
            margin-top: 10px;
        }

        .logo-image {
            max-width: 180px;
            max-height: 95px;
            margin-top: 10px;
        }

        .company-block {
            font-size: 11px;
            line-height: 1.45;
            margin-top: 6px;
        }

        .supplier-block {
            margin-top: 20px;
            font-size: 11px;
            line-height: 1.45;
        }

        .supplier-block .title {
            color: #666;
            margin-bottom: 3px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .items-table thead {
            display: table-header-group;
        }

        .items-table tr {
            page-break-inside: avoid;
        }

        .items-table thead th {
            background: #1f6fa6;
            color: #fff;
            font-weight: bold;
            padding: 8px 8px;
            text-align: left;
            border: none;
        }

        .items-table tbody td {
            padding: 7px 8px;
            vertical-align: top;
            border-bottom: 1px solid #e7e7e7;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .items-table tr.items-filler-row td {
            height: 12px;
            padding: 0;
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .line-title {
            font-weight: bold;
        }

        .line-notes,
        .muted {
            color: #666;
        }

        .notes-section {
            margin-top: 12px;
            font-size: 10px;
            line-height: 1.45;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .notice-box {
            margin-top: 18px;
            font-size: 10px;
            line-height: 1.5;
            color: #444;
            border-top: 1px solid #e7e7e7;
            padding-top: 10px;
        }

        .footer-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            background: #1f6fa6;
            color: #fff;
            padding: 10px 12px;
            font-size: 10px;
            box-sizing: border-box;
            z-index: 2;
        }

        .footer-col {
            width: 24%;
            display: inline-block;
            vertical-align: top;
        }

        .footer-col strong {
            display: block;
            margin-bottom: 2px;
        }

        .page-number {
            float: right;
            text-align: right;
        }

        .no-lines {
            text-align: center;
            color: #666;
            padding: 18px 0;
        }
    </style>
</head>
<body>
    @php
        $company = $companyProfile;

        $companyLogoRelativePath = $company?->logo_path;
        $companyLogoPath = $companyLogoRelativePath ? public_path('storage/' . $companyLogoRelativePath) : null;

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

        $supplierName = $supplier?->name ?: 'Fornecedor nao definido';
        $supplierAddress = $supplier?->address;
        $supplierPostalCode = $supplier?->postal_code;
        $supplierCity = $supplier?->city;
        $supplierCountry = $supplier?->country ?: 'Portugal';
        $supplierTaxNumber = $supplier?->tax_number;
        $supplierEmail = $supplier?->habitual_order_email ?: $supplier?->email;

        $requestDate = $purchaseRequest->created_at?->format('d/m/Y') ?: now()->format('d/m/Y');
        $deadlineDate = $purchaseRequest->deadline_at?->format('d/m/Y') ?: '-';
        $statusLabel = $purchaseRequest->statusLabel();
        $isDraft = $purchaseRequest->status === \App\Models\PurchaseRequest::STATUS_DRAFT;

        $minRows = 8;
        $currentRows = $purchaseRequest->items->count();
        $fillerRows = max(0, $minRows - $currentRows);
    @endphp

    <div class="document">
        @if($isDraft)
            <div class="draft-watermark">RASCUNHO</div>
        @endif

        <div class="content-layer">
            <div class="small-top-line clearfix">
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
                            <strong>{{ $companyName ?: '—' }}</strong><br>

                            @if($companyAddressLine1)
                                {{ $companyAddressLine1 }}<br>
                            @endif

                            @if($companyAddressLine2)
                                {{ $companyAddressLine2 }}<br>
                            @endif

                            @if($companyPostalCode || $companyPostalCodeSuffix || $companyPostalDesignation || $companyCity)
                                {{ trim(($companyPostalCode ?: '') . (!empty($companyPostalCodeSuffix) ? '-' . $companyPostalCodeSuffix : '') . ' ' . ($companyPostalDesignation ?: $companyCity ?: '')) }}<br>
                            @endif

                            {{ $companyCountryCode === 'PT' ? 'Portugal' : $companyCountryCode }}<br>

                            @if($companyTaxNumber)
                                NIF {{ $companyTaxNumber }}<br>
                            @endif

                            @if($companyPhone)
                                Tel. {{ $companyPhone }}<br>
                            @endif

                            @if($companyEmail)
                                {{ $companyEmail }}<br>
                            @endif
                        </div>
                    </td>

                    <td class="top-left">
                        <div class="supplier-block">
                            <div class="title">Exmo.(s) Sr.(s)</div>
                            <strong>{{ $supplierName }}</strong><br>

                            @if($supplierAddress)
                                {{ $supplierAddress }}<br>
                            @endif

                            @if($supplierPostalCode || $supplierCity)
                                {{ trim(($supplierPostalCode ?: '') . ' ' . ($supplierCity ?: '')) }}<br>
                            @endif

                            {{ $supplierCountry }}<br>

                            @if($supplierTaxNumber)
                                V/Contribuinte {{ $supplierTaxNumber }}<br>
                            @endif

                            @if($supplierEmail)
                                {{ $supplierEmail }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">RFQ N.</th>
                        <th style="width: 16%;">Data</th>
                        <th style="width: 16%;">Prazo proposta</th>
                        <th style="width: 24%;">Obra</th>
                        <th style="width: 12%;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $purchaseRequest->code }}</td>
                        <td>{{ $requestDate }}</td>
                        <td>{{ $deadlineDate }}</td>
                        <td>{{ $purchaseRequest->work?->code ? $purchaseRequest->work->code . ' - ' . $purchaseRequest->work->name : '—' }}</td>
                        <td>{{ $statusLabel }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 16%;">Artigo</th>
                        <th style="width: 42%;">Descricao</th>
                        <th style="width: 10%;">Qtd.</th>
                        <th style="width: 8%;">Un.</th>
                        <th style="width: 24%;">Observacoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseRequest->items as $line)
                        <tr>
                            <td>{{ $line->item?->code ?: '—' }}</td>
                            <td>
                                <div class="line-title">{{ $line->description }}</div>
                            </td>
                            <td>{{ number_format((float) $line->qty, 3, ',', '.') }}</td>
                            <td>{{ $line->item?->unit?->code ?: $line->unit_snapshot ?: 'un' }}</td>
                            <td>
                                @if(!empty($line->notes))
                                    <div class="line-notes">{{ $line->notes }}</div>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="no-lines">Sem linhas no pedido de cotacao.</td>
                        </tr>
                    @endforelse

                    @for($i = 0; $i < $fillerRows; $i++)
                        <tr class="items-filler-row">
                            <td colspan="5"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            @if(!empty($purchaseRequest->notes))
                <div class="notes-section">
                    <strong>Notas finais:</strong><br>
                    {{ $purchaseRequest->notes }}
                </div>
            @endif

            <div class="notice-box">
                Este documento destina-se apenas a pedido de cotacao e nao inclui precos, descontos ou totais.
            </div>

            <div class="footer-bar clearfix">
                <div class="footer-col">
                    <strong>Telefone</strong>
                    {{ $companyPhone ?: '—' }}
                </div>

                <div class="footer-col">
                    <strong>Email</strong>
                    {{ $companyEmail ?: '—' }}
                </div>

                <div class="footer-col">
                    <strong>N/Contribuinte</strong>
                    {{ $companyTaxNumber ?: '—' }}
                </div>

                <div class="footer-col page-number">
                    Pag 1/1
                </div>
            </div>
        </div>
    </div>
</body>
</html>

