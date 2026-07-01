<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageEquipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EquipmentCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_calendar_filters_reservations_by_interval(): void
    {
        $user = $this->createOnboardedUser();
        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Mini excavator',
            'category' => 'Excavator',
            'availability_status' => 'available',
            'cost_per_hour' => 120,
            'daily_rate' => 900,
            'active' => true,
        ]);

        [, $phase] = $this->seedProjectPhase($user);

        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 2,
            'usage_start' => '2026-07-08',
            'usage_end' => '2026-07-12',
            'notes' => 'Rezervare principala',
        ]);

        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => '2026-08-01',
            'usage_end' => '2026-08-05',
            'notes' => 'Rezervare in afara intervalului',
        ]);

        $response = $this->actingAs($user)->get('/equipment-calendar?start_date=2026-07-01&end_date=2026-07-31');
        $expectedEquipmentName = 'Mini excavator';

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($expectedEquipmentName): void {
            $page->component('EquipmentCalendar/Index')
                ->where('reservations.0.equipment.name', $expectedEquipmentName)
                ->where('summary.total_reservations', 1)
                ->where('summary.equipment_involved', 1)
                ->where('summary.units_reserved', 2)
                ->where('summary.estimated_cost', 240);
        });
    }

    private function seedProjectPhase(User $user): array
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
            'name' => 'Etapa Utilaje',
            'type' => 'custom',
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
