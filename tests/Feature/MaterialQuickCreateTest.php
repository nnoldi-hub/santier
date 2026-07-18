<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_quick_create_a_material(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->postJson('/materials/quick-create', [
            'name' => 'Ciment rapid',
            'unit' => 'sac',
            'unit_price' => 25.5,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'name', 'unit']);
        $response->assertJson(['name' => 'Ciment rapid', 'unit' => 'sac']);

        $this->assertDatabaseHas('materials', [
            'tenant_id' => 1,
            'name' => 'Ciment rapid',
            'unit' => 'sac',
            'active' => 1,
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->postJson('/materials/quick-create', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'unit', 'unit_price']);
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
