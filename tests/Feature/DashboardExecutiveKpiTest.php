<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardExecutiveKpiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exposes_executive_kpis_for_overdue_work_and_open_defects(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client KPI',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect KPI',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa intarziata',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 25,
            'end_date' => now()->subDays(3)->toDateString(),
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'created_by' => $user->id,
            'title' => 'Task restant',
            'description' => 'Task depasit ca deadline.',
            'status' => 'todo',
            'priority' => 'high',
            'deadline' => now()->subDay()->toDateTimeString(),
        ]);

        Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'reported_by' => $user->id,
            'title' => 'Defect deschis',
            'description' => 'Problema care trebuie urmarita.',
            'status' => 'open',
            'priority' => 'high',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.overdueTasks', 1)
            ->where('stats.delayedPhases', 1)
            ->where('stats.defects', 1)
            ->has('delayedPhases', 1)
            ->has('openDefects', 1)
            ->where('delayedPhases', function ($delayedPhases): bool {
                return $delayedPhases[0]['name'] === 'Etapa intarziata';
            })
            ->where('openDefects', function ($openDefects): bool {
                return $openDefects[0]['title'] === 'Defect deschis';
            })
        );
    }
}
