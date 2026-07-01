<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\StageEquipment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DemoUserScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_sees_only_demo_projects_in_evaluation_views(): void
    {
        $expectedProjectName = 'Renovare Office Park - Corp A';
        $expectedDocumentTitle = 'Document Demo';
        $expectedInvoiceNo = 'DEMO-INV';

        $demoUser = User::factory()->create([
            'name' => 'Demo Public Santier',
            'email' => config('demo.email', 'demo@santier.local'),
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $otherUser = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Demo Scope',
            'type' => 'company',
            'active' => true,
        ]);

        $demoProject = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $demoUser->id,
            'name' => $expectedProjectName,
            'status' => 'active',
            'total_budget' => 200000,
        ]);

        $otherProject = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $otherUser->id,
            'name' => 'Proiect Vechi Din Tenant',
            'status' => 'active',
            'total_budget' => 99000,
        ]);

        ProjectPhase::create([
            'project_id' => $demoProject->id,
            'name' => 'Etapa Demo',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 40,
        ]);

        ProjectPhase::create([
            'project_id' => $otherProject->id,
            'name' => 'Etapa Veche',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 5,
        ]);

        Quote::create([
            'tenant_id' => 1,
            'project_id' => $demoProject->id,
            'version' => 1,
            'title' => 'Oferta Demo',
            'status' => 'accepted',
            'total_net' => 100000,
            'total_tva' => 19000,
            'total_gross' => 119000,
            'created_by' => $demoUser->id,
        ]);

        Quote::create([
            'tenant_id' => 1,
            'project_id' => $otherProject->id,
            'version' => 1,
            'title' => 'Oferta Veche',
            'status' => 'accepted',
            'total_net' => 50000,
            'total_tva' => 9500,
            'total_gross' => 59500,
            'created_by' => $otherUser->id,
        ]);

        Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa Demo',
            'leader_id' => $demoUser->id,
            'active' => true,
        ]);

        Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa Veche',
            'leader_id' => $otherUser->id,
            'active' => true,
        ]);

        $demoContractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Demo',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'active' => true,
        ]);

        $otherContractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Vechi',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'active' => true,
        ]);

        $demoPhase = ProjectPhase::query()->where('project_id', $demoProject->id)->firstOrFail();
        $otherPhase = ProjectPhase::query()->where('project_id', $otherProject->id)->firstOrFail();

        $demoPhase->update(['contractor_id' => $demoContractor->id]);
        $otherPhase->update(['contractor_id' => $otherContractor->id]);

        $demoTeam = Team::query()->where('name', 'Echipa Demo')->firstOrFail();
        $otherTeam = Team::query()->where('name', 'Echipa Veche')->firstOrFail();

        PhaseTeamAssignment::create([
            'phase_id' => $demoPhase->id,
            'team_id' => $demoTeam->id,
            'workers_needed' => 4,
            'workers_assigned' => 3,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-12',
        ]);

        PhaseTeamAssignment::create([
            'phase_id' => $otherPhase->id,
            'team_id' => $otherTeam->id,
            'workers_needed' => 6,
            'workers_assigned' => 6,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-12',
        ]);

        $demoEquipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Utilaj Demo',
            'type' => 'generator',
            'cost_per_hour' => 100,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);

        $otherEquipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Utilaj Vechi',
            'type' => 'generator',
            'cost_per_hour' => 50,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);

        StageEquipment::create([
            'stage_id' => $demoPhase->id,
            'equipment_id' => $demoEquipment->id,
            'quantity' => 2,
            'usage_start' => '2026-07-08',
            'usage_end' => '2026-07-10',
        ]);

        StageEquipment::create([
            'stage_id' => $otherPhase->id,
            'equipment_id' => $otherEquipment->id,
            'quantity' => 1,
            'usage_start' => '2026-07-08',
            'usage_end' => '2026-07-10',
        ]);

        Document::create([
            'tenant_id' => 1,
            'project_id' => $demoProject->id,
            'stage_id' => $demoPhase->id,
            'contractor_id' => $demoContractor->id,
            'title' => 'Document Demo',
            'type' => 'invoice',
            'amount' => 1200,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        Document::create([
            'tenant_id' => 1,
            'project_id' => $otherProject->id,
            'stage_id' => $otherPhase->id,
            'contractor_id' => $otherContractor->id,
            'title' => 'Document Vechi',
            'type' => 'invoice',
            'amount' => 800,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $demoMaterial = Material::create([
            'tenant_id' => 1,
            'code' => 'DEMO-MAT',
            'name' => 'Material Demo',
            'category' => 'Demo',
            'unit' => 'buc',
            'unit_price' => 100,
            'supplier' => 'Demo Supplier',
            'active' => true,
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $demoProject->id,
            'phase_id' => $demoPhase->id,
            'material_id' => $demoMaterial->id,
            'invoice_no' => 'DEMO-INV',
            'issue_date' => now()->toDateString(),
            'amount_net' => 100,
            'amount_vat' => 19,
            'amount_total' => 119,
            'payment_status' => 'unpaid',
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $otherProject->id,
            'phase_id' => $otherPhase->id,
            'material_id' => $demoMaterial->id,
            'invoice_no' => 'OLD-INV',
            'issue_date' => now()->toDateString(),
            'amount_net' => 200,
            'amount_vat' => 38,
            'amount_total' => 238,
            'payment_status' => 'unpaid',
        ]);

        $this->actingAs($demoUser)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.activeProjects', 1)
                ->where('recentProjects.0.name', $expectedProjectName)
            );

        $this->actingAs($demoUser)
            ->get('/wbs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Wbs/Index')
                ->where('projects', fn ($projects) => $projects->count() === 1 && $projects->first()['name'] === $expectedProjectName)
                ->where('phases.data.0.project.name', $expectedProjectName)
            );

        $this->actingAs($demoUser)
            ->get('/cost-tracking')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('CostTracking/Index')
                ->where('summary.projects_count', 1)
                ->where('projects.0.project_name', $expectedProjectName)
            );

        $this->actingAs($demoUser)
            ->get('/exports')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Exports/Index')
                ->where('projects', fn ($projects) => $projects->count() === 1 && $projects->first()['name'] === $expectedProjectName)
            );

        $this->actingAs($demoUser)
            ->get('/stage-progress')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('StageProgress/Index')
                ->where('summary.phases_count', 1)
                ->where('projects', fn ($projects) => count($projects) === 1 && $projects[0]['name'] === $expectedProjectName)
                ->where('phases.data.0.project.name', $expectedProjectName)
            );

        $this->actingAs($demoUser)
            ->get('/team-calendar?start_date=2026-07-01&end_date=2026-07-31')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('TeamCalendar/Index')
                ->where('summary.total_assignments', 1)
                ->where('teams', fn ($teams) => count($teams) === 1 && $teams[0]['name'] === 'Echipa Demo')
            );

        $this->actingAs($demoUser)
            ->get('/equipment-calendar?start_date=2026-07-01&end_date=2026-07-31')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('EquipmentCalendar/Index')
                ->where('summary.total_reservations', 1)
                ->where('equipment', fn ($equipment) => count($equipment) === 1 && $equipment[0]['name'] === 'Utilaj Demo')
            );

        $this->actingAs($demoUser)
            ->get('/documents')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Documents/Index')
                ->where('documents.data.0.title', $expectedDocumentTitle)
                ->where('projects', fn ($projects) => count($projects) === 1 && $projects[0]['name'] === $expectedProjectName)
            );

        $this->actingAs($demoUser)
            ->get('/material-invoices')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MaterialInvoices/Index')
                ->where('invoices.data.0.invoice_no', $expectedInvoiceNo)
                ->where('projects', fn ($projects) => count($projects) === 1 && $projects[0]['name'] === $expectedProjectName)
            );
    }
}