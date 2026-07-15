<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

class UserInvitedNotification extends Notification
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
        $token = Password::createToken($notifiable);
        $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->email], false));

        $mail = (new MailMessage)
            ->subject($this->whiteLabel
                ? 'Ai fost invitat in firma "' . $this->tenantName . '"'
                : 'Ai fost invitat in Modulia - Șantierul devine clar')
            ->line($this->actorName . ' te-a adaugat in firma "' . $this->tenantName . '"' . ($this->whiteLabel ? '.' : ' pe Modulia.'))
            ->line('Rolul tau: ' . $this->roleLabel . '.')
            ->line('Apasa butonul de mai jos ca sa-ti setezi parola si sa-ti activezi contul.')
            ->action('Seteaza-ti parola', $url)
            ->line('Linkul expira in ' . config('auth.passwords.users.expire', 60) . ' de minute.');

        if (!$this->whiteLabel) {
            $mail->line('Modulia - Șantierul devine clar.');
        }

        return $mail;
    }
}
