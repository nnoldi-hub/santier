<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_quick_create_a_client(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->postJson('/clients/quick-create', [
            'name' => 'Client Rapid SRL',
            'type' => 'company',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'name']);
        $response->assertJson(['name' => 'Client Rapid SRL']);

        $this->assertDatabaseHas('clients', [
            'tenant_id' => 1,
            'name' => 'Client Rapid SRL',
            'type' => 'company',
            'active' => 1,
        ]);
    }

    public function test_name_and_type_are_required(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)->postJson('/clients/quick-create', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'type']);
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
