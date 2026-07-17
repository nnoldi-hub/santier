<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceOrder;
use App\Models\SiteCompliancePlan;
use App\Models\StageEquipment;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Support\DailyBriefingBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyBriefingBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_aggregates_all_sections_and_blockers_for_today(): void
    {
        $user = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);
        $project = $this->createProject($user);
        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Structura',
            'type' => 'structura',
            'order' => 1,
            'status' => 'in_progress',
        ]);

        $team = Team::create(['tenant_id' => 1, 'name' => 'Echipa Fier', 'active' => true]);
        PhaseTeamAssignment::create([
            'phase_id' => $phase->id,
            'team_id' => $team->id,
            'workers_needed' => 5,
            'workers_assigned' => 2,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $blockedContractor = Contractor::create(['tenant_id' => 1, 'name' => 'Sub Blocat SRL', 'type' => 'subcontractor', 'active' => true]);
        $blockedPhase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Instalatii',
            'type' => 'instalatii_brute',
            'order' => 2,
            'status' => 'blocked',
            'contractor_id' => $blockedContractor->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $material = Material::create(['tenant_id' => 1, 'name' => 'Beton C25/30', 'unit' => 'mc']);
        ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Holcim',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'delivery_date' => now()->toDateString(),
            'status' => 'blocked_payment',
        ]);

        $equipment = Equipment::create(['tenant_id' => 1, 'name' => 'Pompa beton', 'type' => 'custom', 'availability_status' => 'reserved', 'active' => true]);
        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => now()->toDateString(),
            'usage_end' => now()->toDateString(),
        ]);

        SiteCompliancePlan::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'item_type' => 'autorizatie',
            'title' => 'Autorizatie constructie',
            'status' => 'expired',
            'due_date' => now()->toDateString(),
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Verifica santierul',
            'status' => 'todo',
            'priority' => 'high',
            'deadline' => now(),
        ]);

        StageTask::create([
            'stage_id' => $blockedPhase->id,
            'title' => 'Racordare electrica',
            'status' => 'blocked',
            'deadline' => now(),
        ]);

        $briefing = DailyBriefingBuilder::build($project);

        $this->assertCount(1, $briefing['teams']);
        $this->assertSame('risc', $briefing['teams'][0]['confirmation_status']);

        $this->assertCount(1, $briefing['subcontractors']);
        $this->assertSame('risc', $briefing['subcontractors'][0]['confirmation_status']);

        $this->assertCount(1, $briefing['materials']);
        $this->assertSame('risc', $briefing['materials'][0]['confirmation_status']);

        $this->assertCount(1, $briefing['equipment']);
        $this->assertCount(1, $briefing['documents']);
        $this->assertSame('expired', $briefing['documents'][0]['status']);

        $this->assertCount(2, $briefing['tasks']);

        $this->assertCount(5, $briefing['blockers']);
        $this->assertNotEmpty($briefing['recommendations']);
    }

    public function test_build_returns_empty_sections_when_nothing_is_scheduled_today(): void
    {
        $user = User::factory()->create(['tenant_id' => 1, 'current_tenant_id' => 1]);
        $project = $this->createProject($user);

        $briefing = DailyBriefingBuilder::build($project);

        $this->assertSame([], $briefing['teams']);
        $this->assertSame([], $briefing['subcontractors']);
        $this->assertSame([], $briefing['materials']);
        $this->assertSame([], $briefing['equipment']);
        $this->assertSame([], $briefing['documents']);
        $this->assertSame([], $briefing['tasks']);
        $this->assertSame([], $briefing['blockers']);
    }

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Memento SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Memento',
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
        ]);
    }
}
