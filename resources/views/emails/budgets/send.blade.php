<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Envio de Orçamento {{ $budget->code }}</title>
</head>
<body style="margin:0; padding:0; background-color:#eef1f5; font-family:Arial, Helvetica, sans-serif; color:#2f2f2f;">
    @php
        $company = $companyProfile;
        $displayRecipientName = trim($recipientName) !== ''
            ? $recipientName
            : ($budget->customer?->contact_person ?: $budget->customer?->name ?: 'Cliente');

        $budgetNumber = ltrim(str_replace('ORC-', '', (string) $budget->code), '0');
        $budgetNumber = $budgetNumber !== '' ? $budgetNumber : $budget->code;

        $createdDate = $budget->created_at?->format('d/m/Y') ?: now()->format('d/m/Y');
        $companyDisplayName = $company?->company_name ?: config('app.name');
        $companyContactPerson = $company?->contact_person ?: $companyDisplayName;
        $companyEmail = $company?->email ?: $company?->mail_from_address;
        $companyPhone = $company?->phone;
        $companyWebsite = $company?->website;
    @endphp

    <div style="width:100%; background-color:#eef1f5; padding:24px 12px;">
        <div style="max-width:640px; margin:0 auto; background-color:#ffffff; border:1px solid #d8dde6; border-radius:14px; padding:28px; box-sizing:border-box;">

            <div style="font-size:28px; font-weight:700; color:#1f1f1f; margin-bottom:18px;">
                Envio de Orçamento Nº {{ $budgetNumber }}
            </div>

            <div style="font-size:18px; color:#6a7480; margin-bottom:28px;">
                Nº {{ $budgetNumber }} • Criada em {{ $createdDate }}
            </div>

            <div style="font-size:22px; line-height:1.7; color:#2f2f2f;">
                <p style="margin:0 0 18px 0;">
                    Exmos. Senhores {{ $displayRecipientName }}
                </p>

                <p style="margin:0 0 18px 0;">
                    Vimos por este meio enviar o orçamento solicitado em anexo.
                </p>

                @if(!empty($emailNotes))
                    <div style="margin:22px 0; padding:18px; background:#f7f9fc; border:1px solid #dce4ef; border-radius:10px;">
                        <div style="font-size:16px; font-weight:700; color:#1e5aa8; margin-bottom:10px;">
                            Observações
                        </div>

                        <div style="font-size:16px; line-height:1.7; color:#2f2f2f;">
                            {!! nl2br(e($emailNotes)) !!}
                        </div>
                    </div>
                @endif

                <p style="margin:0 0 6px 0;">
                    Com os melhores cumprimentos,
                </p>

                <p style="margin:0 0 24px 0;">
                    Equipa <span style="background:#ffe082; padding:0 4px;">{{ $companyDisplayName }}</span>
                </p>
            </div>

            <div style="border:2px solid #1e5aa8; border-radius:12px; padding:18px; margin-top:8px;">
                <div style="font-size:24px; font-weight:700; color:#0f5db7; margin-bottom:10px;">
                    {{ $companyContactPerson }}
                </div>

                <div style="font-size:18px; color:#4c4c4c; margin-bottom:18px;">
                    {{ $companyDisplayName }}
                </div>

                @if($companyEmail)
                    <div style="font-size:18px; margin-bottom:10px; color:#0f5db7;">
                        ✉ <a href="mailto:{{ $companyEmail }}" style="color:#0f5db7; text-decoration:underline;">{{ $companyEmail }}</a>
                    </div>
                @endif

                @if($companyPhone)
                    <div style="font-size:18px; margin-bottom:10px; color:#2f2f2f;">
                        ☎ {{ $companyPhone }}
                    </div>
                @endif

                @if($companyWebsite)
                    <div style="font-size:18px; margin-bottom:16px; color:#0f5db7;">
                        🌐 <a href="{{ $companyWebsite }}" style="color:#0f5db7; text-decoration:underline;">{{ $companyWebsite }}</a>
                    </div>
                @endif

                <div style="font-size:17px; color:#0f5db7; margin-bottom:18px;">
                    Soluções profissionais de pavimentos em madeira e eletricidade.
                </div>

                <div style="border-top:1px solid #d6dbe3; padding-top:16px; font-size:13px; line-height:1.6; color:#7a7a7a;">
                    Este email e eventuais anexos são confidenciais e destinados exclusivamente ao seu destinatário.
                    Se recebeu esta mensagem por engano, elimine-a de imediato e contacte o remetente.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
