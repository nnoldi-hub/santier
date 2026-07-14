<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\SiteContractorPlan;
use App\Models\SiteEquipmentPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePlanApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_plan_generates_execution_artifacts(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'order' => 1,
            'status' => 'not_started',
        ]);
        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Subcontractor Test SRL',
            'type' => 'subcontractor',
            'active' => true,
        ]);
        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-APPROVE-001',
            'name' => 'Ciment',
            'unit' => 'sac',
            'unit_price' => 25,
            'active' => true,
        ]);
        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Excavator CAT 320',
            'type' => 'excavator',
            'cost_per_hour' => 150,
            'availability_status' => 'available',
            'active' => true,
        ]);

        SiteStaffPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'specialty' => 'Fierar-betonist',
            'planned_headcount' => 3,
            'risk_level' => 'medium',
        ]);
        SiteContractorPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'contractor_id' => $contractor->id,
            'contract_status' => 'signed',
            'availability_status' => 'ok',
        ]);
        SiteMaterialPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'planned_quantity' => 10,
            'risk_level' => 'low',
        ]);
        SiteEquipmentPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'risk_level' => 'low',
        ]);

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/organizare")
            ->post("/projects/{$project->id}/organizare/approve");

        $response->assertRedirect("/projects/{$project->id}/organizare");

        $this->assertDatabaseCount('tasks', 1);
        $this->assertDatabaseHas('resource_orders', [
            'project_id' => $project->id,
            'material_id' => $material->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('stage_equipment', [
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
        ]);
        $this->assertDatabaseHas('project_phases', [
            'id' => $phase->id,
            'contractor_id' => $contractor->id,
        ]);

        $project->refresh();
        $this->assertNotNull($project->plan_approved_at);
        $this->assertSame($user->id, $project->plan_approved_by);

        $this->assertDatabaseHas('site_plan_approvals', [
            'project_id' => $project->id,
            'action' => 'approved',
        ]);
    }

    public function test_equipment_plan_without_phase_is_skipped(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Macara',
            'type' => 'crane',
            'cost_per_hour' => 200,
            'availability_status' => 'available',
            'active' => true,
        ]);

        SiteEquipmentPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => null,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'risk_level' => 'low',
        ]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/organizare/approve");

        $response->assertRedirect();
        $this->assertDatabaseCount('stage_equipment', 0);
    }

    public function test_locked_plan_blocks_new_staff_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $this->actingAs($user)->post("/projects/{$project->id}/organizare/approve");

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/staff-plans", [
                'specialty' => 'Zidar',
                'planned_headcount' => 2,
                'risk_level' => 'medium',
            ]);

        $response->assertStatus(423);
    }

    public function test_unapprove_plan_unlocks_editing(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $this->actingAs($user)->post("/projects/{$project->id}/organizare/approve");

        $response = $this->actingAs($user)->post("/projects/{$project->id}/organizare/unapprove");
        $response->assertRedirect();

        $project->refresh();
        $this->assertNull($project->plan_approved_at);
        $this->assertNull($project->plan_approved_by);

        $this->assertDatabaseHas('site_plan_approvals', [
            'project_id' => $project->id,
            'action' => 'unapproved',
        ]);

        $storeResponse = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/staff-plans", [
                'specialty' => 'Zidar',
                'planned_headcount' => 2,
                'risk_level' => 'medium',
            ]);

        $storeResponse->assertRedirect();
        $this->assertDatabaseCount('site_staff_plans', 1);
    }

    public function test_cannot_approve_an_already_approved_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $this->actingAs($user)->post("/projects/{$project->id}/organizare/approve");

        $response = $this->actingAs($user)->post("/projects/{$project->id}/organizare/approve");

        $response->assertStatus(422);
    }

    public function test_user_cannot_approve_another_tenants_project(): void
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

        $response = $this->actingAs($user)->post("/projects/{$otherProject->id}/organizare/approve");

        $response->assertNotFound();
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
