<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Devolucao {{ $purchaseReturn->return_number }}</title>
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
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .notes-section { margin-top: 12px; font-size: 10px; line-height: 1.45; page-break-inside: avoid; }
        .footer-bar { position: fixed; left: 0; right: 0; bottom: 0; width: 100%; background: #1f6fa6; color: #fff; padding: 10px 12px; font-size: 10px; box-sizing: border-box; z-index: 2; }
        .footer-col { width: 24%; display: inline-block; vertical-align: top; }
        .footer-col strong { display: block; margin-bottom: 2px; }
        .page-number { float: right; text-align: right; }
    </style>
</head>
<body>
@php
    $company = $companyProfile;
    $companyLogoPath = $company?->logo_path ? public_path('storage/' . ltrim($company->logo_path, '/')) : null;

    $supplier = $order->supplier;
    $supplierName = $supplier?->name ?: 'Fornecedor';
    $supplierAddress = $supplier?->address;
    $supplierPostalCode = $supplier?->postal_code;
    $supplierCity = $supplier?->city;
    $supplierCountry = $supplier?->country ?: 'Portugal';
    $supplierTaxNumber = $supplier?->tax_number;
    $supplierEmail = $supplier?->habitual_order_email ?: $supplier?->email;
    $supplierPhone = $supplier?->phone;

    $companyName = $company?->company_name;
    $companyAddressLine1 = $company?->address_line_1;
    $companyAddressLine2 = $company?->address_line_2;
    $companyPostalCode = $company?->postal_code;
    $companyPostalCodeSuffix = $company?->postal_code_suffix;
    $companyPostalDesignation = $company?->postal_designation;
    $companyCity = $company?->city;
    $companyTaxNumber = $company?->tax_number;
    $companyPhone = $company?->phone;
    $companyEmail = $company?->email ?: $company?->mail_from_address;

    $lines = $purchaseReturn->items
        ->sortBy(fn ($line) => [(int) ($line->orderItem?->sort_order ?? 999999), (int) $line->id])
        ->values();

    $minRows = 8;
    $fillerRows = max(0, $minRows - $lines->count());
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
                        Portugal<br>
                        @if($companyTaxNumber)NIF {{ $companyTaxNumber }}<br>@endif
                        @if($companyPhone)Tel. {{ $companyPhone }}<br>@endif
                        @if($companyEmail){{ $companyEmail }}<br>@endif
                    </div>
                </td>
                <td class="top-left">
                    <div class="supplier-block">
                        <div class="title">Exmo.(s) Sr.(s)</div>
                        <strong>{{ $supplier?->code ? $supplier->code . ' - ' . $supplierName : $supplierName }}</strong><br>
                        @if($supplierAddress){{ $supplierAddress }}<br>@endif
                        @if($supplierPostalCode || $supplierCity){{ trim(($supplierPostalCode ?: '') . ' ' . ($supplierCity ?: '')) }}<br>@endif
                        {{ $supplierCountry }}<br>
                        @if($supplierTaxNumber)V/Contribuinte {{ $supplierTaxNumber }}<br>@endif
                        @if($supplierPhone)Tel. {{ $supplierPhone }}<br>@endif
                        @if($supplierEmail){{ $supplierEmail }}@endif
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Devolucao</th>
                    <th style="width: 14%;">Data</th>
                    <th style="width: 18%;">Encomenda</th>
                    <th style="width: 20%;">RFQ origem</th>
                    <th style="width: 15%;">Estado</th>
                    <th style="width: 15%;">Rececao ref.</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $purchaseReturn->return_number }}</td>
                    <td>{{ $purchaseReturn->return_date?->format('d/m/Y') ?: '-' }}</td>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $purchaseRequest->code }}</td>
                    <td>{{ $purchaseReturn->statusLabel() }}</td>
                    <td>{{ $purchaseReturn->linkedReceipt?->receipt_number ?: '-' }}</td>
                </tr>
            </tbody>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 16%;">Artigo</th>
                    <th style="width: 38%;">Descricao</th>
                    <th style="width: 10%;" class="text-right">Qtd devolvida</th>
                    <th style="width: 8%;" class="text-center">Un.</th>
                    <th style="width: 20%;">Motivo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lines as $line)
                    @php
                        $orderLine = $line->orderItem;
                        $itemCode = $orderLine?->item?->code ?: 'MANUAL';
                        $description = $orderLine?->description ?: '-';
                        $unitCode = $orderLine?->item?->unit?->code ?: ($orderLine?->unit_snapshot ?: '-');
                    @endphp
                    <tr>
                        <td>{{ $orderLine?->sort_order ?: '-' }}</td>
                        <td>{{ $itemCode }}</td>
                        <td>{{ $description }}</td>
                        <td class="text-right">{{ number_format((float) $line->quantity_returned, 3, ',', '.') }}</td>
                        <td class="text-center">{{ $unitCode }}</td>
                        <td>{{ $line->reason ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Sem linhas devolvidas.</td>
                    </tr>
                @endforelse

                @for($i = 0; $i < $fillerRows; $i++)
                    <tr class="items-filler-row"><td colspan="6"></td></tr>
                @endfor
            </tbody>
        </table>

        @if(!empty($purchaseReturn->notes))
            <div class="notes-section"><strong>Notas:</strong><br>{{ $purchaseReturn->notes }}</div>
        @endif

        @if($purchaseReturn->isClosed())
            <div class="notes-section">
                <strong>Fecho operacional:</strong><br>
                Fechada em {{ $purchaseReturn->closed_at?->format('d/m/Y H:i') ?: '-' }}
                por {{ $purchaseReturn->closedBy?->name ?: '-' }}.
            </div>
        @endif

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

