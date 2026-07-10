<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_onboarding_is_redirected_from_dashboard_to_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
            'onboarding_step' => 1,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/onboarding');
    }

    public function test_user_can_complete_three_step_onboarding_flow(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
            'onboarding_step' => 1,
        ]);

        $this->actingAs($user)->post('/onboarding/step-1', [
            'company_name' => 'Santier Demo SRL',
            'company_type' => 'company',
            'contact_phone' => '0711111111',
        ])->assertRedirect();

        $user->refresh();
        $this->assertEquals(2, $user->onboarding_step);

        $this->actingAs($user)->post('/onboarding/step-2', [
            'project_name' => 'Proiect Pilot',
            'project_address' => 'Str. Constructorilor 10',
            'project_budget' => 50000,
        ])->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name' => 'Proiect Pilot',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->post('/onboarding/step-3', [
            'team_name' => 'Echipa Pilot',
            'team_specialty' => 'Renovari',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('teams', [
            'name' => 'Echipa Pilot',
            'leader_id' => $user->id,
        ]);

        $user->refresh();
        $this->assertNotNull($user->onboarding_completed_at);
    }

    public function test_completed_user_can_access_projects_index_without_onboarding_redirect(): void
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $this->seed(IamSeeder::class);
        $user = $user->fresh();

        $response = $this->actingAs($user)->get('/projects');

        $response->assertStatus(200);
    }
}
