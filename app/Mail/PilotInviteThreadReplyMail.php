<?php

namespace App\Mail;

use App\Models\PilotInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class PilotInviteThreadReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PilotInvite $invite,
        public string $body,
        public string $senderName,
        public string $fromEmail,
        public string $messageId,
        public ?string $inReplyToMessageId = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->fromEmail, 'Modulia'),
            subject: 'Re: Modulia - ' . $this->invite->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pilot-invite-thread-reply',
            with: [
                'body' => $this->body,
                'senderName' => $this->senderName,
            ],
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->messageId,
            references: $this->inReplyToMessageId ? [$this->inReplyToMessageId] : [],
            text: $this->inReplyToMessageId ? ['In-Reply-To' => '<' . $this->inReplyToMessageId . '>'] : [],
        );
    }
}
