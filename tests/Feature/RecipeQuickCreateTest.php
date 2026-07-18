<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\TaskTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_quick_create_a_recipe_for_a_task_template(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Zugravit']);
        $vopsea = Material::create(['tenant_id' => 1, 'name' => 'Vopsea', 'unit' => 'L']);

        $response = $this->actingAs($user)->postJson('/recipes/quick-create', [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Zugravit',
            'unit' => 'mp',
            'items' => [
                ['material_id' => $vopsea->id, 'quantity_per_unit' => 0.15],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'unit', 'items']);
        $response->assertJson(['unit' => 'mp']);

        $this->assertDatabaseHas('recipes', [
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
        ]);
    }

    public function test_items_are_required(): void
    {
        $user = $this->createOnboardedUser();
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Zugravit']);

        $response = $this->actingAs($user)->postJson('/recipes/quick-create', [
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Zugravit',
            'unit' => 'mp',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items']);
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
