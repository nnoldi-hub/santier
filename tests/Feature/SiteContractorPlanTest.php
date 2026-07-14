<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\SiteContractorPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteContractorPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_contractor_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $contractor = $this->createContractor();

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/organizare")
            ->post("/projects/{$project->id}/organizare/contractor-plans", [
                'contractor_id' => $contractor->id,
                'contract_status' => 'draft',
                'availability_status' => 'ok',
            ]);

        $response->assertRedirect("/projects/{$project->id}/organizare");

        $this->assertDatabaseHas('site_contractor_plans', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'contractor_id' => $contractor->id,
            'contract_status' => 'draft',
            'availability_status' => 'ok',
        ]);
    }

    public function test_contractor_id_is_required(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/contractor-plans", [
                'contract_status' => 'draft',
                'availability_status' => 'ok',
            ]);

        $response->assertSessionHasErrors('contractor_id');
        $this->assertDatabaseCount('site_contractor_plans', 0);
    }

    public function test_user_can_delete_a_contractor_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $contractor = $this->createContractor();

        $plan = SiteContractorPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'contractor_id' => $contractor->id,
            'contract_status' => 'missing',
            'availability_status' => 'ok',
        ]);

        $response = $this->actingAs($user)
            ->delete("/projects/{$project->id}/organizare/contractor-plans/{$plan->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_contractor_plans', ['id' => $plan->id]);
    }

    public function test_user_cannot_manage_contractor_plans_for_other_tenant_project(): void
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
        $otherContractor = Contractor::create([
            'tenant_id' => 2,
            'name' => 'Contractor Intrus SRL',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post("/projects/{$otherProject->id}/organizare/contractor-plans", [
                'contractor_id' => $otherContractor->id,
                'contract_status' => 'draft',
                'availability_status' => 'ok',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('site_contractor_plans', 0);
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

    private function createContractor(): Contractor
    {
        return Contractor::create([
            'tenant_id' => 1,
            'name' => 'Subcontractor Test SRL',
            'type' => 'subcontractor',
            'active' => true,
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
