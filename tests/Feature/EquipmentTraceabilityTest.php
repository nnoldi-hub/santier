<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageEquipment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EquipmentTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_traceability_shows_aggregated_reservations(): void
    {
        $user = $this->createOnboardedUser();
        [, $phase] = $this->seedContext($user);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Macara turn',
            'type' => 'crane',
            'cost_per_hour' => 100,
            'availability_status' => 'available',
            'active' => true,
        ]);

        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 2,
            'usage_start' => '2026-07-10',
            'usage_end' => '2026-07-12',
        ]);

        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => '2026-07-15',
            'usage_end' => '2026-07-15',
        ]);

        $response = $this->actingAs($user)->get('/trasabilitate-utilaje');

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('EquipmentTraceability/Index')
                ->where('summary.equipment_tracked', 1)
                ->where('equipment.data.0.reservations_count', 2)
                ->where('equipment.data.0.total_reserved_days', 4)
                // 100 cost/h * 2 qty * 3 days * 8h + 100 cost/h * 1 qty * 1 day * 8h
                ->where('equipment.data.0.total_estimated_cost', 5600.0)
                ->where('summary.total_estimated_cost', 5600.0);
        });
    }

    public function test_equipment_traceability_counts_active_reservation_today(): void
    {
        $user = $this->createOnboardedUser();
        [, $phase] = $this->seedContext($user);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Excavator',
            'type' => 'excavator',
            'cost_per_hour' => 50,
            'availability_status' => 'reserved',
            'active' => true,
        ]);

        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => now()->subDay()->toDateString(),
            'usage_end' => now()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get('/trasabilitate-utilaje');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('EquipmentTraceability/Index')
            ->where('summary.active_today_count', 1)
            ->where('summary.unavailable_count', 1)
            ->where('equipment.data.0.active_reservations_count', 1));
    }

    public function test_equipment_traceability_does_not_leak_across_tenants(): void
    {
        Tenant::create([
            'id' => 2,
            'name' => 'Tenant intrus',
            'slug' => 'tenant-intrus',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);

        $user = $this->createOnboardedUser();
        [, $phase] = $this->seedContext($user);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Buldozer propriu',
            'type' => 'bulldozer',
            'cost_per_hour' => 80,
            'availability_status' => 'available',
            'active' => true,
        ]);

        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => '2026-07-10',
            'usage_end' => '2026-07-10',
        ]);

        $otherOwner = User::factory()->create(['tenant_id' => 2, 'current_tenant_id' => 2]);
        $otherClient = Client::create([
            'tenant_id' => 2,
            'name' => 'Client tenant 2',
            'type' => 'company',
            'active' => true,
        ]);
        $otherProject = Project::create([
            'tenant_id' => 2,
            'client_id' => $otherClient->id,
            'created_by' => $otherOwner->id,
            'name' => 'Proiect tenant 2',
            'status' => 'active',
        ]);
        $otherPhase = ProjectPhase::create([
            'project_id' => $otherProject->id,
            'name' => 'Etapa tenant 2',
            'type' => 'structura',
            'status' => 'in_progress',
            'progress_pct' => 0,
        ]);
        $otherEquipment = Equipment::create([
            'tenant_id' => 2,
            'name' => 'Utilaj tenant 2',
            'type' => 'custom',
            'cost_per_hour' => 999,
            'availability_status' => 'available',
            'active' => true,
        ]);
        StageEquipment::create([
            'stage_id' => $otherPhase->id,
            'equipment_id' => $otherEquipment->id,
            'quantity' => 1,
            'usage_start' => '2026-07-10',
            'usage_end' => '2026-07-10',
        ]);

        $response = $this->actingAs($user)->get('/trasabilitate-utilaje');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('EquipmentTraceability/Index')
            ->where('summary.equipment_tracked', 1)
            ->where('equipment.data.0.name', 'Buldozer propriu'));
    }

    private function seedContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Utilaje',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Utilaje',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Turnare fundatie',
            'type' => 'structura',
            'status' => 'in_progress',
            'progress_pct' => 15,
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
