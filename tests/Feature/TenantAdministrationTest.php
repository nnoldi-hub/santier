<?php

namespace Tests\Feature;

use App\Models\TenantUser;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use App\Notifications\UserRoleChangedNotification;
use App\Notifications\UserStatusChangedNotification;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_invite_user_and_assign_role(): void
    {
        Notification::fake();

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

        Notification::assertSentTo($member, UserInvitedNotification::class);

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

    public function test_invite_notification_renders_a_working_password_set_link(): void
    {
        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin.mail@santier.local');
        $role = Role::query()->where('name', 'data_entry')->firstOrFail();

        $this->actingAs($superadmin)
            ->post(route('account.users.invite'), [
                'name' => 'Utilizator Mail',
                'email' => 'mail.nou@firma.local',
                'department' => 'Santier',
                'role_id' => $role->id,
            ])
            ->assertRedirect();

        $member = User::query()->where('email', 'mail.nou@firma.local')->firstOrFail();
        $mail = (new UserInvitedNotification('Tenant implicit', 'Operator introducere date', $superadmin->name))->toMail($member);

        $this->assertStringContainsString('reset-password', $mail->actionUrl);
        $this->assertStringContainsString('email=' . urlencode($member->email), $mail->actionUrl);
    }

    public function test_superadmin_can_resend_invite(): void
    {
        Notification::fake();

        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin.resend@santier.local');
        $role = Role::query()->where('name', 'data_entry')->firstOrFail();
        $member = $this->createTenantMember('reinvite.me@firma.local', 'Reinvite Me', 'Santier', 'active', $role);
        $membership = TenantUser::query()->where('user_id', $member->id)->firstOrFail();

        $this->actingAs($superadmin)
            ->post(route('account.users.resend', $membership))
            ->assertRedirect();

        Notification::assertSentTo($member, UserInvitedNotification::class);
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.user.reinvited',
            'resource_type' => 'user',
            'resource_id' => $member->id,
        ]);
    }

    public function test_superadmin_can_remove_member_from_tenant(): void
    {
        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin.remove@santier.local');
        $role = Role::query()->where('name', 'data_entry')->firstOrFail();
        $member = $this->createTenantMember('remove.me@firma.local', 'Remove Me', 'Santier', 'active', $role);
        $membership = TenantUser::query()->where('user_id', $member->id)->firstOrFail();

        $this->actingAs($superadmin)
            ->delete(route('account.users.destroy', $membership))
            ->assertRedirect();

        $this->assertDatabaseMissing('tenant_users', ['id' => $membership->id]);
        $this->assertDatabaseHas('users', ['id' => $member->id]);
        $this->assertFalse($member->fresh()->hasRole('data_entry'));
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.user.removed',
            'resource_type' => 'user',
            'resource_id' => $member->id,
        ]);
    }

    public function test_actor_cannot_remove_themselves_from_tenant(): void
    {
        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin.self@santier.local');
        $membership = TenantUser::query()->create([
            'tenant_id' => 1,
            'user_id' => $superadmin->id,
            'status' => 'active',
            'invited_by' => $superadmin->id,
            'joined_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->delete(route('account.users.destroy', $membership))
            ->assertStatus(422);

        $this->assertDatabaseHas('tenant_users', ['id' => $membership->id]);
    }

    public function test_superadmin_can_suspend_reactivate_and_reassign_tenant_user(): void
    {
        Notification::fake();

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

        Notification::assertSentTo($member, UserStatusChangedNotification::class, function ($notification) use ($member) {
            return $notification->toArray($member)['status'] === 'suspended';
        });
        Notification::assertSentTo($member, UserStatusChangedNotification::class, function ($notification) use ($member) {
            return $notification->toArray($member)['status'] === 'active';
        });
        Notification::assertSentTo($member, UserRoleChangedNotification::class);
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

    public function test_user_list_can_be_filtered_by_status_role_and_search(): void
    {
        $this->seed(IamSeeder::class);

        $superadmin = $this->createSuperadmin('superadmin4@santier.local');
        $dataEntryRole = Role::query()->where('name', 'data_entry')->firstOrFail();
        $financeRole = Role::query()->where('name', 'finance')->firstOrFail();

        $activeMember = $this->createTenantMember('ana.popescu@santier.local', 'Ana Popescu', 'Productie', 'active', $dataEntryRole);
        $this->createTenantMember('ion.ionescu@santier.local', 'Ion Ionescu', 'Financiar', 'suspended', $financeRole);

        $this->actingAs($superadmin)
            ->get(route('account.users.index', [
                'status' => 'active',
                'role_id' => $dataEntryRole->id,
                'search' => 'Ana',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Account/Users')
                ->has('members', 1)
                ->where('members.0.email', $activeMember->email)
                ->where('filters', function ($filters) use ($dataEntryRole): bool {
                    return $filters['status'] === 'active'
                        && (int) $filters['role_id'] === (int) $dataEntryRole->id
                        && $filters['search'] === 'Ana';
                })
            );
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

    private function createTenantMember(string $email, string $name, string $department, string $status, Role $role): User
    {
        $member = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $member->syncRoles([$role]);

        TenantUser::query()->create([
            'tenant_id' => 1,
            'user_id' => $member->id,
            'department' => $department,
            'status' => $status,
            'invited_by' => $member->id,
            'joined_at' => now(),
        ]);

        return $member;
    }
}
