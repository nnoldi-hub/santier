<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingPlanLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_plan_cannot_create_more_than_one_project(): void
    {
        $user = $this->createOnboardedUser('free');
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client A',
            'type' => 'company',
            'active' => true,
        ]);

        $payload = [
            'name' => 'Proiect 1',
            'client_id' => $client->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
        ];

        $this->actingAs($user)->post('/projects', $payload)->assertRedirect();

        $payload['name'] = 'Proiect 2';
        $this->actingAs($user)
            ->from('/projects/create')
            ->post('/projects', $payload)
            ->assertRedirect('/projects/create');

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_free_plan_cannot_access_gantt(): void
    {
        $user = $this->createOnboardedUser('free');

        $this->actingAs($user)
            ->get('/gantt')
            ->assertRedirect('/dashboard');
    }

    public function test_starter_plan_can_access_gantt_and_csv_exports(): void
    {
        $user = $this->createOnboardedUser('starter');

        $this->actingAs($user)->get('/gantt')->assertStatus(200);
        $this->actingAs($user)->get('/exports/projects')->assertStatus(200);
    }

    public function test_starter_plan_cannot_access_enterprise_exports(): void
    {
        $user = $this->createOnboardedUser('starter');

        $this->actingAs($user)
            ->get('/exports/workbook')
            ->assertRedirect('/dashboard');
    }

    public function test_pro_plan_can_access_enterprise_exports(): void
    {
        $user = $this->createOnboardedUser('pro');

        $this->actingAs($user)->get('/exports/workbook')->assertStatus(200);
    }

    private function createOnboardedUser(string $plan): User
    {
        return User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => $plan,
        ]);
    }
}
