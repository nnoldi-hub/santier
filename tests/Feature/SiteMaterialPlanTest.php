<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\Project;
use App\Models\SiteMaterialPlan;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMaterialPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_material_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial();

        $response = $this->actingAs($user)
            ->from("/projects/{$project->id}/organizare")
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'material_id' => $material->id,
                'planned_quantity' => 25.5,
                'supplier_name' => 'Furnizor Test SRL',
                'lead_time_days' => 10,
                'risk_level' => 'medium',
            ]);

        $response->assertRedirect("/projects/{$project->id}/organizare");

        $this->assertDatabaseHas('site_material_plans', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'material_id' => $material->id,
            'supplier_name' => 'Furnizor Test SRL',
            'lead_time_days' => 10,
            'risk_level' => 'medium',
        ]);
    }

    public function test_material_id_and_planned_quantity_are_required(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $response = $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'risk_level' => 'medium',
            ]);

        $response->assertSessionHasErrors(['material_id', 'planned_quantity']);
        $this->assertDatabaseCount('site_material_plans', 0);
    }

    public function test_material_plan_defaults_unit_price_from_catalog_when_not_provided(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial(45.50);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'material_id' => $material->id,
                'planned_quantity' => 10,
                'risk_level' => 'medium',
            ]);

        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'material_id' => $material->id,
            'unit_price' => 45.50,
        ]);
    }

    public function test_material_plan_price_is_frozen_after_catalog_price_changes(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial(45.50);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'material_id' => $material->id,
                'planned_quantity' => 10,
                'risk_level' => 'medium',
            ]);

        $material->update(['unit_price' => 999]);

        $plan = SiteMaterialPlan::where('project_id', $project->id)->first();
        $this->assertEquals(45.50, (float) $plan->unit_price);
    }

    public function test_order_date_is_computed_from_delivery_date_and_lead_time(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'material_id' => $material->id,
                'planned_quantity' => 10,
                'planned_delivery_date' => '2026-08-15',
                'lead_time_days' => 7,
                'risk_level' => 'medium',
            ]);

        $plan = SiteMaterialPlan::where('project_id', $project->id)->first();
        $this->assertSame('2026-08-15', $plan->planned_delivery_date->toDateString());
        $this->assertSame('2026-08-08', $plan->planned_order_date->toDateString());
    }

    public function test_explicit_order_date_is_not_overwritten_by_lead_time_calculation(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'material_id' => $material->id,
                'planned_quantity' => 10,
                'planned_delivery_date' => '2026-08-15',
                'lead_time_days' => 7,
                'planned_order_date' => '2026-08-01',
                'risk_level' => 'medium',
            ]);

        $plan = SiteMaterialPlan::where('project_id', $project->id)->first();
        $this->assertSame('2026-08-01', $plan->planned_order_date->toDateString());
    }

    public function test_choosing_a_supplier_from_catalog_fills_in_the_supplier_name(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Dedeman SRL', 'active' => true]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'material_id' => $material->id,
                'planned_quantity' => 10,
                'supplier_id' => $supplier->id,
                'risk_level' => 'medium',
            ]);

        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => 'Dedeman SRL',
        ]);
    }

    public function test_supplier_name_snapshot_is_frozen_after_supplier_is_renamed(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Dedeman SRL', 'active' => true]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/organizare/material-plans", [
                'material_id' => $material->id,
                'planned_quantity' => 10,
                'supplier_id' => $supplier->id,
                'risk_level' => 'medium',
            ]);

        $supplier->update(['name' => 'Dedeman Nume Nou SRL']);

        $plan = SiteMaterialPlan::where('project_id', $project->id)->first();
        $this->assertSame('Dedeman SRL', $plan->supplier_name);
    }

    public function test_user_can_delete_a_material_plan(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = $this->createMaterial();

        $plan = SiteMaterialPlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'material_id' => $material->id,
            'planned_quantity' => 10,
            'risk_level' => 'low',
        ]);

        $response = $this->actingAs($user)
            ->delete("/projects/{$project->id}/organizare/material-plans/{$plan->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('site_material_plans', ['id' => $plan->id]);
    }

    public function test_user_cannot_manage_material_plans_for_other_tenant_project(): void
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
        $otherMaterial = Material::create([
            'tenant_id' => 2,
            'code' => 'MAT-INTRUS',
            'name' => 'Material Intrus',
            'unit' => 'buc',
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post("/projects/{$otherProject->id}/organizare/material-plans", [
                'material_id' => $otherMaterial->id,
                'planned_quantity' => 5,
                'risk_level' => 'medium',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('site_material_plans', 0);
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

    private function createMaterial(float $unitPrice = 0): Material
    {
        return Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-001',
            'name' => 'Ciment',
            'unit' => 'sac',
            'unit_price' => $unitPrice,
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
