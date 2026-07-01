<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StageReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_report_can_be_created(): void
    {
        $user = $this->createOnboardedUser();
        [$stage, $contractor] = $this->seedContext($user);

        $response = $this->actingAs($user)->post('/stage-reports', [
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'report_date' => '2026-07-01',
            'progress_pct' => 35,
            'activities' => 'Montaj structura metalica.',
            'issues' => 'Intarziere furnizor.',
        ]);

        $response->assertRedirect('/stage-reports');

        $this->assertDatabaseHas('stage_reports', [
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'progress_pct' => 35,
            'created_by' => $user->id,
        ]);
    }

    public function test_stage_reports_index_returns_inertia_payload(): void
    {
        $user = $this->createOnboardedUser();
        [$stage, $contractor] = $this->seedContext($user);

        StageReport::create([
            'stage_id' => $stage->id,
            'contractor_id' => $contractor->id,
            'report_date' => '2026-07-01',
            'progress_pct' => 55,
            'activities' => 'Turnare placa.',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/stage-reports');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('StageReports/Index')
            ->where('reports.data.0.progress_pct', 55)
            ->where('reports.data.0.stage.name', $stage->name)
        );
    }

    private function seedContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Rapoarte',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Rapoarte',
            'status' => 'active',
        ]);

        $stage = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa Structura',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 20,
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Rapoarte',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        return [$stage, $contractor];
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
