<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_suspend_and_schedule_tenant_deletion(): void
    {
        $admin = $this->createSuperadmin();
        $tenant = $this->createTenant('Firma De Arhivat');

        $this->actingAs($admin)
            ->post(route('admin.tenants.suspend', $tenant), ['reason' => 'Client plecat'])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'suspended',
            'lifecycle_status' => 'suspended',
            'lifecycle_reason' => 'Client plecat',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tenants.schedule-deletion', $tenant), ['reason' => 'Stergere solicitata'])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame('pending_deletion', $tenant->lifecycle_status);
        $this->assertNotNull($tenant->deletion_requested_at);
        $this->assertNotNull($tenant->deletion_scheduled_for);
        $this->assertTrue($tenant->deletion_scheduled_for->isFuture());
    }

    public function test_anonymization_removes_personal_data_but_preserves_tenant_record(): void
    {
        $admin = $this->createSuperadmin();
        $tenant = $this->createTenant('Firma Cu Date');
        $user = User::factory()->create([
            'name' => 'Persoana Reala',
            'email' => 'real@example.com',
            'phone' => '0712345678',
            'tenant_id' => $tenant->id,
            'current_tenant_id' => $tenant->id,
            'is_superadmin' => false,
        ]);
        TenantUser::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tenants.anonymize', $tenant))
            ->assertRedirect();

        $tenant->refresh();
        $user->refresh();
        $this->assertSame('anonymized', $tenant->lifecycle_status);
        $this->assertNotNull($tenant->anonymized_at);
        $this->assertSame('Firma arhivata #' . $tenant->id, $tenant->name);
        $this->assertSame('Utilizator eliminat #' . $user->id, $user->name);
        $this->assertSame('deleted-user-' . $user->id . '@invalid.local', $user->email);
        $this->assertNull($user->phone);
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'platform.tenant.anonymized',
            'resource_id' => $tenant->id,
        ]);
    }

    public function test_tenant_user_cannot_run_lifecycle_actions(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
        $tenant = $this->createTenant('Firma Protejata');

        $this->actingAs($user)
            ->post(route('admin.tenants.anonymize', $tenant))
            ->assertForbidden();
    }

    private function createTenant(string $name): Tenant
    {
        return Tenant::query()->create([
            'name' => $name,
            'slug' => str()->slug($name),
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);
    }

    private function createSuperadmin(): User
    {
        return User::factory()->create([
            'email' => 'lifecycle-admin@example.com',
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
