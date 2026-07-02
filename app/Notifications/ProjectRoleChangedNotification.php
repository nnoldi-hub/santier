<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectRoleChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly string $event,
        private readonly ?string $roleKey,
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
            'event' => $this->event,
            'project_id' => (int) $this->project->id,
            'project_name' => (string) $this->project->name,
            'role_key' => $this->roleKey,
            'actor_name' => $this->actorName,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $eventLabel = match ($this->event) {
            'assigned' => 'acordat',
            'updated' => 'actualizat',
            'revoked' => 'revocat',
            default => 'modificat',
        };

        $roleLabel = $this->roleKey !== null ? strtoupper($this->roleKey) : 'N/A';

        return (new MailMessage)
            ->subject('Rol pe proiect ' . $eventLabel)
            ->line('Rolul tau pe proiect a fost ' . $eventLabel . '.')
            ->line('Proiect: ' . $this->project->name)
            ->line('Rol: ' . $roleLabel)
            ->line('Operat de: ' . $this->actorName)
            ->action('Deschide proiectul', route('projects.show', $this->project->id));
    }
}
