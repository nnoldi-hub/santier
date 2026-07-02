<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectRoleChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notification_center_and_only_own_notifications(): void
    {
        $userA = $this->createUser('center.a@santier.local');
        $userB = $this->createUser('center.b@santier.local');

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $userA->id,
            'name' => 'Centru Notificari',
            'status' => 'active',
        ]);

        $userA->notify(new ProjectRoleChangedNotification(
            project: $project,
            event: 'assigned',
            roleKey: 'viewer',
            actorName: 'Tester',
        ));

        $userB->notify(new ProjectRoleChangedNotification(
            project: $project,
            event: 'updated',
            roleKey: 'contributor',
            actorName: 'Tester',
        ));

        $this->actingAs($userA)
            ->get(route('account.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Notifications')
                ->has('notifications.data', 1)
                ->where('notifications.data.0.data.event', 'assigned')
            );
    }

    public function test_notification_center_filters_unread_and_read(): void
    {
        $user = $this->createUser('center.filter@santier.local');

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Filter Project',
            'status' => 'active',
        ]);

        $user->notify(new ProjectRoleChangedNotification(
            project: $project,
            event: 'assigned',
            roleKey: 'viewer',
            actorName: 'Tester',
        ));

        $notification = $user->notifications()->latest()->firstOrFail();
        $notification->markAsRead();

        $this->actingAs($user)
            ->get(route('account.notifications.index', ['status' => 'read']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Notifications')
                ->has('notifications.data', 1)
                ->where('notifications.data.0.read_at', fn ($value) => !empty($value))
            );
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
