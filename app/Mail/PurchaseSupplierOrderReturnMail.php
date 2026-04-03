<?php

namespace App\Mail;

use App\Models\CompanyProfile;
use App\Models\PurchaseRequest;
use App\Models\PurchaseSupplierOrder;
use App\Models\PurchaseSupplierOrderReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseSupplierOrderReturnMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public PurchaseRequest $purchaseRequest,
        public PurchaseSupplierOrder $order,
        public PurchaseSupplierOrderReturn $purchaseReturn,
        public string $pdfContent,
        public string $pdfFileName,
        public string $fromAddress,
        public string $fromName,
        public string $subjectLine,
        public string $recipientName = '',
        public string $emailNotes = '',
        public ?CompanyProfile $companyProfile = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName),
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchases.return-send',
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

