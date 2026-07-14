<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\SiteCompliancePlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteCompliancePlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_compliance_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/organizare")
            ->post("/projects/{$project->id}/organizare/compliance-plans", [
                'item_type' => 'autorizatie',
                'title' => 'Autorizatie de construire',
                'status' => 'valid',
                'due_date' => now()->addMonths(6)->toDateString(),
            ]);

        $response->assertRedirect("/projects/{$project->id}/organizare");

        $this->assertDatabaseHas('site_compliance_plans', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'item_type' => 'autorizatie',
            'title' => 'Autorizatie de construire',
            'status' => 'valid',
        ]);
    }

    public function test_item_type_and_title_are_required(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/compliance-plans", [
                'status' => 'missing',
            ]);

        $response->assertSessionHasErrors(['item_type', 'title']);
        $this->assertDatabaseCount('site_compliance_plans', 0);
    }

    public function test_user_can_delete_a_compliance_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $plan = SiteCompliancePlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'item_type' => 'contract',
            'title' => 'Contract subcontractor structura',
            'status' => 'missing',
        ]);

        $response = $this->actingAs($user)
            ->delete("/projects/{$project->id}/organizare/compliance-plans/{$plan->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_compliance_plans', ['id' => $plan->id]);
    }

    public function test_user_cannot_manage_compliance_plans_for_other_tenant_project(): void
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

        $response = $this->actingAs($user)
            ->post("/projects/{$otherProject->id}/organizare/compliance-plans", [
                'item_type' => 'aviz',
                'title' => 'Aviz intrus',
                'status' => 'missing',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('site_compliance_plans', 0);
    }

    public function test_contractor_id_is_scoped_to_tenant(): void
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
        $project = $this->createProject($user);
        $otherContractor = Contractor::create([
            'tenant_id' => 2,
            'name' => 'Subcontractor Intrus SRL',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/compliance-plans", [
                'item_type' => 'contract',
                'title' => 'Contract cu subcontractor din alt tenant',
                'status' => 'missing',
                'contractor_id' => $otherContractor->id,
            ]);

        $response->assertSessionHasErrors('contractor_id');
        $this->assertDatabaseCount('site_compliance_plans', 0);
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
