<?php

namespace Tests\Feature;

use App\Models\TenantUser;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_invite_user_and_assign_role(): void
    {
        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin@santier.local');
        $role = Role::query()->where('name', 'data_entry')->firstOrFail();

        $this->actingAs($superadmin)
            ->post(route('account.users.invite'), [
                'name' => 'Utilizator Nou',
                'email' => 'nou@firma.local',
                'department' => 'Santier',
                'role_id' => $role->id,
            ])
            ->assertRedirect();

        $member = User::query()->where('email', 'nou@firma.local')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'name' => 'Utilizator Nou',
        ]);

        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => 1,
            'user_id' => $member->id,
            'department' => 'Santier',
            'status' => 'active',
            'invited_by' => $superadmin->id,
        ]);

        $this->assertTrue($member->hasRole('data_entry'));
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.user.invited',
            'resource_type' => 'user',
            'resource_id' => $member->id,
        ]);
    }

    public function test_superadmin_can_suspend_reactivate_and_reassign_tenant_user(): void
    {
        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin2@santier.local');
        $initialRole = Role::query()->where('name', 'data_entry')->firstOrFail();
        $nextRole = Role::query()->where('name', 'finance')->firstOrFail();

        $member = User::query()->create([
            'name' => 'Membru Firma',
            'email' => 'membru@firma.local',
            'password' => bcrypt('password'),
            'tenant_id' => 1,
            'current_tenant_id' => 1,
        ]);
        $member->syncRoles([$initialRole]);

        $membership = TenantUser::query()->create([
            'tenant_id' => 1,
            'user_id' => $member->id,
            'department' => 'Productie',
            'status' => 'active',
            'invited_by' => $superadmin->id,
            'joined_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->patch(route('account.users.status.update', $membership), [
                'status' => 'suspended',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenant_users', [
            'id' => $membership->id,
            'status' => 'suspended',
        ]);

        $this->actingAs($superadmin)
            ->patch(route('account.users.status.update', $membership), [
                'status' => 'active',
            ])
            ->assertRedirect();

        $this->actingAs($superadmin)
            ->patch(route('account.users.role.update', $membership), [
                'role_id' => $nextRole->id,
            ])
            ->assertRedirect();

        $this->assertTrue($member->fresh()->hasRole('finance'));
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.user.status_changed',
            'resource_type' => 'user',
            'resource_id' => $member->id,
        ]);
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.user.role_changed',
            'resource_type' => 'user',
            'resource_id' => $member->id,
        ]);
    }

    public function test_superadmin_can_manage_custom_tenant_roles(): void
    {
        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin3@santier.local');

        $this->actingAs($superadmin)
            ->post(route('account.roles.store'), [
                'name' => 'auditor_local',
                'permissions' => ['projects.view', 'documents.view', 'reports.view'],
            ])
            ->assertRedirect();

        $role = Role::query()->where('name', 'auditor_local')->firstOrFail();

        $this->assertSame(1, (int) $role->tenant_id);
        $this->assertEqualsCanonicalizing(
            ['projects.view', 'documents.view', 'reports.view'],
            $role->permissions()->pluck('name')->all()
        );

        $this->actingAs($superadmin)
            ->patch(route('account.roles.update', $role), [
                'name' => 'auditor_extins',
                'permissions' => ['projects.view', 'documents.view', 'reports.view', 'contractors.view'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'auditor_extins',
        ]);
        $this->assertEqualsCanonicalizing(
            ['projects.view', 'documents.view', 'reports.view', 'contractors.view'],
            $role->refresh()->permissions()->pluck('name')->all()
        );

        $this->actingAs($superadmin)
            ->delete(route('account.roles.destroy', $role))
            ->assertRedirect();

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    private function createSuperadmin(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }
}
