<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageReport;
use App\Models\StageTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardFinancialInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_document_financial_metrics(): void
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Dashboard',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Dashboard',
            'status' => 'active',
        ]);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Factura restanta',
            'type' => 'invoice',
            'project_id' => $project->id,
            'amount' => 1100,
            'issued_at' => now()->subDays(40)->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Factura partiala',
            'type' => 'invoice',
            'project_id' => $project->id,
            'amount' => 900,
            'issued_at' => now()->subDays(10)->toDateString(),
            'payment_status' => 'partial',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa planificata',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 30,
        ]);

        StageReport::create([
            'stage_id' => $phase->id,
            'report_date' => now()->toDateString(),
            'progress_pct' => 40,
            'activities' => 'Avans pe executie.',
            'created_by' => $user->id,
        ]);

        StageTask::create([
            'stage_id' => $phase->id,
            'title' => 'Task operational',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('stats.documentsUnpaidCount', 2)
            ->where('stats.documentsUnpaidAmount', 2000)
            ->where('stats.documentsOverdueInvoices', 1)
            ->where('stats.stageTasksOpen', 1)
            ->where('stagePlanVsReal.0.stage_name', 'Etapa planificata')
            ->where('stagePlanVsReal.0.planned_progress', 30)
            ->where('stagePlanVsReal.0.actual_progress', 40)
            ->where('stagePlanVsReal.0.progress_delta', 10)
        );
    }
}
