<?php

namespace App\Mail;

use App\Models\Budget;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($this->fromAddress, $this->fromName),
            subject: 'Orçamento ' . $this->budget->code,
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
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $this->pdfContent,
                $this->pdfFileName
            )->withMime('application/pdf'),
        ];
    }
}
