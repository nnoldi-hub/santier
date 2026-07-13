<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceDelivery;
use App\Models\ResourceOrder;
use App\Models\StageEquipment;
use App\Models\User;
use App\Support\EquipmentCostEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentConsumptionExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_correlates_equipment_reservation_with_phase_material_consumption(): void
    {
        $user = $this->createOnboardedUser();

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Consum SRL',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Consum',
            'status' => 'active',
            'total_budget' => 20000,
            'start_date' => now()->toDateString(),
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Consum',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Turnare',
            'type' => 'custom',
            'order' => 1,
            'status' => 'in_progress',
            'progress_pct' => 30,
            'contractor_id' => $contractor->id,
        ]);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Pompa beton test',
            'type' => 'pump',
            'supplier_name' => 'Utilaje Consum SRL',
            'cost_per_hour' => 180,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);

        $reservation = StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => now()->addDay()->toDateString(),
            'usage_end' => now()->addDays(2)->toDateString(),
        ]);

        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-CONS1',
            'name' => 'Beton Consum',
            'category' => 'Constructii',
            'unit' => 'mc',
            'unit_price' => 300,
            'active' => true,
        ]);

        $materialOrder = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Furnizor Beton SRL',
            'ordered_quantity' => 30,
            'ordered_unit' => 'mc',
            'unit_price' => 300,
            'delivery_date' => now()->toDateString(),
            'responsible_user_id' => $user->id,
            'status' => 'delivered',
        ]);

        ResourceDelivery::create([
            'tenant_id' => 1,
            'resource_order_id' => $materialOrder->id,
            'declared_quantity' => 30,
            'received_quantity' => 30,
            'consumed_quantity' => 25,
            'returned_quantity' => 5,
            'delivered_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=equipment-consumption');

        $response->assertOk();
        $response->assertJsonFragment(['export_type' => 'equipment-consumption']);
        $this->assertSame(1, $response->json('rows_count'));

        $sample = $response->json('sample.0');
        $this->assertEquals(EquipmentCostEstimator::estimate($reservation->fresh(['equipment'])), $sample['estimated_cost']);
        $this->assertEquals(25, $sample['phase_material_consumed_quantity']);
        $this->assertSame(1, $sample['phase_material_orders_count']);
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
