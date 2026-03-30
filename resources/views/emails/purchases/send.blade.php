<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido de cotacao {{ $purchaseRequest->code }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    @php
        $company = $companyProfile;

        $displayRecipientName = trim((string) $recipientName) !== ''
            ? trim((string) $recipientName)
            : ($supplier?->contact_person ?: $supplier?->name ?: 'Fornecedor');

        $companyName = $company?->company_name ?: config('app.name');
        $companyEmail = $company?->email ?: $company?->mail_from_address;
        $companyPhone = $company?->phone;
        $companyTaxNumber = $company?->tax_number;

        $rfqDate = $purchaseRequest->created_at?->format('d/m/Y') ?: now()->format('d/m/Y');
        $deadline = $purchaseRequest->deadline_at?->format('d/m/Y') ?: '-';
        $lineCount = $purchaseRequest->items->count();
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:28px 10px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:700px; width:100%;">
                    <tr>
                        <td style="background:#1f6fa6; border-radius:10px 10px 0 0; padding:18px 24px; color:#ffffff;">
                            <div style="font-size:12px; letter-spacing:0.8px; text-transform:uppercase; opacity:0.9; margin-bottom:6px;">Pedido de cotacao</div>
                            <div style="font-size:26px; font-weight:700; line-height:1.2;">{{ $companyName }}</div>
                            <div style="font-size:14px; margin-top:6px; opacity:0.95;">RFQ {{ $purchaseRequest->code }} · Data {{ $rfqDate }}</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff; border:1px solid #d8dee4; border-top:none; border-radius:0 0 10px 10px; padding:26px 24px;">
                            <div style="font-size:20px; font-weight:700; color:#111827; margin-bottom:14px;">
                                Exmos. Senhores {{ $displayRecipientName }}
                            </div>

                            <div style="font-size:15px; line-height:24px; color:#374151; margin-bottom:16px;">
                                Enviamos em anexo o RFQ <strong>{{ $purchaseRequest->code }}</strong> para apresentacao da vossa proposta.
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #d8dee4; border-radius:8px; margin-bottom:16px; overflow:hidden;">
                                <tr>
                                    <td style="background:#f8fafc; border-bottom:1px solid #d8dee4; padding:10px 12px; font-size:12px; font-weight:700; color:#475569; text-transform:uppercase;">Resumo tecnico do pedido</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:14px; color:#1f2937;">
                                            <tr>
                                                <td style="padding:4px 0; color:#64748b; width:45%;">Numero RFQ</td>
                                                <td style="padding:4px 0; font-weight:700;">{{ $purchaseRequest->code }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#64748b;">Data</td>
                                                <td style="padding:4px 0; font-weight:700;">{{ $rfqDate }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#64748b;">Prazo para proposta</td>
                                                <td style="padding:4px 0; font-weight:700;">{{ $deadline }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0; color:#64748b;">Numero de linhas</td>
                                                <td style="padding:4px 0; font-weight:700;">{{ $lineCount }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if(!empty($emailNotes))
                                <div style="border:1px solid #c5d6e5; border-left:4px solid #1f6fa6; border-radius:8px; padding:12px 14px; margin-bottom:16px; background:#f8fbff;">
                                    <div style="font-size:13px; font-weight:700; color:#1f6fa6; margin-bottom:6px;">Observacoes</div>
                                    <div style="font-size:14px; line-height:22px; color:#374151;">{!! nl2br(e($emailNotes)) !!}</div>
                                </div>
                            @endif

                            <div style="font-size:15px; line-height:24px; color:#374151; margin-bottom:16px;">
                                Aguardamos o vosso envio dentro do prazo indicado.
                            </div>

                            <div style="font-size:15px; line-height:24px; color:#374151; margin-bottom:18px;">
                                Com os melhores cumprimentos,<br>
                                <strong>{{ $companyName }}</strong>
                            </div>

                            <div style="border-top:1px solid #e5e7eb; padding-top:12px; font-size:13px; line-height:22px; color:#6b7280;">
                                @if($companyEmail)
                                    <div><strong>Email:</strong> {{ $companyEmail }}</div>
                                @endif
                                @if($companyPhone)
                                    <div><strong>Telefone:</strong> {{ $companyPhone }}</div>
                                @endif
                                @if($companyTaxNumber)
                                    <div><strong>NIF:</strong> {{ $companyTaxNumber }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
