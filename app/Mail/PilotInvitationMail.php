<?php

namespace App\Mail;

use App\Models\PilotInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PilotInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PilotInvite $invite,
        public string $senderName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Modulia - Invitatie pilot pentru ' . $this->invite->company_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pilot-invitation',
            with: [
                'invite' => $this->invite,
                'senderName' => $this->senderName,
                'demoUrl' => url('/') . '#solicita-demo',
            ],
        );
    }
}
