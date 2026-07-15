<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRoleChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $tenantName,
        private readonly string $roleLabel,
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
            'role_label' => $this->roleLabel,
            'actor_name' => $this->actorName,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->whiteLabel ? 'Rolul tau a fost actualizat' : 'Rolul tau in Modulia a fost actualizat')
            ->line('Rolul tau in firma "' . $this->tenantName . '" a fost actualizat de ' . $this->actorName . '.')
            ->line('Noul tau rol: ' . $this->roleLabel . '.')
            ->action($this->whiteLabel ? 'Deschide aplicatia' : 'Deschide Modulia', url(route('dashboard', [], false)));

        if (!$this->whiteLabel) {
            $mail->line('Modulia - Șantierul devine clar.');
        }

        return $mail;
    }
}
