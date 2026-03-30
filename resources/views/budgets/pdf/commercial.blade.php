<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>{{ $budget->code }}</title>
    <style>
        @page {
            margin: 14mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .sheet {
            position: relative;
        }

        .watermark {
            position: fixed;
            top: 42%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-28deg);
            font-size: 72px;
            font-weight: 700;
            color: rgba(148, 163, 184, 0.2);
            letter-spacing: 8px;
            z-index: 0;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .header td {
            vertical-align: top;
        }

        .logo-image {
            max-width: 170px;
            max-height: 80px;
        }

        .logo-box {
            width: 170px;
            height: 80px;
            line-height: 80px;
            text-align: center;
            border: 1px solid #cbd5e1;
            color: #64748b;
            font-weight: 700;
        }

        .company-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .muted {
            color: #64748b;
        }

        .meta-table,
        .items-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table th,
        .meta-table td,
        .items-table th,
        .items-table td,
        .summary-table td {
            border: 1px solid #e2e8f0;
            padding: 7px 8px;
        }

        .meta-table th,
        .items-table thead th {
            background: #f8fafc;
            text-align: left;
            color: #334155;
            font-weight: 700;
        }

        .items-table {
            margin-top: 12px;
        }

        .items-table tbody tr {
            page-break-inside: avoid;
        }

        .group-row td {
            background: #eff6ff;
            font-weight: 700;
            color: #1e3a8a;
        }

        .text-right {
            text-align: right;
        }

        .summary-wrap {
            margin-top: 14px;
            width: 42%;
            margin-left: auto;
        }

        .summary-table td:first-child {
            background: #f8fafc;
            font-weight: 700;
            width: 62%;
        }

        .summary-highlight td {
            background: #0f172a;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
        }

        .notice {
            margin-top: 10px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #92400e;
            padding: 8px 10px;
            font-size: 10px;
        }

        .notes {
            margin-top: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 10px;
            font-size: 10px;
            line-height: 1.5;
        }

        .footer {
            margin-top: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>
<body>
    @php
        $showVatValues = $showVatValues ?? (($vatMode ?? 'with_vat') === 'with_vat');
        $showVatNotice = $showVatNotice ?? (($vatMode ?? 'with_vat') === 'without_vat_with_notice');
        $vatNoticeText = $vatNoticeText ?? 'Ao valor apresentado acresce IVA à taxa legal em vigor.';
        $displayTotal = $showVatValues ? (float) $budget->total : (float) $budget->subtotal;

        $useSnapshot = $budget->snapshot_generated_at !== null;
        $isDraft = $budget->status === \App\Models\Budget::STATUS_DRAFT;

        if ($useSnapshot) {
            $companyLogoRelativePath = $budget->snapshot_company_logo_path;
            $companyName = $budget->snapshot_company_name;
            $companyAddressLine1 = $budget->snapshot_company_address_line_1;
            $companyAddressLine2 = $budget->snapshot_company_address_line_2;
            $companyPostalCode = $budget->snapshot_company_postal_code;
            $companyPostalCodeSuffix = $budget->snapshot_company_postal_code_suffix;
            $companyPostalDesignation = $budget->snapshot_company_postal_designation;
            $companyCity = $budget->snapshot_company_city;
            $companyCountryCode = $budget->snapshot_company_country_code ?: 'PT';
            $companyTaxNumber = $budget->snapshot_company_tax_number;
            $companyPhone = $budget->snapshot_company_phone;
            $companyEmail = $budget->snapshot_company_email;
            $companyWebsite = $budget->snapshot_company_website;

            $customerName = $budget->snapshot_customer_name;
            $customerAddressLine1 = $budget->snapshot_customer_address_line_1;
            $customerAddressLine2 = $budget->snapshot_customer_address_line_2;
            $customerPostalCode = $budget->snapshot_customer_postal_code;
            $customerCity = $budget->snapshot_customer_city;
            $customerCountry = $budget->snapshot_customer_country ?: 'Portugal';
            $customerTaxNumber = $budget->snapshot_customer_nif;
        } else {
            $company = $companyProfile ?? \App\Models\CompanyProfile::query()->orderBy('id')->first();
            $customer = $budget->customer;

            $companyLogoRelativePath = $company?->logo_path;
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
            $companyEmail = $company?->email;
            $companyWebsite = $company?->website;

            $customerName = $customer?->name;
            $customerAddressLine1 = $customer?->address_line_1;
            $customerAddressLine2 = $customer?->address_line_2;
            $customerPostalCode = $customer?->postal_code;
            $customerCity = $customer?->city;
            $customerCountry = $customer?->country ?: 'Portugal';
            $customerTaxNumber = $customer?->nif;
        }

        $companyLogoPath = $companyLogoRelativePath ? public_path('storage/' . $companyLogoRelativePath) : null;

        $groupedItems = $budget->items
            ->groupBy(function ($line) {
                $group = trim((string) $line->item_type);

                return $group !== '' ? $group : 'Itens';
            })
            ->map(function ($lines, $groupName) use ($showVatValues) {
                return [
                    'name' => $groupName,
                    'lines' => $lines,
                    'total' => $lines->sum(function ($line) use ($showVatValues) {
                        return $showVatValues ? (float) $line->total : (float) $line->subtotal;
                    }),
                ];
            });
    @endphp

    <div class="sheet">
        @if($isDraft)
            <div class="watermark">RASCUNHO</div>
        @endif

        <div class="content">
            <table class="header">
                <tr>
                    <td style="width: 34%;">
                        @if($companyLogoPath && file_exists($companyLogoPath))
                            <img src="{{ $companyLogoPath }}" alt="Logotipo" class="logo-image">
                        @else
                            <div class="logo-box">LOGO</div>
                        @endif
                    </td>
                    <td style="width: 66%;">
                        <div class="company-name">{{ $companyName ?: 'Empresa' }}</div>
                        <div>{{ $companyAddressLine1 ?: '—' }}</div>
                        @if($companyAddressLine2)
                            <div>{{ $companyAddressLine2 }}</div>
                        @endif
                        <div>
                            {{ trim(($companyPostalCode ?: '') . (!empty($companyPostalCodeSuffix) ? '-' . $companyPostalCodeSuffix : '') . ' ' . ($companyPostalDesignation ?: $companyCity ?: '')) }}
                        </div>
                        <div>{{ $companyCountryCode === 'PT' ? 'Portugal' : $companyCountryCode }}</div>
                    </td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <th style="width: 25%;">Orcamento</th>
                    <td style="width: 25%;">{{ $budget->code }}</td>
                    <th style="width: 25%;">Data</th>
                    <td style="width: 25%;">{{ $budget->budget_date?->format('d/m/Y') ?: '—' }}</td>
                </tr>
                <tr>
                    <th>Cliente</th>
                    <td>{{ $customerName ?: '—' }}</td>
                    <th>Validade</th>
                    <td>{{ $budget->valid_until ? \Carbon\Carbon::parse($budget->valid_until)->format('d/m/Y') : '—' }}</td>
                </tr>
                <tr>
                    <th>NIF Cliente</th>
                    <td>{{ $customerTaxNumber ?: '—' }}</td>
                    <th>Cond. pagamento</th>
                    <td>{{ $budget->paymentTerm?->name ?: '—' }}</td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Descricao</th>
                        <th style="width: 16%;" class="text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groupedItems as $group)
                        <tr class="group-row">
                            <td colspan="2">{{ $group['name'] }}</td>
                        </tr>
                        @foreach($group['lines'] as $line)
                            @php
                                $lineValue = $showVatValues ? (float) $line->total : (float) $line->subtotal;
                                $lineUnit = $line->unit_code ?: $line->item?->unit?->code ?: $line->unit_name ?: 'un';
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $line->item_name }}</strong>
                                    @if($line->description)
                                        <div class="muted">{{ $line->description }}</div>
                                    @endif
                                    <div class="muted">
                                        {{ number_format((float) $line->quantity, 2, ',', '.') }} {{ $lineUnit }}
                                        × {{ number_format((float) $line->unit_price, 2, ',', '.') }} €
                                    </div>
                                </td>
                                <td class="text-right">{{ number_format($lineValue, 2, ',', '.') }} €</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="text-right"><strong>Total {{ $group['name'] }}</strong></td>
                            <td class="text-right"><strong>{{ number_format((float) $group['total'], 2, ',', '.') }} €</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="muted">Sem linhas no orcamento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary-wrap">
                <table class="summary-table">
                    <tr>
                        <td>Valor sem IVA</td>
                        <td class="text-right">{{ number_format((float) $budget->subtotal, 2, ',', '.') }} €</td>
                    </tr>
                    @if($showVatValues)
                        <tr>
                            <td>Valor IVA</td>
                            <td class="text-right">{{ number_format((float) $budget->tax_total, 2, ',', '.') }} €</td>
                        </tr>
                        <tr class="summary-highlight">
                            <td>Total com IVA</td>
                            <td class="text-right">{{ number_format((float) $budget->total, 2, ',', '.') }} €</td>
                        </tr>
                    @else
                        <tr class="summary-highlight">
                            <td>Total apresentado</td>
                            <td class="text-right">{{ number_format((float) $displayTotal, 2, ',', '.') }} €</td>
                        </tr>
                    @endif
                </table>
            </div>

            @if($showVatNotice)
                <div class="notice">{{ $vatNoticeText }}</div>
            @endif

            @if(!empty($budget->notes))
                <div class="notes">
                    <strong>Observacoes:</strong><br>
                    {!! nl2br(e($budget->notes)) !!}
                </div>
            @endif

            <div class="footer">
                @if($companyPhone)
                    <strong>Telefone:</strong> {{ $companyPhone }}
                @endif
                @if($companyEmail)
                    &nbsp; | &nbsp;<strong>Email:</strong> {{ $companyEmail }}
                @endif
                @if($companyWebsite)
                    &nbsp; | &nbsp;<strong>Website:</strong> {{ $companyWebsite }}
                @endif
                @if($companyTaxNumber)
                    &nbsp; | &nbsp;<strong>NIF:</strong> {{ $companyTaxNumber }}
                @endif
                @if($customerAddressLine1 || $customerAddressLine2 || $customerPostalCode || $customerCity || $customerCountry)
                    <div style="margin-top: 4px;">
                        <strong>Morada cliente:</strong>
                        {{ trim(($customerAddressLine1 ?: '') . ' ' . ($customerAddressLine2 ?: '') . ' ' . ($customerPostalCode ?: '') . ' ' . ($customerCity ?: '') . ' ' . ($customerCountry ?: '')) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
