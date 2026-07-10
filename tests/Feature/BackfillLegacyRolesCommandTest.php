<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackfillLegacyRolesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->create(['name' => 'tenant_admin', 'guard_name' => 'web', 'tenant_id' => null]);
        Role::query()->create(['name' => 'data_entry', 'guard_name' => 'web', 'tenant_id' => null]);

        Tenant::create([
            'id' => 2,
            'name' => 'Tenant fara admin',
            'slug' => 'tenant-fara-admin',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);
    }

    public function test_dry_run_reports_without_assigning_roles(): void
    {
        $user = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);

        Artisan::call('iam:backfill-legacy-roles');

        $this->assertFalse($user->fresh()->hasAnyRole(['tenant_admin', 'data_entry']));
    }

    public function test_apply_assigns_tenant_admin_to_earliest_user_without_existing_admin(): void
    {
        $first = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2, 'created_at' => now()->subDay()]);
        $second = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2, 'created_at' => now()]);

        Artisan::call('iam:backfill-legacy-roles', ['--apply' => true]);

        $this->assertTrue($first->fresh()->hasRole('tenant_admin'));
        $this->assertTrue($second->fresh()->hasRole('data_entry'));
    }

    public function test_apply_assigns_only_data_entry_when_tenant_already_has_an_admin(): void
    {
        $admin = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);
        $admin->syncRoles([Role::where('name', 'tenant_admin')->firstOrFail()]);

        $legacyUser = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);

        Artisan::call('iam:backfill-legacy-roles', ['--apply' => true]);

        $this->assertTrue($legacyUser->fresh()->hasRole('data_entry'));
        $this->assertFalse($legacyUser->fresh()->hasRole('tenant_admin'));
    }
}
