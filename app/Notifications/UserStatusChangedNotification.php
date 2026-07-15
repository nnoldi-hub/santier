<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
        private readonly string $status,
        private readonly string $actorName,
        public readonly bool $whiteLabel = false,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_name' => $this->tenantName,
            'status' => $this->status,
            'actor_name' => $this->actorName,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isActive = $this->status === 'active';

        $mail = (new MailMessage)
            ->subject($isActive
                ? 'Contul tau' . ($this->whiteLabel ? '' : ' din Modulia') . ' a fost reactivat'
                : 'Contul tau' . ($this->whiteLabel ? '' : ' din Modulia') . ' a fost suspendat')
            ->line(($isActive ? 'Contul tau in firma "' : 'Contul tau in firma "') . $this->tenantName . '" a fost '
                . ($isActive ? 'reactivat' : 'suspendat') . ' de ' . $this->actorName . '.');

        if ($isActive) {
            $mail->action($this->whiteLabel ? 'Deschide aplicatia' : 'Deschide Modulia', url(route('dashboard', [], false)));
        } else {
            $mail->line('Nu mai poti accesa aplicatia pana cand contul tau este reactivat de un administrator al firmei.');
        }

        if (!$this->whiteLabel) {
            $mail->line('Modulia - Șantierul devine clar.');
        }

        return $mail;
    }
}
