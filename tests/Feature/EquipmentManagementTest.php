<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EquipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_can_be_created_from_form(): void
    {
        $user = $this->createOnboardedUser();

        $response = $this->actingAs($user)
            ->from('/equipment/create')
            ->post('/equipment', [
                'name' => 'Excavator CAT 320',
                'type' => 'excavator',
                'supplier_name' => 'Utilaje Vest',
                'cost_per_hour' => 230,
                'availability_status' => Equipment::STATUS_AVAILABLE,
                'notes' => 'Model nou',
                'active' => true,
            ]);

        $response->assertRedirect('/equipment');

        $this->assertDatabaseHas('equipment', [
            'tenant_id' => 1,
            'name' => 'Excavator CAT 320',
            'type' => 'excavator',
            'supplier_name' => 'Utilaje Vest',
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);
    }

    public function test_phase_can_store_equipment_reservation(): void
    {
        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Proiect utilaje',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Sapaturi',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Miniexcavator',
            'type' => 'excavator',
            'supplier_name' => 'Furnizor',
            'cost_per_hour' => 120,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->from('/projects/' . $project->id)
            ->post('/projects/' . $project->id . '/phases/' . $phase->id . '/equipment', [
                'equipment_id' => $equipment->id,
                'quantity' => 2,
                'usage_start' => '2026-07-05',
                'usage_end' => '2026-07-07',
            ]);

        $response->assertRedirect('/projects/' . $project->id);

        $this->assertDatabaseHas('stage_equipment', [
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 2,
        ]);
    }

    public function test_overlap_reservation_still_saves_but_adds_warning_message(): void
    {
        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Proiect overlap',
            'status' => 'active',
        ]);

        $phaseOne = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa 1',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $phaseTwo = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa 2',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Macara telescopica',
            'type' => 'crane',
            'supplier_name' => 'Furnizor',
            'cost_per_hour' => 180,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);

        $this->actingAs($user)->post('/projects/' . $project->id . '/phases/' . $phaseOne->id . '/equipment', [
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => '2026-07-10',
            'usage_end' => '2026-07-12',
        ]);

        $response = $this->actingAs($user)
            ->from('/projects/' . $project->id)
            ->post('/projects/' . $project->id . '/phases/' . $phaseTwo->id . '/equipment', [
                'equipment_id' => $equipment->id,
                'quantity' => 1,
                'usage_start' => '2026-07-11',
                'usage_end' => '2026-07-13',
            ]);

        $response->assertRedirect('/projects/' . $project->id);
        $response->assertSessionHas('success');
        $this->assertStringContainsString('Atentie', (string) session('success'));

        $this->assertDatabaseCount('stage_equipment', 2);
    }

    public function test_overlap_reservation_is_blocked_when_strict_mode_is_enabled(): void
    {
        Config::set('equipment.strict_conflict_block', true);

        $user = $this->createOnboardedUser();

        $project = Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Proiect strict conflict',
            'status' => 'active',
        ]);

        $phaseOne = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa A',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $phaseTwo = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa B',
            'type' => 'custom',
            'status' => 'pending',
            'progress_pct' => 0,
        ]);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Compactor',
            'type' => 'custom',
            'supplier_name' => 'Furnizor',
            'cost_per_hour' => 90,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);

        $this->actingAs($user)->post('/projects/' . $project->id . '/phases/' . $phaseOne->id . '/equipment', [
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => '2026-07-15',
            'usage_end' => '2026-07-16',
        ]);

        $response = $this->actingAs($user)
            ->from('/projects/' . $project->id)
            ->post('/projects/' . $project->id . '/phases/' . $phaseTwo->id . '/equipment', [
                'equipment_id' => $equipment->id,
                'quantity' => 1,
                'usage_start' => '2026-07-16',
                'usage_end' => '2026-07-17',
            ]);

        $response->assertRedirect('/projects/' . $project->id);
        $response->assertSessionHasErrors('equipment_id');
        $response->assertSessionHas('error');

        $this->assertDatabaseCount('stage_equipment', 1);
    }

    private function createOnboardedUser(): User
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $this->seed(IamSeeder::class);

        return $user->fresh();
    }
}
