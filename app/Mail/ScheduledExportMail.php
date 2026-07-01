<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledExportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subscriptionName,
        public string $exportType,
        public string $format,
        public string $filePath,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Export automat: ' . $this->subscriptionName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scheduled-export',
            with: [
                'subscriptionName' => $this->subscriptionName,
                'exportType' => $this->exportType,
                'format' => strtoupper($this->format),
                'generatedAt' => now()->format('Y-m-d H:i:s'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->filePath)->as(basename($this->filePath)),
        ];
    }
}
