<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Project;
use App\Models\SiteEquipmentPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteEquipmentPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_an_equipment_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $equipment = $this->createEquipment();

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/organizare")
            ->post("/projects/{$project->id}/organizare/equipment-plans", [
                'equipment_id' => $equipment->id,
                'quantity' => 2,
                'usage_start' => now()->toDateString(),
                'usage_end' => now()->addDays(4)->toDateString(),
                'risk_level' => 'medium',
            ]);

        $response->assertRedirect("/projects/{$project->id}/organizare");

        $this->assertDatabaseHas('site_equipment_plans', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'equipment_id' => $equipment->id,
            'quantity' => 2,
            'risk_level' => 'medium',
        ]);
    }

    public function test_equipment_id_and_quantity_are_required(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/equipment-plans", [
                'risk_level' => 'medium',
            ]);

        $response->assertSessionHasErrors(['equipment_id', 'quantity']);
        $this->assertDatabaseCount('site_equipment_plans', 0);
    }

    public function test_equipment_plan_defaults_hourly_rate_from_catalog_when_not_provided(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $equipment = $this->createEquipment();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/equipment-plans", [
                'equipment_id' => $equipment->id,
                'quantity' => 1,
                'risk_level' => 'medium',
            ]);

        $this->assertDatabaseHas('site_equipment_plans', [
            'project_id' => $project->id,
            'equipment_id' => $equipment->id,
            'hourly_rate' => 150,
        ]);
    }

    public function test_equipment_plan_rate_is_frozen_after_catalog_price_changes(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $equipment = $this->createEquipment();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/equipment-plans", [
                'equipment_id' => $equipment->id,
                'quantity' => 1,
                'risk_level' => 'medium',
            ]);

        $equipment->update(['cost_per_hour' => 999]);

        $plan = SiteEquipmentPlan::where('project_id', $project->id)->first();
        $this->assertEquals(150, (float) $plan->hourly_rate);
    }

    public function test_user_can_delete_an_equipment_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $equipment = $this->createEquipment();

        $plan = SiteEquipmentPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'risk_level' => 'low',
        ]);

        $response = $this->actingAs($user)
            ->delete("/projects/{$project->id}/organizare/equipment-plans/{$plan->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_equipment_plans', ['id' => $plan->id]);
    }

    public function test_user_cannot_manage_equipment_plans_for_other_tenant_project(): void
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
        $otherEquipment = Equipment::create([
            'tenant_id' => 2,
            'name' => 'Excavator Intrus',
            'type' => 'excavator',
            'cost_per_hour' => 100,
            'availability_status' => 'available',
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post("/projects/{$otherProject->id}/organizare/equipment-plans", [
                'equipment_id' => $otherEquipment->id,
                'quantity' => 1,
                'risk_level' => 'medium',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('site_equipment_plans', 0);
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

    private function createEquipment(): Equipment
    {
        return Equipment::create([
            'tenant_id' => 1,
            'name' => 'Excavator CAT 320',
            'type' => 'excavator',
            'cost_per_hour' => 150,
            'availability_status' => 'available',
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
