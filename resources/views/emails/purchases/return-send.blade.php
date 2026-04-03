<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolucao {{ $purchaseReturn->return_number }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    @php
        $company = $companyProfile;

        $displayRecipientName = trim((string) $recipientName) !== ''
            ? trim((string) $recipientName)
            : ($order->supplier?->contact_person ?: $order->supplier?->name ?: 'Fornecedor');

        $companyDisplayName = $company?->company_name ?: config('app.name');
        $companyContactPerson = $company?->contact_person ?: $companyDisplayName;
        $companyEmail = $company?->email ?: $company?->mail_from_address;
        $companyPhone = $company?->phone;
        $companyWebsite = $company?->website;
        $companyTaxNumber = $company?->tax_number;

        $companyLogoPath = !empty($company?->logo_path)
            ? public_path('storage/' . ltrim($company->logo_path, '/'))
            : null;

        $embeddedLogo = null;
        if ($companyLogoPath && file_exists($companyLogoPath) && isset($message)) {
            try {
                $embeddedLogo = $message->embed($companyLogoPath);
            } catch (\Throwable $e) {
                $embeddedLogo = null;
            }
        }
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f3f6fb; margin:0; padding:0; width:100%;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:700px; width:100%;">
                    <tr>
                        <td style="padding-bottom:18px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#0f172a; border-radius:20px 20px 0 0;">
                                <tr>
                                    <td style="padding:28px 32px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td valign="middle" style="width:88px; padding-right:18px;">
                                                    @if($embeddedLogo)
                                                        <img src="{{ $embeddedLogo }}" alt="{{ $companyDisplayName }}" style="display:block; width:72px; height:72px; object-fit:contain; border-radius:14px; background:#ffffff; padding:8px;">
                                                    @else
                                                        <div style="width:72px; height:72px; line-height:72px; text-align:center; background:#ffffff; color:#0f172a; font-size:22px; font-weight:700; border-radius:14px;">
                                                            {{ mb_strtoupper(mb_substr($companyDisplayName, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td valign="middle">
                                                    <div style="font-size:14px; line-height:20px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:#93c5fd; margin-bottom:6px;">Envio de devolucao a fornecedor</div>
                                                    <div style="font-size:30px; line-height:36px; font-weight:700; color:#ffffff; margin-bottom:8px;">{{ $companyDisplayName }}</div>
                                                    <div style="font-size:15px; line-height:22px; color:#cbd5e1;">
                                                        {{ $purchaseReturn->return_number }} · {{ $purchaseReturn->return_date?->format('d/m/Y') ?: '-' }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ffffff; border:1px solid #dbe3ef; border-top:none; border-radius:0 0 20px 20px; overflow:hidden;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding:34px 32px 24px 32px;">
                                        <div style="font-size:24px; line-height:32px; font-weight:700; color:#111827; margin-bottom:22px;">Exmos. Senhores {{ $displayRecipientName }}</div>

                                        <div style="font-size:16px; line-height:28px; color:#374151; margin-bottom:22px;">
                                            Enviamos em anexo o documento de devolucao {{ $purchaseReturn->return_number }}, referente a encomenda #{{ $order->id }} (RFQ {{ $purchaseRequest->code }}).
                                        </div>

                                        @if(!empty($emailNotes))
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:24px;">
                                                <tr>
                                                    <td style="background:#eff6ff; border:1px solid #bfdbfe; border-left:5px solid #2563eb; border-radius:14px; padding:18px 18px 18px 16px;">
                                                        <div style="font-size:14px; line-height:20px; font-weight:700; color:#1d4ed8; margin-bottom:8px;">Observacoes</div>
                                                        <div style="font-size:15px; line-height:26px; color:#1f2937;">{!! nl2br(e($emailNotes)) !!}</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif

                                        <div style="font-size:16px; line-height:28px; color:#374151; margin-bottom:26px;">Com os melhores cumprimentos,<br><strong>{{ $companyContactPerson }}</strong></div>

                                        <div style="border-top:1px solid #e5e7eb; padding-top:16px; font-size:13px; line-height:22px; color:#6b7280;">
                                            @if($companyEmail)<div><strong>Email:</strong> {{ $companyEmail }}</div>@endif
                                            @if($companyPhone)<div><strong>Telefone:</strong> {{ $companyPhone }}</div>@endif
                                            @if($companyWebsite)<div><strong>Website:</strong> {{ $companyWebsite }}</div>@endif
                                            @if($companyTaxNumber)<div><strong>NIF:</strong> {{ $companyTaxNumber }}</div>@endif
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

