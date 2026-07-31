<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProformaRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $companyName,
        public string $pdfBinary,
        public string $fileName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Factura proforma Modulia - ' . $this->companyName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proforma-sent',
            with: [
                'recipientName' => $this->recipientName,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBinary, $this->fileName)
                ->withMime('application/pdf'),
        ];
    }
}
