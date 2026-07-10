<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_view_event_is_recorded_for_guest(): void
    {
        $this->get('/')->assertStatus(200);

        $this->assertDatabaseHas('analytics_events', [
            'event_name' => 'landing_view',
        ]);
    }

    public function test_funnel_events_are_recorded_across_registration_onboarding_project_and_upgrade(): void
    {
        $this->post('/register', [
            'name' => 'Analytics User',
            'email' => 'analytics@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/onboarding');

        $user = User::where('email', 'analytics@example.com')->firstOrFail();

        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_name' => 'register_completed',
        ]);

        $this->actingAs($user)->post('/onboarding/step-1', [
            'company_name' => 'Analytics Company',
            'company_type' => 'company',
        ])->assertRedirect();

        $this->actingAs($user)->post('/onboarding/step-2', [
            'project_name' => 'Onboarding Project',
            'project_address' => 'Str. Test 12',
            'project_budget' => 1000,
        ])->assertRedirect();

        $this->actingAs($user)->post('/onboarding/step-3', [
            'team_name' => 'Team Analytics',
            'team_specialty' => 'Execution',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_name' => 'onboarding_completed',
        ]);

        $this->seed(IamSeeder::class);
        $user = $user->fresh();

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Extra Client',
            'type' => 'company',
            'active' => true,
        ]);

        $this->actingAs($user)->post('/projects', [
            'name' => 'Extra Project',
            'client_id' => $client->id,
            'status' => 'active',
            'start_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_name' => 'first_project_created',
        ]);

        $this->actingAs($user)->patch('/billing', [
            'plan' => 'starter',
        ])->assertRedirect();

        $this->assertDatabaseHas('analytics_events', [
            'user_id' => $user->id,
            'event_name' => 'trial_upgraded',
        ]);

        $this->actingAs($user)->get('/analytics')->assertStatus(200);
    }
}
