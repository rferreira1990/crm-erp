<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envio de Orçamento {{ $budget->code }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    @php
        $company = $companyProfile;

        $displayRecipientName = trim((string) $recipientName) !== ''
            ? trim((string) $recipientName)
            : ($budget->customer?->contact_person ?: $budget->customer?->name ?: 'Cliente');

        $budgetNumber = ltrim(str_replace('ORC-', '', (string) $budget->code), '0');
        $budgetNumber = $budgetNumber !== '' ? $budgetNumber : $budget->code;

        $createdDate = $budget->created_at?->format('d/m/Y') ?: now()->format('d/m/Y');
        $budgetDate = $budget->budget_date?->format('d/m/Y') ?: $createdDate;

        $companyDisplayName = $company?->company_name ?: config('app.name');
        $companyContactPerson = $company?->contact_person ?: $companyDisplayName;
        $companyEmail = $company?->email ?: $company?->mail_from_address;
        $companyPhone = $company?->phone;
        $companyWebsite = $company?->website;
        $companyTaxNumber = $company?->tax_number;
        $companyAddressLine1 = $company?->address_line_1;
        $companyAddressLine2 = $company?->address_line_2;
        $companyPostal = trim(
            ($company?->postal_code ?: '')
            . (!empty($company?->postal_code_suffix) ? '-' . $company->postal_code_suffix : '')
        );
        $companyLocation = trim(
            $companyPostal
            . ($company?->postal_designation ? ' ' . $company->postal_designation : ($company?->city ? ' ' . $company->city : ''))
        );

        $totalValue = number_format((float) $budget->total, 2, ',', '.');
        $subtotalValue = number_format((float) $budget->subtotal, 2, ',', '.');
        $taxValue = number_format((float) $budget->tax_total, 2, ',', '.');

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
                                                        <img
                                                            src="{{ $embeddedLogo }}"
                                                            alt="{{ $companyDisplayName }}"
                                                            style="display:block; width:72px; height:72px; object-fit:contain; border-radius:14px; background:#ffffff; padding:8px;"
                                                        >
                                                    @else
                                                        <div style="width:72px; height:72px; line-height:72px; text-align:center; background:#ffffff; color:#0f172a; font-size:22px; font-weight:700; border-radius:14px;">
                                                            {{ mb_strtoupper(mb_substr($companyDisplayName, 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td valign="middle">
                                                    <div style="font-size:14px; line-height:20px; font-weight:700; letter-spacing:1.2px; text-transform:uppercase; color:#93c5fd; margin-bottom:6px;">
                                                        Envio de orçamento
                                                    </div>

                                                    <div style="font-size:30px; line-height:36px; font-weight:700; color:#ffffff; margin-bottom:8px;">
                                                        {{ $companyDisplayName }}
                                                    </div>

                                                    <div style="font-size:15px; line-height:22px; color:#cbd5e1;">
                                                        Orçamento Nº {{ $budgetNumber }} · Data {{ $budgetDate }}
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
                                        <div style="font-size:14px; line-height:20px; color:#64748b; margin-bottom:16px;">
                                            Criado em {{ $createdDate }}
                                        </div>

                                        <div style="font-size:24px; line-height:32px; font-weight:700; color:#111827; margin-bottom:22px;">
                                            Exmos. Senhores {{ $displayRecipientName }}
                                        </div>

                                        <div style="font-size:16px; line-height:28px; color:#374151; margin-bottom:22px;">
                                            Vimos por este meio enviar o orçamento solicitado em anexo, em formato PDF, para a vossa análise.
                                        </div>

                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:24px;">
                                            <tr>
                                                <td style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:20px;">
                                                    <div style="font-size:13px; line-height:18px; font-weight:700; text-transform:uppercase; letter-spacing:0.8px; color:#64748b; margin-bottom:14px;">
                                                        Resumo do orçamento
                                                    </div>

                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td style="font-size:14px; line-height:22px; color:#64748b; padding:4px 0;">Número</td>
                                                            <td align="right" style="font-size:14px; line-height:22px; color:#111827; font-weight:700; padding:4px 0;">{{ $budget->code }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="font-size:14px; line-height:22px; color:#64748b; padding:4px 0;">Data</td>
                                                            <td align="right" style="font-size:14px; line-height:22px; color:#111827; font-weight:700; padding:4px 0;">{{ $budgetDate }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="font-size:14px; line-height:22px; color:#64748b; padding:4px 0;">Valor s/ IVA</td>
                                                            <td align="right" style="font-size:14px; line-height:22px; color:#111827; font-weight:700; padding:4px 0;">{{ $subtotalValue }} €</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="font-size:14px; line-height:22px; color:#64748b; padding:4px 0;">IVA</td>
                                                            <td align="right" style="font-size:14px; line-height:22px; color:#111827; font-weight:700; padding:4px 0;">{{ $taxValue }} €</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding-top:10px; border-top:1px solid #e2e8f0; font-size:15px; line-height:24px; color:#0f172a; font-weight:700;">Total</td>
                                                            <td align="right" style="padding-top:10px; border-top:1px solid #e2e8f0; font-size:18px; line-height:24px; color:#0f172a; font-weight:700;">{{ $totalValue }} €</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        @if(!empty($emailNotes))
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:24px;">
                                                <tr>
                                                    <td style="background:#eff6ff; border:1px solid #bfdbfe; border-left:5px solid #2563eb; border-radius:14px; padding:18px 18px 18px 16px;">
                                                        <div style="font-size:14px; line-height:20px; font-weight:700; color:#1d4ed8; margin-bottom:8px;">
                                                            Observações
                                                        </div>

                                                        <div style="font-size:15px; line-height:26px; color:#1f2937;">
                                                            {!! nl2br(e($emailNotes)) !!}
                                                        </div>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif

                                        <div style="font-size:16px; line-height:28px; color:#374151; margin-bottom:10px;">
                                            Ficamos ao dispor para qualquer esclarecimento adicional.
                                        </div>

                                        <div style="font-size:16px; line-height:28px; color:#374151; margin-bottom:26px;">
                                            Com os melhores cumprimentos,<br>
                                            <strong>{{ $companyContactPerson }}</strong>
                                        </div>

                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #dbe3ef; border-radius:16px; overflow:hidden;">
                                            <tr>
                                                <td style="background:#f8fafc; padding:20px;">
                                                    <div style="font-size:18px; line-height:24px; font-weight:700; color:#0f172a; margin-bottom:4px;">
                                                        {{ $companyDisplayName }}
                                                    </div>

                                                    <div style="font-size:14px; line-height:22px; color:#475569; margin-bottom:14px;">
                                                        Soluções profissionais de pavimentos em madeira e eletricidade.
                                                    </div>

                                                    @if($companyEmail || $companyPhone || $companyWebsite)
                                                        <div style="font-size:14px; line-height:24px; color:#334155; margin-bottom:10px;">
                                                            @if($companyEmail)
                                                                <div><strong>Email:</strong> <a href="mailto:{{ $companyEmail }}" style="color:#2563eb; text-decoration:none;">{{ $companyEmail }}</a></div>
                                                            @endif

                                                            @if($companyPhone)
                                                                <div><strong>Telefone:</strong> {{ $companyPhone }}</div>
                                                            @endif

                                                            @if($companyWebsite)
                                                                <div><strong>Website:</strong> <a href="{{ $companyWebsite }}" style="color:#2563eb; text-decoration:none;">{{ $companyWebsite }}</a></div>
                                                            @endif
                                                        </div>
                                                    @endif

                                                    @if($companyAddressLine1 || $companyAddressLine2 || $companyLocation || $companyTaxNumber)
                                                        <div style="font-size:13px; line-height:22px; color:#64748b;">
                                                            @if($companyAddressLine1)
                                                                <div>{{ $companyAddressLine1 }}</div>
                                                            @endif

                                                            @if($companyAddressLine2)
                                                                <div>{{ $companyAddressLine2 }}</div>
                                                            @endif

                                                            @if($companyLocation)
                                                                <div>{{ $companyLocation }}</div>
                                                            @endif

                                                            @if($companyTaxNumber)
                                                                <div>NIF: {{ $companyTaxNumber }}</div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:0 32px 28px 32px;">
                                        <div style="border-top:1px solid #e5e7eb; padding-top:18px; font-size:12px; line-height:20px; color:#6b7280;">
                                            Este email e eventuais anexos são confidenciais e destinados exclusivamente ao seu destinatário.
                                            Se recebeu esta mensagem por engano, elimine-a de imediato e contacte o remetente.
                                            O tratamento de dados pessoais cumpre o RGPD e a Política de Privacidade disponível em www.fortiscasa.pt/privacidade.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-top:14px; text-align:center;">
                            <div style="font-size:12px; line-height:18px; color:#94a3b8;">
                                {{ $companyDisplayName }} · Orçamento {{ $budget->code }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
