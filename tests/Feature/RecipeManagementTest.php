<?php

namespace Tests\Feature;

use App\Models\Equipment;
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

    public function test_user_can_create_a_recipe_with_labor_equipment_and_timing(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Turnare beton']);
        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);
        $mixer = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Betoniera',
            'type' => 'concrete_mixer',
            'cost_per_hour' => 35,
            'availability_status' => 'available',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->post('/recipes', [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Turnare beton fundatie',
            'unit' => 'mc',
            'items' => [
                ['material_id' => $ciment->id, 'quantity_per_unit' => 300],
            ],
            'labor_items' => [
                ['role' => 'Zidar', 'hours_per_unit' => 1.5, 'hourly_rate' => 45],
            ],
            'equipment_items' => [
                ['equipment_id' => $mixer->id, 'hours_per_unit' => 0.5],
            ],
            'drying_hours' => 24,
            'curing_hours' => 168,
        ]);

        $response->assertRedirect('/recipes');
        $this->assertDatabaseHas('recipes', [
            'name' => 'Turnare beton fundatie',
            'drying_hours' => 24,
            'curing_hours' => 168,
        ]);

        $recipe = Recipe::first();
        $this->assertDatabaseHas('recipe_labor_items', [
            'recipe_id' => $recipe->id,
            'role' => 'Zidar',
            'hours_per_unit' => 1.5,
            'hourly_rate' => 45,
        ]);
        $this->assertDatabaseHas('recipe_equipment_items', [
            'recipe_id' => $recipe->id,
            'equipment_id' => $mixer->id,
            'hours_per_unit' => 0.5,
        ]);
    }

    public function test_updating_a_recipe_replaces_labor_and_equipment_items(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Turnare beton']);
        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);
        $mixerOld = Equipment::create(['tenant_id' => 1, 'name' => 'Betoniera veche', 'type' => 'concrete_mixer', 'cost_per_hour' => 30, 'availability_status' => 'available', 'active' => true]);
        $mixerNew = Equipment::create(['tenant_id' => 1, 'name' => 'Betoniera noua', 'type' => 'concrete_mixer', 'cost_per_hour' => 40, 'availability_status' => 'available', 'active' => true]);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Turnare beton',
            'unit' => 'mc',
        ]);
        $recipe->items()->create(['material_id' => $ciment->id, 'quantity_per_unit' => 300]);
        $recipe->laborItems()->create(['role' => 'Zidar', 'hours_per_unit' => 1, 'hourly_rate' => 40]);
        $recipe->equipmentItems()->create(['equipment_id' => $mixerOld->id, 'hours_per_unit' => 0.5]);

        $response = $this->actingAs($user)->put("/recipes/{$recipe->id}", [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Turnare beton',
            'unit' => 'mc',
            'items' => [
                ['material_id' => $ciment->id, 'quantity_per_unit' => 300],
            ],
            'labor_items' => [
                ['role' => 'Dulgher', 'hours_per_unit' => 2, 'hourly_rate' => 50],
            ],
            'equipment_items' => [
                ['equipment_id' => $mixerNew->id, 'hours_per_unit' => 0.75],
            ],
        ]);

        $response->assertRedirect('/recipes');
        $this->assertDatabaseCount('recipe_labor_items', 1);
        $this->assertDatabaseCount('recipe_equipment_items', 1);
        $this->assertDatabaseHas('recipe_labor_items', ['recipe_id' => $recipe->id, 'role' => 'Dulgher']);
        $this->assertDatabaseHas('recipe_equipment_items', ['recipe_id' => $recipe->id, 'equipment_id' => $mixerNew->id]);
        $this->assertDatabaseMissing('recipe_equipment_items', ['equipment_id' => $mixerOld->id]);
    }

    public function test_user_can_create_a_recipe_with_wbs_stages_and_default_tasks(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Turnare beton']);
        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);

        $response = $this->actingAs($user)->post('/recipes', [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Turnare beton fundatie',
            'unit' => 'mc',
            'items' => [
                ['material_id' => $ciment->id, 'quantity_per_unit' => 300],
            ],
            'wbs_stages' => [
                ['name' => 'Sapatura', 'default_tasks' => ['Trasare sant', 'Excavare manuala']],
                ['name' => 'Turnare', 'default_tasks' => ['Pregatire mixer', '']],
            ],
        ]);

        $response->assertRedirect('/recipes');
        $this->assertDatabaseCount('recipe_wbs_stages', 2);

        $recipe = Recipe::first();
        $this->assertDatabaseHas('recipe_wbs_stages', [
            'recipe_id' => $recipe->id,
            'name' => 'Sapatura',
            'order' => 0,
        ]);
        $this->assertDatabaseHas('recipe_wbs_stages', [
            'recipe_id' => $recipe->id,
            'name' => 'Turnare',
            'order' => 1,
        ]);

        $sapatura = $recipe->wbsStages()->where('name', 'Sapatura')->firstOrFail();
        $this->assertSame(['Trasare sant', 'Excavare manuala'], $sapatura->default_tasks);

        $turnare = $recipe->wbsStages()->where('name', 'Turnare')->firstOrFail();
        $this->assertSame(['Pregatire mixer'], $turnare->default_tasks);
    }

    public function test_updating_a_recipe_replaces_wbs_stages(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Turnare beton']);
        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Turnare beton',
            'unit' => 'mc',
        ]);
        $recipe->items()->create(['material_id' => $ciment->id, 'quantity_per_unit' => 300]);
        $recipe->wbsStages()->create(['name' => 'Etapa veche', 'order' => 0, 'default_tasks' => []]);

        $response = $this->actingAs($user)->put("/recipes/{$recipe->id}", [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Turnare beton',
            'unit' => 'mc',
            'items' => [
                ['material_id' => $ciment->id, 'quantity_per_unit' => 300],
            ],
            'wbs_stages' => [
                ['name' => 'Etapa noua', 'default_tasks' => ['Task nou']],
            ],
        ]);

        $response->assertRedirect('/recipes');
        $this->assertDatabaseCount('recipe_wbs_stages', 1);
        $this->assertDatabaseHas('recipe_wbs_stages', ['recipe_id' => $recipe->id, 'name' => 'Etapa noua']);
        $this->assertDatabaseMissing('recipe_wbs_stages', ['name' => 'Etapa veche']);
    }

    public function test_equipment_from_another_tenant_is_rejected_on_recipe_creation(): void
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
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Turnare beton']);
        $ciment = Material::create(['tenant_id' => 1, 'name' => 'Ciment', 'unit' => 'kg']);
        $otherEquipment = Equipment::create(['tenant_id' => 2, 'name' => 'Utilaj intrus', 'type' => 'custom', 'cost_per_hour' => 20, 'availability_status' => 'available', 'active' => true]);

        $response = $this->actingAs($user)->post('/recipes', [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Turnare beton',
            'unit' => 'mc',
            'items' => [
                ['material_id' => $ciment->id, 'quantity_per_unit' => 300],
            ],
            'equipment_items' => [
                ['equipment_id' => $otherEquipment->id, 'hours_per_unit' => 0.5],
            ],
        ]);

        $response->assertSessionHasErrors('equipment_items.0.equipment_id');
        $this->assertDatabaseCount('recipes', 0);
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
