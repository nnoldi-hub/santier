<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectRoleChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = $this->createUser('notify.single@santier.local');
        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Notificare proiect',
            'status' => 'active',
        ]);

        $user->notify(new ProjectRoleChangedNotification(
            project: $project,
            event: 'assigned',
            roleKey: 'viewer',
            actorName: 'Tester',
        ));

        $notification = $user->notifications()->latest()->firstOrFail();

        $this->actingAs($user)
            ->patch(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
            'read_at' => null,
        ]);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = $this->createUser('notify.all@santier.local');
        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Notificare bulk',
            'status' => 'active',
        ]);

        $user->notify(new ProjectRoleChangedNotification(
            project: $project,
            event: 'assigned',
            roleKey: 'viewer',
            actorName: 'Tester',
        ));

        $user->notify(new ProjectRoleChangedNotification(
            project: $project,
            event: 'updated',
            roleKey: 'contributor',
            actorName: 'Tester',
        ));

        $this->actingAs($user)
            ->patch(route('notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    private function createUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
