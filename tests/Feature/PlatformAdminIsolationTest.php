<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Tenant;
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

    public function test_global_dashboard_exposes_actionable_platform_alerts(): void
    {
        $admin = $this->createSuperadmin('alerts@example.com');
        $trialTenant = Tenant::query()->create([
            'name' => 'Firma Trial Urgent',
            'slug' => 'firma-trial-urgent',
            'billing_plan' => 'free',
            'billing_trial_ends_at' => now()->addDays(2),
            'status' => 'active',
            'module_flags' => [],
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
        Tenant::query()->create([
            'name' => 'Firma Suspendata Alert',
            'slug' => 'firma-suspendata-alert',
            'billing_plan' => 'free',
            'status' => 'suspended',
            'module_flags' => [],
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
        Project::query()->create([
            'tenant_id' => $trialTenant->id,
            'created_by' => $admin->id,
            'name' => 'Proiect lead cald',
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('platformAlerts', fn ($alerts) => collect($alerts)->pluck('type')->contains('trial_expiring'))
                ->where('platformAlerts', fn ($alerts) => collect($alerts)->pluck('type')->contains('suspended'))
                ->where('platformAlerts', fn ($alerts) => collect($alerts)->pluck('type')->contains('warm_lead'))
                ->where('platformAlerts', fn ($alerts) => collect($alerts)->every(fn (array $alert) => isset($alert['action_url'], $alert['action_label'])))
            );
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
