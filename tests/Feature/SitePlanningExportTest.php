<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\Project;
use App\Models\SiteMaterialPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePlanningExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_export_the_plan_as_pdf(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $this->seedMaterialPlan($project);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare/export/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_user_can_export_the_plan_as_xlsx(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $this->seedMaterialPlan($project);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare/export/xlsx");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_user_cannot_export_another_tenants_project(): void
    {
        Tenant::create([
            'id' => 2,
            'name' => 'Tenant intrus',
            'slug' => 'tenant-intrus',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);

        $user = $this->createOnboardedUser();

        $otherOwner = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2]);
        $otherClient = Client::create([
            'tenant_id' => 2,
            'name' => 'Client Intrus SRL',
            'type' => 'company',
            'active' => true,
        ]);
        $otherProject = Project::create([
            'tenant_id' => 2,
            'client_id' => $otherClient->id,
            'created_by' => $otherOwner->id,
            'name' => 'Proiect Intrus',
            'status' => 'active',
            'total_budget' => 1000,
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get("/projects/{$otherProject->id}/organizare/export/pdf");

        $response->assertNotFound();
    }

    private function seedMaterialPlan(Project $project): void
    {
        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-EXPORT-001',
            'name' => 'Ciment',
            'unit' => 'sac',
            'unit_price' => 25,
            'active' => true,
        ]);

        SiteMaterialPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'material_id' => $material->id,
            'planned_quantity' => 10,
            'risk_level' => 'low',
        ]);
    }

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Organizare SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Organizare',
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
        ]);
    }

    private function createOnboardedUser(): User
    {
        return User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);
    }
}
