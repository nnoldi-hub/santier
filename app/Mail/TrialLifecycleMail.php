<?php

namespace App\Mail;

use App\Models\User;
use App\Support\PricingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialLifecycleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $campaignKey,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->campaignKey) {
            'welcome' => 'Invitatie Modulia - Șantierul devine clar',
            'trial_day_3' => 'Modulia - 3 zile de trial: optimizeaza primul proiect',
            'trial_day_10' => 'Modulia - 10 zile de trial: pregateste decizia de upgrade',
            'upgrade_prompt' => 'Modulia - Trial-ul expira curand, continua fara intreruperi',
            default => 'Modulia - Notificare trial',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.trial-lifecycle',
            with: [
                'user' => $this->user,
                'campaignKey' => $this->campaignKey,
                'trialEndsAt' => PricingPlan::trialEndsAt($this->user),
            ],
        );
    }
}
