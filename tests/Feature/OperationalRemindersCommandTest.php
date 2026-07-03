<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectPhase as PhaseModel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OperationalRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_reminders_command_creates_deduplicated_notifications(): void
    {
        $user = User::factory()->create([
            'email' => 'ops.reminder@santier.local',
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Reminders',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Reminders',
            'status' => 'active',
        ]);

        $phase = PhaseModel::create([
            'project_id' => $project->id,
            'name' => 'Etapa intarziata',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 35,
            'end_date' => now()->subDays(2)->toDateString(),
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Task restant',
            'description' => 'Task care a depasit termenul.',
            'status' => 'todo',
            'priority' => 'high',
            'deadline' => now()->subDay()->toDateTimeString(),
        ]);

        Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'reported_by' => $user->id,
            'assigned_to' => $user->id,
            'title' => 'Defect critic',
            'description' => 'Defect critic deschis.',
            'status' => 'open',
            'priority' => 'high',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        Artisan::call('notifications:send-operational-reminders');
        Artisan::call('notifications:send-operational-reminders');

        $notifications = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->whereIn('type', [
                \App\Notifications\OperationalReminderNotification::class,
            ])
            ->get();

        $this->assertCount(3, $notifications);
        $this->assertTrue($notifications->contains(fn (DatabaseNotification $notification) => ($notification->data['event'] ?? null) === 'task_overdue'));
        $this->assertTrue($notifications->contains(fn (DatabaseNotification $notification) => ($notification->data['event'] ?? null) === 'phase_overdue'));
        $this->assertTrue($notifications->contains(fn (DatabaseNotification $notification) => ($notification->data['event'] ?? null) === 'defect_overdue'));
    }
}
