<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $budget->code }}</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #222;">
    <p>Boa tarde,</p>

    <p>
        Enviamos em anexo o orçamento <strong>{{ $budget->code }}</strong>.
    </p>

    @if(!empty($budget->designation))
        <p>
            <strong>Designação:</strong> {{ $budget->designation }}
        </p>
    @endif

    <p>
        Qualquer questão, estamos disponíveis.
    </p>

    <p>
        Cumprimentos,<br>
        {{ $budget->owner?->companyProfile?->mail_from_name ?: $budget->owner?->companyProfile?->company_name ?: config('app.name') }}
    </p>
</body>
</html>
