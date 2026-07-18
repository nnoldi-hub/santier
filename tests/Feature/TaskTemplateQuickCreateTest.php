<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTemplateQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_quick_create_a_task_template(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->postJson('/task-templates/quick-create', [
            'title' => 'Turnare beton fundatie',
        ]);

        $response->assertOk();
        $response->assertJson(['title' => 'Turnare beton fundatie']);

        $this->assertDatabaseHas('task_templates', [
            'tenant_id' => 1,
            'title' => 'Turnare beton fundatie',
        ]);
        $this->assertDatabaseCount('task_templates', 1);
    }

    public function test_creating_the_same_title_twice_does_not_duplicate(): void
    {
        $user = $this->createOnboardedUser();

        $first = $this->actingAs($user)->postJson('/task-templates/quick-create', ['title' => 'Montaj gips-carton']);
        $second = $this->actingAs($user)->postJson('/task-templates/quick-create', ['title' => 'Montaj gips-carton']);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertDatabaseCount('task_templates', 1);
    }

    public function test_title_is_required(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->postJson('/task-templates/quick-create', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
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
