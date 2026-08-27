<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantSubscriptionsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_filter_tenants_by_commercial_status(): void
    {
        $admin = $this->createSuperadmin();
        $trialTenant = $this->createTenant('Firma Trial', [
            'billing_plan' => 'free',
            'billing_trial_ends_at' => now()->addDays(5),
        ]);
        $this->createTenant('Firma Platitoare', ['billing_plan' => 'pro']);
        $this->createTenant('Firma Suspendata', ['status' => 'suspended']);

        $this->actingAs($admin)
            ->get(route('admin.tenants.index', ['commercial_status' => 'Trial activ']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TenantsIndex')
                ->where('filters.commercial_status', fn ($value) => $value === 'Trial activ')
                ->where('tenants.total', 1)
                ->where('tenants.data.0.name', $trialTenant->name)
                ->where('tenants.data.0.commercial_status', fn ($value) => $value === 'Trial activ')
            );
    }

    public function test_unknown_commercial_status_does_not_filter_tenants(): void
    {
        $admin = $this->createSuperadmin();
        $tenant = $this->createTenant('Firma Valida');

        $this->actingAs($admin)
            ->get(route('admin.tenants.index', ['commercial_status' => 'invalid']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TenantsIndex')
                ->where('filters.commercial_status', fn ($value) => $value === 'invalid')
                ->where('tenants.total', 2)
                ->where('tenants.data', fn ($data) => collect($data)->pluck('name')->contains($tenant->name))
            );
    }

    private function createTenant(string $name, array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => $name,
            'slug' => str()->slug($name),
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ], $attributes));
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'email' => 'platform@example.com',
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
