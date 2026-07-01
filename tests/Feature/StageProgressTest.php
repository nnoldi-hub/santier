<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StageProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_progress_shows_phase_summary_and_filters(): void
    {
        $user = $this->createOnboardedUser();
        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Progres',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'active' => true,
        ]);
        [$project, $phase] = $this->seedProjectPhase($user, $contractor);

        ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa finalizata',
            'type' => 'custom',
            'status' => 'completed',
            'progress_pct' => 100,
            'contractor_id' => $contractor->id,
        ]);

        $response = $this->actingAs($user)->get('/stage-progress?status=in_progress&project_id=' . $project->id);
        $expectedPhaseName = 'Etapa control';

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($expectedPhaseName): void {
            $page->component('StageProgress/Index')
                ->where('summary.phases_count', 1)
                ->where('summary.average_progress', 45)
                ->where('summary.completed_count', 0)
                ->where('summary.in_progress_count', 1)
                ->where('summary.not_started_count', 0)
                ->where('phases.data.0.name', $expectedPhaseName);
        });
    }

    private function seedProjectPhase(User $user, Contractor $contractor): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Progres',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Progres',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa control',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 45,
            'contractor_id' => $contractor->id,
        ]);

        return [$project, $phase];
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
