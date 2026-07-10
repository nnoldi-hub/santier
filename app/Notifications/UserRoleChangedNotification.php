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
        return (new MailMessage)
            ->subject('Rolul tau in Modulia a fost actualizat')
            ->line('Rolul tau in firma "' . $this->tenantName . '" a fost actualizat de ' . $this->actorName . '.')
            ->line('Noul tau rol: ' . $this->roleLabel . '.')
            ->action('Deschide Modulia', url(route('dashboard', [], false)));
    }
}
