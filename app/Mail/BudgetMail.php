<?php

namespace App\Mail;

use App\Models\Budget;
use App\Models\CompanyProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BudgetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Budget $budget,
        public string $pdfContent,
        public string $pdfFileName,
        public string $fromAddress,
        public string $fromName,
        public string $recipientName = '',
        public string $emailNotes = '',
        public ?CompanyProfile $companyProfile = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            subject: 'Envio de Orçamento Nº ' . ltrim(str_replace('ORC-', '', (string) $this->budget->code), '0'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.budgets.send',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent,
                $this->pdfFileName
            )->withMime('application/pdf'),
        ];
    }
}
