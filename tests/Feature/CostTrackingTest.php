<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Quote;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CostTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cost_tracking_shows_budget_summary_and_projects(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->seedProject($user, 'Proiect Cost Tracking', 10000);

        Quote::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'version' => 1,
            'title' => 'Oferta principala',
            'status' => 'accepted',
            'total_net' => 8000,
            'total_tva' => 1520,
            'total_gross' => 9520,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/cost-tracking');
        $expectedProjectName = 'Proiect Cost Tracking';

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($expectedProjectName): void {
            $page->component('CostTracking/Index')
                ->where('summary.projects_count', 1)
                ->where('summary.budget_total', 10000)
                ->where('summary.quotes_total', 9520)
                ->where('summary.accepted_total', 9520)
                ->where('summary.over_budget_count', 0)
            ->where('projects.0.project_name', $expectedProjectName);
        });
    }

    private function seedProject(User $user, string $name, float $budget): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Cost',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => $name,
            'status' => 'active',
            'total_budget' => $budget,
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
