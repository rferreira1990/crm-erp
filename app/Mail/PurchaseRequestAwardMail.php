<?php

namespace App\Mail;

use App\Models\CompanyProfile;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestAward;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestAwardMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public PurchaseRequestAward $award,
        public string $pdfContent,
        public string $pdfFileName,
        public string $fromAddress,
        public string $fromName,
        public string $recipientName = '',
        public string $emailNotes = '',
        public ?CompanyProfile $companyProfile = null,
        public ?Supplier $supplier = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            subject: 'Adjudicacao RFQ ' . $this->purchaseRequest->code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchases.award-send',
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

