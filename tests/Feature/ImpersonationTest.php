<?php

namespace Tests\Feature;

use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_impersonate_active_tenant_user_and_return(): void
    {
        $admin = $this->createSuperadmin();
        $target = $this->createTenantUser();

        $startResponse = $this->actingAs($admin)
            ->post(route('admin.impersonation.start', $target));

        $startResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($target);
        $this->assertSame($admin->id, session('impersonation.admin_id'));
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'platform.impersonation.started',
            'actor_user_id' => $admin->id,
            'resource_id' => $target->id,
        ]);

        $stopResponse = $this->post(route('impersonation.stop'));

        $stopResponse->assertRedirect(route('admin.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonation.admin_id'));
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'platform.impersonation.stopped',
            'actor_user_id' => $admin->id,
            'resource_id' => $target->id,
        ]);
    }

    public function test_tenant_user_cannot_start_impersonation(): void
    {
        $actor = $this->createTenantUser('actor@example.com');
        $target = $this->createTenantUser('target@example.com');

        $this->actingAs($actor)
            ->post(route('admin.impersonation.start', $target))
            ->assertForbidden();
    }

    public function test_superadmin_cannot_impersonate_platform_admin(): void
    {
        $admin = $this->createSuperadmin('platform@example.com');
        $targetAdmin = $this->createSuperadmin('other-platform@example.com');

        $this->actingAs($admin)
            ->post(route('admin.impersonation.start', $targetAdmin))
            ->assertStatus(422);
    }

    private function createSuperadmin(string $email = 'platform@example.com'): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function createTenantUser(string $email = 'tenant@example.com'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        TenantUser::query()->create([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $user;
    }
}
