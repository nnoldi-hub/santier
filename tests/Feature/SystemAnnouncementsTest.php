<?php

namespace Tests\Feature;

use App\Models\SystemAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_publish_and_deactivate_announcement(): void
    {
        $admin = $this->createSuperadmin();

        $this->actingAs($admin)
            ->post(route('admin.announcements.store'), [
                'title' => 'Actualizare programata',
                'message' => 'Aplicatia poate fi indisponibila cinci minute.',
                'level' => 'warning',
                'starts_at' => now()->toDateTimeString(),
                'ends_at' => now()->addHour()->toDateTimeString(),
                'is_active' => true,
            ])
            ->assertRedirect();

        $announcement = SystemAnnouncement::query()->firstOrFail();
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'platform.announcement.created',
            'actor_user_id' => $admin->id,
            'resource_id' => $announcement->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.announcements.destroy', $announcement))
            ->assertRedirect();

        $this->assertFalse($announcement->fresh()->is_active);
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'platform.announcement.deactivated',
            'actor_user_id' => $admin->id,
            'resource_id' => $announcement->id,
        ]);
    }

    public function test_tenant_user_cannot_publish_announcement(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('admin.announcements.store'), [
                'title' => 'Nu ar trebui',
                'message' => 'Mesaj',
                'level' => 'info',
            ])
            ->assertForbidden();
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'email' => 'announcements-admin@example.com',
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
