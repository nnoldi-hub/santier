<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Defect;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\StageReport;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Tenant::create([
            'id' => 2,
            'name' => 'Tenant intrus',
            'slug' => 'tenant-intrus',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);
    }

    private function actorForTenant(int $tenantId): User
    {
        return User::factory()->create([
            'tenant_id' => $tenantId,
            'current_tenant_id' => $tenantId,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function projectForTenant(int $tenantId): Project
    {
        $client = Client::create([
            'tenant_id' => $tenantId,
            'name' => "Client tenant {$tenantId}",
            'type' => 'company',
            'active' => true,
        ]);

        $owner = User::factory()->create(['tenant_id' => $tenantId, 'current_tenant_id' => $tenantId]);

        return Project::create([
            'tenant_id' => $tenantId,
            'client_id' => $client->id,
            'created_by' => $owner->id,
            'name' => "Proiect tenant {$tenantId}",
            'status' => 'active',
        ]);
    }

    public function test_cross_tenant_user_cannot_edit_or_update_task(): void
    {
        $project = $this->projectForTenant(1);
        $task = Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'created_by' => $project->created_by,
            'title' => 'Task tenant 1',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/tasks/{$task->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/tasks/{$task->id}", ['title' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/tasks/{$task->id}")->assertForbidden();
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Task tenant 1']);
    }

    public function test_cross_tenant_user_cannot_edit_or_update_client(): void
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client tenant 1',
            'type' => 'company',
            'active' => true,
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/clients/{$client->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/clients/{$client->id}", ['name' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/clients/{$client->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_team(): void
    {
        $team = Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa tenant 1',
            'active' => true,
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/teams/{$team->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/teams/{$team->id}", ['name' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/teams/{$team->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_contractor(): void
    {
        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor tenant 1',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'active' => true,
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/contractors/{$contractor->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/contractors/{$contractor->id}", ['name' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/contractors/{$contractor->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_material(): void
    {
        $material = Material::create([
            'tenant_id' => 1,
            'name' => 'Material tenant 1',
            'unit' => 'buc',
            'active' => true,
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/materials/{$material->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/materials/{$material->id}", ['name' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/materials/{$material->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_defect(): void
    {
        $project = $this->projectForTenant(1);
        $defect = Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'reported_by' => $project->created_by,
            'title' => 'Defect tenant 1',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/defects/{$defect->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/defects/{$defect->id}", ['title' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->patch("/defects/{$defect->id}/status", ['status' => 'resolved'])->assertForbidden();
        $this->actingAs($intruder)->delete("/defects/{$defect->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_equipment(): void
    {
        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Utilaj tenant 1',
            'active' => true,
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/equipment/{$equipment->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/equipment/{$equipment->id}", ['name' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/equipment/{$equipment->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_quality_check(): void
    {
        $project = $this->projectForTenant(1);
        $qualityCheck = QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'title' => 'Verificare tenant 1',
            'check_type' => 'execution',
            'status' => 'pending',
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/quality-checks/{$qualityCheck->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/quality-checks/{$qualityCheck->id}", ['title' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->patch("/quality-checks/{$qualityCheck->id}/status", ['status' => 'passed'])->assertForbidden();
        $this->actingAs($intruder)->delete("/quality-checks/{$qualityCheck->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_stage_report(): void
    {
        $project = $this->projectForTenant(1);
        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa tenant 1',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 10,
        ]);
        $stageReport = StageReport::create([
            'stage_id' => $phase->id,
            'report_date' => now()->toDateString(),
            'created_by' => $project->created_by,
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/stage-reports/{$stageReport->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/stage-reports/{$stageReport->id}", ['report_date' => now()->toDateString()])->assertForbidden();
        $this->actingAs($intruder)->delete("/stage-reports/{$stageReport->id}")->assertForbidden();
    }

    public function test_cross_tenant_user_cannot_edit_or_update_stage_task(): void
    {
        $project = $this->projectForTenant(1);
        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa tenant 1',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 10,
        ]);
        $stageTask = StageTask::create([
            'stage_id' => $phase->id,
            'title' => 'Task etapa tenant 1',
            'status' => 'todo',
        ]);

        $intruder = $this->actorForTenant(2);

        $this->actingAs($intruder)->get("/stage-tasks/{$stageTask->id}/edit")->assertForbidden();
        $this->actingAs($intruder)->put("/stage-tasks/{$stageTask->id}", ['title' => 'hacked'])->assertForbidden();
        $this->actingAs($intruder)->delete("/stage-tasks/{$stageTask->id}")->assertForbidden();
    }
}
