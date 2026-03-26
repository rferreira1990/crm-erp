<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Teste SMTP</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f9; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    @php
        $companyName = $companyProfile->company_name ?: config('app.name');
        $companyEmail = $companyProfile->email ?: $companyProfile->mail_from_address;
        $companyPhone = $companyProfile->phone;
        $companyWebsite = $companyProfile->website;
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%; background:#f4f6f9;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px; background:#ffffff; border:1px solid #dbe3ef; border-radius:16px; overflow:hidden;">
                    <tr>
                        <td style="background:#0f172a; padding:24px 28px;">
                            <div style="font-size:13px; line-height:18px; text-transform:uppercase; letter-spacing:1px; color:#93c5fd; margin-bottom:8px;">
                                Teste de configuração SMTP
                            </div>
                            <div style="font-size:28px; line-height:34px; font-weight:700; color:#ffffff;">
                                {{ $companyName }}
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <div style="font-size:18px; line-height:28px; font-weight:700; color:#111827; margin-bottom:16px;">
                                O envio de email está a funcionar.
                            </div>

                            <div style="font-size:15px; line-height:26px; color:#374151; margin-bottom:18px;">
                                Este email foi enviado com sucesso usando a configuração SMTP atualmente gravada nos dados da empresa.
                            </div>

                            <div style="font-size:15px; line-height:26px; color:#374151; margin-bottom:22px;">
                                Se recebeste esta mensagem, a ligação ao servidor SMTP, autenticação, remetente e envio básico estão válidos.
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="font-size:14px; line-height:22px; color:#64748b; margin-bottom:8px;">
                                            Resumo
                                        </div>

                                        <div style="font-size:14px; line-height:24px; color:#111827;">
                                            <div><strong>Empresa:</strong> {{ $companyName }}</div>
                                            <div><strong>Data/Hora:</strong> {{ now()->format('d/m/Y H:i:s') }}</div>
                                            <div><strong>Remetente:</strong> {{ $companyProfile->mail_from_name }} &lt;{{ $companyProfile->mail_from_address }}&gt;</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            @if($companyEmail || $companyPhone || $companyWebsite)
                                <div style="margin-top:24px; font-size:14px; line-height:24px; color:#475569;">
                                    @if($companyEmail)
                                        <div><strong>Email:</strong> {{ $companyEmail }}</div>
                                    @endif

                                    @if($companyPhone)
                                        <div><strong>Telefone:</strong> {{ $companyPhone }}</div>
                                    @endif

                                    @if($companyWebsite)
                                        <div><strong>Website:</strong> {{ $companyWebsite }}</div>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 28px 24px 28px;">
                            <div style="border-top:1px solid #e5e7eb; padding-top:16px; font-size:12px; line-height:20px; color:#6b7280;">
                                Email automático de validação da configuração SMTP.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
