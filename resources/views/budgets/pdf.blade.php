<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $budget->code }}</title>
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
        }

        .small-top-line {
            width: 100%;
            font-size: 9px;
            color: #6b6b6b;
            margin-bottom: 10px;
        }

        .small-top-line .left {
            float: left;
            width: 50%;
            text-align: left;
        }

        .small-top-line .right {
            float: right;
            width: 50%;
            text-align: right;
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

        .top-left,
        .top-middle,
        .top-right {
            vertical-align: top;
        }

        .top-left {
            width: 28%;
        }

        .top-middle {
            width: 34%;
        }

        .top-right {
            width: 38%;
            text-align: right;
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

        .customer-block {
            margin-top: 20px;
            font-size: 11px;
            line-height: 1.45;
        }

        .customer-block .title {
            color: #666;
            margin-bottom: 3px;
        }

        .doc-type {
            font-size: 15px;
            font-weight: bold;
            margin-top: 40px;
            margin-bottom: 14px;
            text-align: right;
        }

        .doc-meta {
            width: 100%;
            font-size: 11px;
            line-height: 1.6;
            margin-top: 18px;
        }

        .doc-meta .label {
            width: 46%;
            text-align: left;
            color: #333;
        }

        .doc-meta .value {
            width: 54%;
            text-align: right;
            font-weight: bold;
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

        .text-center {
            text-align: center;
        }

        .item-name {
            font-weight: bold;
        }

        .item-desc,
        .muted {
            color: #666;
        }

        .final-block {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-top: 8px;
        }

        .notes-section {
            margin-top: 12px;
            font-size: 10px;
            line-height: 1.45;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .bottom-area {
            width: 100%;
            margin-top: 18px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .tax-summary {
            width: 48%;
            float: left;
        }

        .totals-box {
            width: 34%;
            float: right;
            font-size: 11px;
        }

        .tax-summary table,
        .totals-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .tax-summary th,
        .tax-summary td,
        .totals-box td {
            padding: 4px 6px;
        }

        .tax-summary th {
            text-align: left;
            color: #444;
            font-weight: bold;
            border-bottom: 1px solid #dcdcdc;
        }

        .tax-summary td {
            border-bottom: 1px solid #efefef;
        }

        .totals-box td:first-child {
            text-align: right;
            font-weight: bold;
            width: 62%;
        }

        .totals-box td:last-child {
            text-align: right;
            width: 38%;
        }

        .amount-to-pay {
            margin-top: 8px;
            background: #1f6fa6;
            color: #fff;
            font-weight: bold;
        }

        .amount-to-pay td {
            padding: 7px 8px;
        }

        .bank-and-qr {
            width: 100%;
            margin-top: 22px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .bank-box {
            width: 52%;
            float: left;
            font-size: 10px;
            line-height: 1.6;
        }

        .bank-box .label {
            display: inline-block;
            width: 72px;
            color: #555;
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
        $companyProfile = $budget->owner?->companyProfile;
        $customer = $budget->customer;

        $companyName = $companyProfile?->company_name;
        $companyAddressLine1 = $companyProfile?->address_line_1;
        $companyAddressLine2 = $companyProfile?->address_line_2;
        $companyPostalCode = $companyProfile?->postal_code;
        $companyPostalCodeSuffix = $companyProfile?->postal_code_suffix;
        $companyPostalDesignation = $companyProfile?->postal_designation;
        $companyCity = $companyProfile?->city;
        $companyCountryCode = $companyProfile?->country_code ?: 'PT';
        $companyTaxNumber = $companyProfile?->tax_number;
        $companyPhone = $companyProfile?->phone;
        $companyEmail = $companyProfile?->email;
        $companyWebsite = $companyProfile?->website;
        $companyBankName = $companyProfile?->bank_name;
        $companyIban = $companyProfile?->bank_iban;
        $companyBicSwift = $companyProfile?->bank_bic_swift;
        $companyLogoPath = $companyProfile?->logo_path ? public_path('storage/' . $companyProfile->logo_path) : null;

        $customerAddressLine1 = $customer?->address_line_1;
        $customerAddressLine2 = $customer?->address_line_2;
        $customerPostalCode = $customer?->postal_code;
        $customerCity = $customer?->city;
        $customerCountry = $customer?->country ?: 'Portugal';
        $customerTaxNumber = $customer?->nif;

        $hasExemption = $budget->items->contains(function ($line) {
            return !empty($line->tax_exemption_reason);
        });

        $hasHeaderContextRow = !empty($budget->designation) || !empty($budget->project_name) || !empty($budget->zone);
        $minRows = $hasExemption ? 3 : 4;
        $currentRows = $budget->items->count() + ($hasHeaderContextRow ? 1 : 0);
        $fillerRows = max(0, $minRows - $currentRows);
    @endphp

    <div class="document">
        <div class="small-top-line clearfix">
            @if($companyLogoPath && file_exists($companyLogoPath))
                <img src="{{ $companyLogoPath }}" alt="Logótipo" class="logo-image">
            @else
                <div class="logo-box">
                    LOGO
                </div>
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

                        @if($companyWebsite)
                            {{ $companyWebsite }}
                        @endif
                    </div>
                </td>

                <td class="top-left">
                    <div class="customer-block">
                        <div class="title">Exmo.(s) Sr.(s)</div>

                        <strong>{{ $customer?->name ?? '—' }}</strong><br>

                        @if($customerAddressLine1)
                            {{ $customerAddressLine1 }}<br>
                        @endif

                        @if($customerAddressLine2)
                            {{ $customerAddressLine2 }}<br>
                        @endif

                        @if($customerPostalCode || $customerCity)
                            {{ trim(($customerPostalCode ?: '') . ' ' . ($customerCity ?: '')) }}<br>
                        @endif

                        {{ $customerCountry }}<br>

                        @if($customerTaxNumber)
                            V/Contribuinte {{ $customerTaxNumber }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Orçamento Nº</th>
                    <th style="width: 16%;">Data</th>
                    <th style="width: 16%;">Validade</th>
                    <th style="width: 16%;">Ref.</th>
                    <th style="width: 16%;">Cond. Pagamento</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $budget->code }}</td>
                    <td>{{ $budget->budget_date?->format('Y-m-d') ?? '—' }}</td>
                    <td></td>
                    <td>{{ $budget->designation ?: '—' }}</td>
                    <td>{{ $budget->zone ?: '—' }}</td>
                </tr>
            </tbody>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 46%;">Designação</th>
                    <th style="width: 8%;">Qtd.</th>
                    <th style="width: 8%;">Un.</th>
                    <th style="width: 14%;">Preço Un.</th>
                    <th style="width: 8%;">Dsc(%)</th>
                    <th style="width: 8%;">IVA(%)</th>
                    <th style="width: 14%;" class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($budget->designation) || !empty($budget->project_name) || !empty($budget->zone))
                    <tr>
                        <td colspan="7" class="muted">
                            @if(!empty($budget->designation))
                                ({{ $budget->designation }})
                            @elseif(!empty($budget->project_name))
                                ({{ $budget->project_name }})
                            @elseif(!empty($budget->zone))
                                ({{ $budget->zone }})
                            @endif
                        </td>
                    </tr>
                @endif

                @forelse($budget->items as $line)
                    <tr>
                        <td>
                            @if(!empty($line->item_code))
                                <div>{{ $line->item_code }}</div>
                            @endif

                            <div class="item-name">{{ $line->item_name }}</div>

                            @if(!empty($line->description))
                                <div class="item-desc">{{ $line->description }}</div>
                            @endif

                            @if(!empty($line->notes))
                                <div class="item-desc">Obs.: {{ $line->notes }}</div>
                            @endif
                        </td>
                        <td>{{ number_format((float) $line->quantity, 0, ',', '.') }}</td>
                        <td>{{ $line->unit_name ?: 'un' }}</td>
                        <td class="text-right">{{ number_format((float) $line->unit_price, 2, ',', '.') }}€</td>
                        <td>{{ number_format((float) $line->unit_price, 2, ',', '.') }}€</td>
                        <td>{{ number_format((float) $line->discount_percent, 0, ',', '.') }}</td>
                        <td>
                            @if(!empty($line->tax_exemption_reason))
                                a)
                            @else
                                {{ number_format((float) $line->tax_percent, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="text-right">{{ number_format((float) $line->total, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="no-lines">Sem linhas no orçamento.</td>
                    </tr>
                @endforelse

                @for($i = 0; $i < $fillerRows; $i++)
                    <tr class="items-filler-row">
                        <td colspan="7"></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="final-block">
            @if($hasExemption)
                <div class="notes-section">
                    <strong>Motivos de Isenção:</strong><br>
                    @foreach($budget->items as $line)
                        @if(!empty($line->tax_exemption_reason))
                            a) {{ $line->tax_exemption_reason }}<br>
                            @break
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="bottom-area clearfix">
                <div class="tax-summary">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 15%;">Taxa</th>
                                <th style="width: 35%;">Designação</th>
                                <th style="width: 25%;">Incidência</th>
                                <th style="width: 25%;">Valor IVA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    @if($hasExemption)
                                        0
                                    @else
                                        {{ number_format((float) $budget->items->pluck('tax_percent')->first(), 0, ',', '.') }}%
                                    @endif
                                </td>
                                <td>{{ $hasExemption ? 'Autoliquidação' : 'IVA' }}</td>
                                <td>{{ number_format((float) $budget->subtotal, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $budget->tax_total, 2, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="totals-box">
                    <table>
                        <tr>
                            <td>Valor Ilíquido</td>
                            <td>{{ number_format((float) $budget->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Valor sem IVA</td>
                            <td>{{ number_format((float) $budget->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Valor IVA</td>
                            <td>{{ number_format((float) $budget->tax_total, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Valor Total</td>
                            <td>{{ number_format((float) $budget->total, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Retenção</td>
                            <td>0,00</td>
                        </tr>
                    </table>

                    <table class="amount-to-pay">
                        <tr>
                            <td>Valor a Pagar&nbsp;&nbsp;EUR</td>
                            <td class="text-right">{{ number_format((float) $budget->total, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="bank-and-qr clearfix">
                <div class="bank-box">
                    <span class="label">Banco</span> {{ $companyBankName ?: '—' }}<br>
                    <span class="label">IBAN</span> {{ $companyIban ?: '—' }}<br>
                    <span class="label">BIC / Swift</span> {{ $companyBicSwift ?: '—' }}
                </div>
            </div>
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
                Pág 1/1
            </div>
        </div>
    </div>
</body>
</html>
