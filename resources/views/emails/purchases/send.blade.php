<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido de cotacao {{ $purchaseRequest->code }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    @php
        $company = $companyProfile;
        $displayRecipientName = trim((string) $recipientName) !== ''
            ? trim((string) $recipientName)
            : ($supplier?->contact_person ?: $supplier?->name ?: 'Fornecedor');

        $companyDisplayName = $company?->company_name ?: config('app.name');
        $companyEmail = $company?->email ?: $company?->mail_from_address;
        $companyPhone = $company?->phone;

        $deadline = $purchaseRequest->deadline_at?->format('d/m/Y') ?: '-';
        $createdDate = $purchaseRequest->created_at?->format('d/m/Y') ?: now()->format('d/m/Y');
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3f6fb; width:100%;">
        <tr>
            <td align="center" style="padding:30px 12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:700px; width:100%;">
                    <tr>
                        <td style="background:#0f172a; border-radius:18px 18px 0 0; padding:26px 30px;">
                            <div style="font-size:13px; line-height:18px; color:#93c5fd; text-transform:uppercase; letter-spacing:1px; font-weight:700; margin-bottom:8px;">
                                Pedido de cotacao
                            </div>
                            <div style="font-size:29px; line-height:35px; font-weight:700; color:#ffffff; margin-bottom:7px;">
                                {{ $companyDisplayName }}
                            </div>
                            <div style="font-size:15px; line-height:22px; color:#cbd5e1;">
                                RFQ {{ $purchaseRequest->code }} · Criado em {{ $createdDate }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff; border:1px solid #dbe3ef; border-top:none; border-radius:0 0 18px 18px; padding:30px;">
                            <div style="font-size:24px; line-height:30px; font-weight:700; color:#111827; margin-bottom:18px;">
                                Exmos. Senhores {{ $displayRecipientName }}
                            </div>

                            <div style="font-size:16px; line-height:28px; color:#374151; margin-bottom:18px;">
                                Enviamos em anexo o pedido de cotacao <strong>{{ $purchaseRequest->code }}</strong> para a vossa melhor proposta.
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="font-size:14px; line-height:22px; color:#64748b;">RFQ</td>
                                                <td align="right" style="font-size:14px; line-height:22px; color:#111827; font-weight:700;">{{ $purchaseRequest->code }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:14px; line-height:22px; color:#64748b;">Prazo para propostas</td>
                                                <td align="right" style="font-size:14px; line-height:22px; color:#111827; font-weight:700;">{{ $deadline }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size:14px; line-height:22px; color:#64748b;">Linhas solicitadas</td>
                                                <td align="right" style="font-size:14px; line-height:22px; color:#111827; font-weight:700;">{{ $purchaseRequest->items->count() }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if(!empty($emailNotes))
                                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-left:5px solid #2563eb; border-radius:12px; padding:14px 14px 14px 12px; margin-bottom:18px;">
                                    <div style="font-size:14px; line-height:20px; font-weight:700; color:#1d4ed8; margin-bottom:6px;">Observacoes</div>
                                    <div style="font-size:15px; line-height:24px; color:#1f2937;">
                                        {!! nl2br(e($emailNotes)) !!}
                                    </div>
                                </div>
                            @endif

                            <div style="font-size:16px; line-height:28px; color:#374151; margin-bottom:18px;">
                                Aguardamos o vosso envio dentro do prazo indicado.
                            </div>

                            <div style="font-size:15px; line-height:26px; color:#374151; margin-bottom:20px;">
                                Com os melhores cumprimentos,
                                <br>
                                <strong>{{ $companyDisplayName }}</strong>
                            </div>

                            @if($companyEmail || $companyPhone)
                                <div style="border-top:1px solid #e5e7eb; padding-top:14px; font-size:13px; line-height:22px; color:#64748b;">
                                    @if($companyEmail)
                                        <div><strong>Email:</strong> {{ $companyEmail }}</div>
                                    @endif
                                    @if($companyPhone)
                                        <div><strong>Telefone:</strong> {{ $companyPhone }}</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
