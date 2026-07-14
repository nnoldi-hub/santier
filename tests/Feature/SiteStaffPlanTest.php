<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\SiteStaffPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteStaffPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_staff_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/organizare")
            ->post("/projects/{$project->id}/organizare/staff-plans", [
                'specialty' => 'Electrician',
                'planned_headcount' => 3,
                'risk_level' => 'medium',
            ]);

        $response->assertRedirect("/projects/{$project->id}/organizare");

        $this->assertDatabaseHas('site_staff_plans', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'specialty' => 'Electrician',
            'planned_headcount' => 3,
            'risk_level' => 'medium',
        ]);
    }

    public function test_specialty_is_required(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/staff-plans", [
                'planned_headcount' => 2,
                'risk_level' => 'low',
            ]);

        $response->assertSessionHasErrors('specialty');
        $this->assertDatabaseCount('site_staff_plans', 0);
    }

    public function test_user_can_delete_a_staff_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $plan = SiteStaffPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'specialty' => 'Zidar',
            'planned_headcount' => 2,
            'risk_level' => 'low',
        ]);

        $response = $this->actingAs($user)
            ->delete("/projects/{$project->id}/organizare/staff-plans/{$plan->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_staff_plans', ['id' => $plan->id]);
    }

    public function test_user_cannot_manage_staff_plans_for_other_tenant_project(): void
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
            ->post("/projects/{$otherProject->id}/organizare/staff-plans", [
                'specialty' => 'Instalator',
                'planned_headcount' => 1,
                'risk_level' => 'low',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('site_staff_plans', 0);
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
