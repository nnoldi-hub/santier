<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\Recipe;
use App\Models\TaskTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_recipe_for_a_task_template(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Zugravit']);
        $vopsea = Material::create(['tenant_id' => 1, 'name' => 'Vopsea lavabila', 'unit' => 'L']);

        $response = $this->actingAs($user)->post('/recipes', [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Zugravit lavabil',
            'unit' => 'mp',
            'items' => [
                ['material_id' => $vopsea->id, 'quantity_per_unit' => 0.15],
            ],
        ]);

        $response->assertRedirect('/recipes');
        $this->assertDatabaseHas('recipes', [
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Zugravit lavabil',
            'unit' => 'mp',
        ]);
        $recipe = Recipe::first();
        $this->assertDatabaseHas('recipe_items', [
            'recipe_id' => $recipe->id,
            'material_id' => $vopsea->id,
            'quantity_per_unit' => 0.15,
        ]);
    }

    public function test_user_can_create_a_recipe_for_a_composite_material(): void
    {
        $user = $this->createOnboardedUser();
        $beton = Material::create(['tenant_id' => 1, 'name' => 'Beton C25/30', 'unit' => 'mc']);
        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);
        $pietris = Material::create(['tenant_id' => 1, 'name' => 'Pietris', 'unit' => 'mc']);

        $response = $this->actingAs($user)->post('/recipes', [
            'subject_type' => 'material',
            'subject_id' => $beton->id,
            'name' => 'Beton C25/30',
            'unit' => 'mc',
            'items' => [
                ['material_id' => $ciment->id, 'quantity_per_unit' => 300],
                ['material_id' => $pietris->id, 'quantity_per_unit' => 0.7],
            ],
        ]);

        $response->assertRedirect('/recipes');
        $this->assertDatabaseCount('recipe_items', 2);
    }

    public function test_user_can_update_a_recipe(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Zugravit']);
        $vopsea = Material::create(['tenant_id' => 1, 'name' => 'Vopsea', 'unit' => 'L']);
        $glet = Material::create(['tenant_id' => 1, 'name' => 'Glet', 'unit' => 'kg']);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Zugravit',
            'unit' => 'mp',
        ]);
        $recipe->items()->create(['material_id' => $vopsea->id, 'quantity_per_unit' => 0.15]);

        $response = $this->actingAs($user)->put("/recipes/{$recipe->id}", [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Zugravit actualizat',
            'unit' => 'mp',
            'items' => [
                ['material_id' => $vopsea->id, 'quantity_per_unit' => 0.18],
                ['material_id' => $glet->id, 'quantity_per_unit' => 0.05],
            ],
        ]);

        $response->assertRedirect('/recipes');
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'name' => 'Zugravit actualizat']);
        $this->assertDatabaseCount('recipe_items', 2);
    }

    public function test_user_can_delete_a_recipe(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Zugravit']);
        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Zugravit',
            'unit' => 'mp',
        ]);

        $response = $this->actingAs($user)->delete("/recipes/{$recipe->id}");

        $response->assertRedirect('/recipes');
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_user_cannot_manage_another_tenants_recipe(): void
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
        $otherTemplate = TaskTemplate::create(['tenant_id' => 2, 'title' => 'Etapa intrusa']);
        $otherRecipe = Recipe::create([
            'tenant_id' => 2,
            'subject_type' => 'task_template',
            'subject_id' => $otherTemplate->id,
            'name' => 'Reteta intrusa',
            'unit' => 'mp',
        ]);

        $this->actingAs($user)->get("/recipes/{$otherRecipe->id}/edit")->assertNotFound();
        $this->actingAs($user)->delete("/recipes/{$otherRecipe->id}")->assertNotFound();
        $this->assertDatabaseHas('recipes', ['id' => $otherRecipe->id]);
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
