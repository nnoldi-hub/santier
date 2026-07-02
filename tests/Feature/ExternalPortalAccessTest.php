<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ExternalPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_portal_user_sees_only_owned_projects(): void
    {
        $creator = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $clientOwned = Client::create([
            'tenant_id' => 1,
            'name' => 'Client extern',
            'email' => 'client.portal@example.com',
            'active' => true,
        ]);

        $clientOther = Client::create([
            'tenant_id' => 1,
            'name' => 'Client alt',
            'email' => 'other.client@example.com',
            'active' => true,
        ]);

        $ownedProject = Project::create([
            'tenant_id' => 1,
            'client_id' => $clientOwned->id,
            'created_by' => $creator->id,
            'name' => 'Proiect Client Portal',
            'status' => 'active',
        ]);

        Project::create([
            'tenant_id' => 1,
            'client_id' => $clientOther->id,
            'created_by' => $creator->id,
            'name' => 'Proiect Privat Alt Client',
            'status' => 'active',
        ]);

        $user = $this->createExternalUser('client.portal@example.com');
        $this->assignRoleWithPermissions($user, 'client_portal', ['projects.view', 'documents.view', 'reports.view']);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $ownedProject->id)
        );
    }

    public function test_subcontractor_portal_user_sees_only_assigned_projects(): void
    {
        $creator = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $subcontractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Subcontractor extern',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'email' => 'sub.portal@example.com',
            'active' => true,
        ]);

        $otherSubcontractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Subcontractor alt',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'email' => 'other.sub@example.com',
            'active' => true,
        ]);

        $projectAssigned = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Proiect Alocat Subcontractor',
            'status' => 'active',
        ]);

        $projectHidden = Project::create([
            'tenant_id' => 1,
            'created_by' => $creator->id,
            'name' => 'Proiect Fara Acces',
            'status' => 'active',
        ]);

        ProjectPhase::create([
            'project_id' => $projectAssigned->id,
            'name' => 'Etapa subcontractor',
            'contractor_id' => $subcontractor->id,
            'status' => 'in_progress',
            'progress_pct' => 10,
        ]);

        ProjectPhase::create([
            'project_id' => $projectHidden->id,
            'name' => 'Etapa alt subcontractor',
            'contractor_id' => $otherSubcontractor->id,
            'status' => 'in_progress',
            'progress_pct' => 10,
        ]);

        $user = $this->createExternalUser('sub.portal@example.com');
        $this->assignRoleWithPermissions($user, 'subcontractor_portal', ['projects.view', 'tasks.view', 'calendar.view', 'documents.view']);

        $response = $this->actingAs($user)->get('/projects');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $projectAssigned->id)
        );
    }

    public function test_external_portal_user_cannot_access_quotes(): void
    {
        $user = $this->createExternalUser('client.portal@example.com');
        $this->assignRoleWithPermissions($user, 'client_portal', ['projects.view', 'documents.view', 'reports.view']);

        $this->actingAs($user)
            ->get('/quotes')
            ->assertForbidden();
    }

    private function createExternalUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function assignRoleWithPermissions(User $user, string $roleName, array $permissionNames): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($permissionNames)->map(function (string $name) {
            return Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        });

        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
            'tenant_id' => null,
        ]);

        $role->syncPermissions($permissions->pluck('name')->all());
        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
