<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\Project;
use App\Models\Recipe;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMaterialPlanRecipeApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_applying_a_recipe_generates_a_material_plan_per_item(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);
        $pietris = Material::create(['tenant_id' => 1, 'name' => 'Pietris', 'unit' => 'mc']);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'material',
            'subject_id' => Material::create(['tenant_id' => 1, 'name' => 'Beton C25/30', 'unit' => 'mc'])->id,
            'name' => 'Beton C25/30',
            'unit' => 'mc',
        ]);
        $recipe->items()->create(['material_id' => $ciment->id, 'quantity_per_unit' => 300]);
        $recipe->items()->create(['material_id' => $pietris->id, 'quantity_per_unit' => 0.7]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/organizare/material-plans/apply-recipe", [
            'recipe_id' => $recipe->id,
            'work_quantity' => 10,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('site_material_plans', 2);
        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'material_id' => $ciment->id,
            'planned_quantity' => 3000,
            'risk_level' => 'medium',
        ]);
        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'material_id' => $pietris->id,
            'planned_quantity' => 7,
            'risk_level' => 'medium',
        ]);
    }

    public function test_applying_a_recipe_snapshots_the_material_unit_price(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg', 'unit_price' => 0.85]);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'material',
            'subject_id' => Material::create(['tenant_id' => 1, 'name' => 'Beton C25/30', 'unit' => 'mc'])->id,
            'name' => 'Beton C25/30',
            'unit' => 'mc',
        ]);
        $recipe->items()->create(['material_id' => $ciment->id, 'quantity_per_unit' => 300]);

        $this->actingAs($user)->post("/projects/{$project->id}/organizare/material-plans/apply-recipe", [
            'recipe_id' => $recipe->id,
            'work_quantity' => 10,
        ]);

        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'material_id' => $ciment->id,
            'unit_price' => 0.85,
        ]);

        $ciment->update(['unit_price' => 99]);

        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'material_id' => $ciment->id,
            'unit_price' => 0.85,
        ]);
    }

    public function test_apply_recipe_is_blocked_when_plan_is_approved(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $project->update(['plan_approved_at' => now(), 'plan_approved_by' => $user->id]);

        $material = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);
        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'material',
            'subject_id' => $material->id,
            'name' => 'Reteta',
            'unit' => 'mc',
        ]);
        $recipe->items()->create(['material_id' => $material->id, 'quantity_per_unit' => 300]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/organizare/material-plans/apply-recipe", [
            'recipe_id' => $recipe->id,
            'work_quantity' => 10,
        ]);

        $response->assertStatus(423);
        $this->assertDatabaseCount('site_material_plans', 0);
    }

    public function test_recipe_must_belong_to_the_same_tenant(): void
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

        $otherMaterial = Material::create(['tenant_id' => 2, 'name' => 'Material Intrus', 'unit' => 'kg']);
        $otherRecipe = Recipe::create([
            'tenant_id' => 2,
            'subject_type' => 'material',
            'subject_id' => $otherMaterial->id,
            'name' => 'Reteta intrusa',
            'unit' => 'mc',
        ]);
        $otherRecipe->items()->create(['material_id' => $otherMaterial->id, 'quantity_per_unit' => 1]);

        $response = $this->actingAs($user)->post("/projects/{$project->id}/organizare/material-plans/apply-recipe", [
            'recipe_id' => $otherRecipe->id,
            'work_quantity' => 10,
        ]);

        $response->assertSessionHasErrors('recipe_id');
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

    private function createOnboardedUser(): User
    {
        return User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);
    }
}
