<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
use App\Models\User;
use App\Support\SitePlanningAIAdvisor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SitePlanningAIAdvisorTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_without_staff_plan_produces_a_staff_suggestion(): void
    {
        $phase = $this->makePhase(1, 'Structura', 'structura', 25);

        $result = SitePlanningAIAdvisor::suggest(collect([$phase]), collect(), collect());

        $this->assertCount(1, $result['staff']);
        $this->assertSame(1, $result['staff'][0]['phase_id']);
    }

    public function test_phase_with_existing_staff_plan_produces_no_staff_suggestion(): void
    {
        $phase = $this->makePhase(1, 'Structura', 'structura', 25);
        $staffPlan = new SiteStaffPlan(['risk_level' => 'low']);
        $staffPlan->phase_id = 1;

        $result = SitePlanningAIAdvisor::suggest(collect([$phase]), collect([$staffPlan]), collect());

        $this->assertCount(0, $result['staff']);
    }

    public function test_duration_far_outside_typical_range_produces_a_timeline_suggestion(): void
    {
        $phase = $this->makePhase(2, 'Structura scurta', 'structura', 2);

        $result = SitePlanningAIAdvisor::suggest(collect([$phase]), collect(), collect());

        $this->assertCount(1, $result['timeline']);
        $this->assertSame(2, $result['timeline'][0]['phase_id']);
    }

    public function test_custom_phase_type_never_produces_suggestions(): void
    {
        $phase = $this->makePhase(3, 'Etapa personalizata', 'custom', null);

        $result = SitePlanningAIAdvisor::suggest(collect([$phase]), collect(), collect());

        $this->assertCount(0, $result['staff']);
        $this->assertCount(0, $result['materials']);
        $this->assertCount(0, $result['timeline']);
    }

    public function test_organizare_page_returns_ai_suggestions_in_payload(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);

        ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'type' => 'structura',
            'order' => 1,
            'status' => 'not_started',
        ]);

        $response = $this->actingAs($user)->get("/projects/{$project->id}/organizare");

        $response->assertInertia(function (Assert $page) {
            $page->has('aiSuggestions.staff')
                ->has('aiSuggestions.materials')
                ->has('aiSuggestions.timeline');
        });
    }

    private function makePhase(int $id, string $name, string $type, ?int $durationDays): ProjectPhase
    {
        $phase = new ProjectPhase([
            'name' => $name,
            'type' => $type,
            'duration_days' => $durationDays,
        ]);
        $phase->id = $id;

        return $phase;
    }

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Organizare SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Organizare',
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
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
