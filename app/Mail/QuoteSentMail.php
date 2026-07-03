<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quote $quote,
        public string $pdfBinary,
        public string $fileName,
        public string $recipientName = '',
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Modulia - Șantierul devine clar - Oferta ' . $this->quote->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-sent',
            with: [
                'quote' => $this->quote,
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
