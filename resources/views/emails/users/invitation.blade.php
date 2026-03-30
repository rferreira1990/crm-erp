<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Convite de acesso</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Ola{{ $invitation->invitee_name ? ' ' . e($invitation->invitee_name) : '' }},</p>

    <p>
        Recebeste um convite para aceder ao sistema <strong>{{ config('app.name') }}</strong>.
    </p>

    <p>
        Para concluir o teu registo, clica no link abaixo:
    </p>

    <p>
        <a href="{{ $invitationUrl }}">{{ $invitationUrl }}</a>
    </p>

    <p>
        Este link expira em <strong>{{ $invitation->expires_at?->format('d/m/Y H:i') }}</strong>.
    </p>

    <p>Se nao estavas a espera deste convite, ignora este email.</p>
</body>
</html>
