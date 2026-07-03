<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly string $title,
        private readonly string $message,
        private readonly string $entityType,
        private readonly int $entityId,
        private readonly ?int $projectId,
        private readonly ?string $projectName,
        private readonly string $url,
        private readonly string $severity,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'project_id' => $this->projectId,
            'project_name' => $this->projectName,
            'url' => $this->url,
            'severity' => $this->severity,
        ];
    }
}