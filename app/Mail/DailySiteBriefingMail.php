<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySiteBriefingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public array $briefing,
        public string $recipientName = '',
        public bool $whiteLabel = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->whiteLabel ? '' : 'Modulia - Șantierul devine clar - ') . 'Memento zilnic - ' . $this->project->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-briefing',
            with: [
                'project' => $this->project,
                'briefing' => $this->briefing,
                'recipientName' => $this->recipientName,
                'whiteLabel' => $this->whiteLabel,
            ],
        );
    }
}
