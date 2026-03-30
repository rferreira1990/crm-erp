<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>RFQ {{ $purchaseRequest->code }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #0f172a; margin: 0; font-size: 12px; }
        .page { padding: 24px 26px; }
        .header { border-bottom: 2px solid #1e293b; padding-bottom: 14px; margin-bottom: 16px; }
        .title { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .subtitle { font-size: 11px; color: #64748b; }
        .section { margin-bottom: 16px; }
        .block-title { font-size: 11px; font-weight: 700; color: #1e293b; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.4px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 4px 6px; border: 1px solid #cbd5e1; vertical-align: top; }
        .meta-label { width: 24%; background: #f8fafc; font-weight: 700; color: #334155; }
        .lines { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .lines th { background: #e2e8f0; color: #0f172a; text-align: left; border: 1px solid #94a3b8; padding: 6px; font-size: 11px; }
        .lines td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        .text-end { text-align: right; }
        .notes { white-space: pre-wrap; }
        .footer { margin-top: 18px; font-size: 10px; color: #64748b; border-top: 1px solid #cbd5e1; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $company = $companyProfile;

        $companyName = $company?->company_name ?: config('app.name');
        $companyTaxNumber = $company?->tax_number ?: '-';
        $companyAddress = trim(implode(', ', array_filter([
            $company?->address_line_1,
            $company?->address_line_2,
            trim(implode(' ', array_filter([
                trim(($company?->postal_code ?: '') . (!empty($company?->postal_code_suffix) ? '-' . $company->postal_code_suffix : '')),
                $company?->postal_designation ?: $company?->city,
            ]))),
        ]))) ?: '-';

        $supplierName = $supplier?->name ?: 'Fornecedor nao definido';
        $supplierTaxNumber = $supplier?->tax_number ?: '-';
        $supplierEmail = $supplier?->habitual_order_email ?: $supplier?->email ?: '-';

        $requestDate = $purchaseRequest->created_at?->format('d/m/Y') ?: now()->format('d/m/Y');
        $deadlineDate = $purchaseRequest->deadline_at?->format('d/m/Y') ?: '-';
    @endphp

    <div class="page">
        <div class="header">
            <div class="title">Pedido de Cotacao {{ $purchaseRequest->code }}</div>
            <div class="subtitle">Documento de consulta a fornecedor (sem valores)</div>
        </div>

        <div class="section">
            <div class="block-title">Empresa emissora</div>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Empresa</td>
                    <td>{{ $companyName }}</td>
                    <td class="meta-label">NIF</td>
                    <td>{{ $companyTaxNumber }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Morada</td>
                    <td colspan="3">{{ $companyAddress }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="block-title">Fornecedor</div>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Nome</td>
                    <td>{{ $supplierName }}</td>
                    <td class="meta-label">NIF</td>
                    <td>{{ $supplierTaxNumber }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Email</td>
                    <td colspan="3">{{ $supplierEmail }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="block-title">Dados do RFQ</div>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Numero</td>
                    <td>{{ $purchaseRequest->code }}</td>
                    <td class="meta-label">Data</td>
                    <td>{{ $requestDate }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Obra</td>
                    <td>{{ $purchaseRequest->work?->code ? $purchaseRequest->work->code . ' - ' . $purchaseRequest->work->name : '-' }}</td>
                    <td class="meta-label">Prazo proposta</td>
                    <td>{{ $deadlineDate }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="block-title">Linhas solicitadas</div>
            <table class="lines">
                <thead>
                    <tr>
                        <th style="width: 6%;">#</th>
                        <th style="width: 17%;">Artigo</th>
                        <th>Descricao</th>
                        <th style="width: 10%;" class="text-end">Qtd</th>
                        <th style="width: 10%;">Unidade</th>
                        <th style="width: 25%;">Observacoes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseRequest->items as $line)
                        <tr>
                            <td>{{ $line->sort_order }}</td>
                            <td>
                                @if ($line->item)
                                    {{ $line->item->code }}
                                    <br>
                                    {{ $line->item->name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $line->description }}</td>
                            <td class="text-end">{{ number_format((float) $line->qty, 3, ',', '.') }}</td>
                            <td>{{ $line->unit_snapshot ?: '-' }}</td>
                            <td class="notes">{{ $line->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Sem linhas registadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (!empty($purchaseRequest->notes))
            <div class="section">
                <div class="block-title">Notas finais</div>
                <table class="meta-table">
                    <tr>
                        <td class="notes">{{ $purchaseRequest->notes }}</td>
                    </tr>
                </table>
            </div>
        @endif

        <div class="footer">
            Este documento e um pedido de cotacao e nao apresenta valores de venda.
        </div>
    </div>
</body>
</html>
