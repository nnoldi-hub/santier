<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_calendar_filters_assignments_by_interval(): void
    {
        $user = $this->createOnboardedUser();
        $team = Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa Calendar',
            'specialty' => 'Structura',
            'leader_id' => $user->id,
            'active' => true,
        ]);

        [$project, $phase] = $this->seedProjectPhase($user);

        PhaseTeamAssignment::create([
            'phase_id' => $phase->id,
            'team_id' => $team->id,
            'workers_needed' => 4,
            'workers_assigned' => 3,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-14',
            'notes' => 'Alocare principala',
        ]);

        PhaseTeamAssignment::create([
            'phase_id' => $phase->id,
            'team_id' => $team->id,
            'workers_needed' => 2,
            'workers_assigned' => 2,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-14',
            'notes' => 'Alocare in afara intervalului',
        ]);

        $response = $this->actingAs($user)->get('/team-calendar?start_date=2026-07-01&end_date=2026-07-31');
        $expectedTeamName = 'Echipa Calendar';

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($expectedTeamName): void {
            $page->component('TeamCalendar/Index')
            ->where('assignments.0.team.name', $expectedTeamName)
                ->where('summary.total_assignments', 1)
                ->where('summary.teams_involved', 1)
                ->where('summary.workers_needed', 4)
                ->where('summary.workers_assigned', 3);
        });
    }

    private function seedProjectPhase(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Calendar',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Calendar',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa Calendar',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 10,
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
