<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BrochureRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $pdfBinary,
        public string $fileName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Brosura Modulia - Santierul devine clar',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.brochure-sent',
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
