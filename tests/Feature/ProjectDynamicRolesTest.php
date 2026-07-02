<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\ProjectUserRole;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProjectDynamicRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_dynamic_assignments_sees_only_assigned_projects_in_index(): void
    {
        $creator = $this->createInternalUser('creator@santier.local');

        $hiddenProject = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Hidden Project',
            'status' => 'active',
        ]);

        $assignedViewer = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Assigned Viewer',
            'status' => 'active',
        ]);

        $assignedContributor = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Assigned Contributor',
            'status' => 'active',
        ]);

        $user = $this->createInternalUser('member@santier.local');
        $this->assignProjectPermissions($user, ['projects.view', 'projects.edit']);

        $viewerRole = $this->projectRole(ProjectRole::VIEWER);
        $contributorRole = $this->projectRole(ProjectRole::CONTRIBUTOR);

        ProjectUserRole::create([
            'tenant_id' => 1,
            'project_id' => $assignedViewer->id,
            'user_id' => $user->id,
            'project_role_id' => $viewerRole->id,
        ]);

        ProjectUserRole::create([
            'tenant_id' => 1,
            'project_id' => $assignedContributor->id,
            'user_id' => $user->id,
            'project_role_id' => $contributorRole->id,
        ]);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 2)
            ->where('projects.data', function ($projects) use ($assignedContributor, $assignedViewer): bool {
                $actual = collect($projects)->pluck('id')->sort()->values()->all();
                $expected = collect([$assignedContributor->id, $assignedViewer->id])->sort()->values()->all();

                return $actual === $expected;
            })
        );

        $this->assertNotEquals($hiddenProject->id, $assignedContributor->id);
    }

    public function test_contributor_cannot_delete_project(): void
    {
        $creator = $this->createInternalUser('creator2@santier.local');

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Project Protected',
            'status' => 'active',
        ]);

        $user = $this->createInternalUser('contrib@santier.local');
        $this->assignProjectPermissions($user, ['projects.view', 'projects.edit', 'projects.delete']);

        ProjectUserRole::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_role_id' => $this->projectRole(ProjectRole::CONTRIBUTOR)->id,
        ]);

        $this->actingAs($user)
            ->delete(route('projects.destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    public function test_owner_can_delete_project(): void
    {
        $creator = $this->createInternalUser('creator3@santier.local');

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Project Deletable',
            'status' => 'active',
        ]);

        $user = $this->createInternalUser('owner@santier.local');
        $this->assignProjectPermissions($user, ['projects.view', 'projects.delete']);

        ProjectUserRole::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'project_role_id' => $this->projectRole(ProjectRole::OWNER)->id,
        ]);

        $this->actingAs($user)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_user_without_dynamic_assignments_keeps_default_tenant_visibility(): void
    {
        $creator = $this->createInternalUser('creator4@santier.local');

        Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Tenant Project 1',
            'status' => 'active',
        ]);

        Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Tenant Project 2',
            'status' => 'active',
        ]);

        $user = $this->createInternalUser('plain@santier.local');
        $this->assignProjectPermissions($user, ['projects.view']);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 2)
        );
    }

    public function test_owner_can_assign_and_update_and_revoke_project_roles_with_audit(): void
    {
        $creator = $this->createInternalUser('creator5@santier.local');

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Project Role Management',
            'status' => 'active',
        ]);

        $owner = $this->createInternalUser('owner.manage@santier.local');
        $member = $this->createInternalUser('member.manage@santier.local');

        $this->assignProjectPermissions($owner, ['projects.view', 'projects.edit']);

        ProjectUserRole::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'project_role_id' => $this->projectRole(ProjectRole::OWNER)->id,
        ]);

        $this->actingAs($owner)
            ->post(route('projects.roles.store', $project), [
                'user_id' => $member->id,
                'role_key' => ProjectRole::VIEWER,
            ])
            ->assertRedirect();

        $assignment = ProjectUserRole::query()
            ->where('project_id', $project->id)
            ->where('user_id', $member->id)
            ->firstOrFail();

        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.project_role.assigned',
            'resource_type' => 'project_user_role',
            'resource_id' => $assignment->id,
        ]);

        $this->actingAs($owner)
            ->patch(route('projects.roles.update', [$project, $assignment]), [
                'role_key' => ProjectRole::CONTRIBUTOR,
            ])
            ->assertRedirect();

        $assignment->refresh();
        $this->assertSame(ProjectRole::CONTRIBUTOR, $assignment->projectRole->key);

        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.project_role.updated',
            'resource_type' => 'project_user_role',
            'resource_id' => $assignment->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('projects.roles.destroy', [$project, $assignment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_user_roles', ['id' => $assignment->id]);
        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.project_role.revoked',
            'resource_type' => 'project_user_role',
            'resource_id' => $assignment->id,
        ]);
    }

    public function test_contributor_cannot_manage_project_roles(): void
    {
        $creator = $this->createInternalUser('creator6@santier.local');

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Project Role Protection',
            'status' => 'active',
        ]);

        $contributor = $this->createInternalUser('contributor.manage@santier.local');
        $target = $this->createInternalUser('target.manage@santier.local');

        $this->assignProjectPermissions($contributor, ['projects.view', 'projects.edit']);

        ProjectUserRole::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'user_id' => $contributor->id,
            'project_role_id' => $this->projectRole(ProjectRole::CONTRIBUTOR)->id,
        ]);

        $this->actingAs($contributor)
            ->post(route('projects.roles.store', $project), [
                'user_id' => $target->id,
                'role_key' => ProjectRole::VIEWER,
            ])
            ->assertForbidden();
    }

    public function test_owner_can_bulk_assign_project_role_with_audit_and_notifications(): void
    {
        $creator = $this->createInternalUser('creator7@santier.local');

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Project Bulk Roles',
            'status' => 'active',
        ]);

        $owner = $this->createInternalUser('owner.bulk@santier.local');
        $memberA = $this->createInternalUser('member.a.bulk@santier.local');
        $memberB = $this->createInternalUser('member.b.bulk@santier.local');

        $this->assignProjectPermissions($owner, ['projects.view', 'projects.edit']);

        ProjectUserRole::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'user_id' => $owner->id,
            'project_role_id' => $this->projectRole(ProjectRole::OWNER)->id,
        ]);

        $this->actingAs($owner)
            ->post(route('projects.roles.bulk.store', $project), [
                'user_ids' => [$memberA->id, $memberB->id],
                'role_key' => ProjectRole::VIEWER,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_user_roles', [
            'project_id' => $project->id,
            'user_id' => $memberA->id,
        ]);

        $this->assertDatabaseHas('project_user_roles', [
            'project_id' => $project->id,
            'user_id' => $memberB->id,
        ]);

        $this->assertDatabaseHas('access_audit_logs', [
            'action' => 'iam.project_role.assigned_bulk',
            'resource_type' => 'project_user_role',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $memberA->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $memberB->id,
        ]);
    }

    private function createInternalUser(string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        TenantUser::query()->firstOrCreate([
            'tenant_id' => 1,
            'user_id' => $user->id,
        ], [
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return $user;
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    private function assignProjectPermissions(User $user, array $permissionNames): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($permissionNames)->map(function (string $name) {
            return Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        });

        $role = Role::query()->firstOrCreate([
            'name' => 'project_dynamic_test_' . $user->id,
            'guard_name' => 'web',
            'tenant_id' => null,
        ]);

        $role->syncPermissions($permissions->pluck('name')->all());
        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function projectRole(string $key): ProjectRole
    {
        return ProjectRole::query()->firstOrCreate(
            ['tenant_id' => null, 'key' => $key],
            ['name' => ucfirst($key), 'description' => ucfirst($key)]
        );
    }
}
