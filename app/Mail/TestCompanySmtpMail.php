<?php

namespace App\Mail;

use App\Models\CompanyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestCompanySmtpMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public CompanyProfile $companyProfile
    ) {
    }

    public function envelope(): Envelope
    {
        $companyName = $this->companyProfile->company_name ?: config('app.name');

        return new Envelope(
            subject: 'Teste SMTP - ' . $companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-profile.test-smtp',
        );
    }
}
