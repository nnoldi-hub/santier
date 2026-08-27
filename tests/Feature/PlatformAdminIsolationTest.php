<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PlatformAdminIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_access_platform_routes(): void
    {
        $superadmin = $this->createSuperadmin('platform@example.com');

        $this->actingAs($superadmin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Index')
                ->where('metrics.tenants_total', 1)
                ->where('metrics.tenants_active', 1)
                ->where('metrics.tenants_trial', 0)
                ->where('metrics.trial_expiring_soon', 0)
            );
    }

    public function test_tenant_user_cannot_access_platform_routes(): void
    {
        $user = $this->createTenantUser('tenant@example.com');

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertForbidden();
    }

    public function test_superadmin_cannot_access_tenant_routes(): void
    {
        $superadmin = $this->createSuperadmin('platform@example.com');

        $this->actingAs($superadmin)
            ->get(route('projects.index'))
            ->assertForbidden();
    }

    public function test_superadmin_dashboard_redirects_to_global_dashboard(): void
    {
        $superadmin = $this->createSuperadmin('platform@example.com');

        $this->actingAs($superadmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.index'));
    }

    public function test_configured_platform_admin_email_has_same_platform_access(): void
    {
        config()->set('platform.admin_emails', ['configured@example.com']);
        $admin = $this->createTenantUser('configured@example.com');

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk();
    }

    private function createSuperadmin(string $email): User
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

    private function createTenantUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
