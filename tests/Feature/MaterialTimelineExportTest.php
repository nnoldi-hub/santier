<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Document;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceDelivery;
use App\Models\ResourceDocumentLink;
use App\Models\ResourceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialTimelineExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_returns_chronological_events_with_actors(): void
    {
        $user = $this->createOnboardedUser();

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Timeline SRL',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Timeline',
            'status' => 'active',
            'total_budget' => 10000,
            'start_date' => now()->toDateString(),
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Timeline',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Executie',
            'type' => 'custom',
            'order' => 1,
            'status' => 'in_progress',
            'progress_pct' => 20,
            'contractor_id' => $contractor->id,
        ]);

        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-TL1',
            'name' => 'Ciment Timeline',
            'category' => 'Constructii',
            'unit' => 'sac',
            'unit_price' => 30,
            'active' => true,
        ]);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Furnizor Timeline SRL',
            'carrier_name' => 'Transport Timeline SRL',
            'ordered_quantity' => 50,
            'ordered_unit' => 'sac',
            'unit_price' => 30,
            'delivery_date' => now()->subDays(3)->toDateString(),
            'responsible_user_id' => $user->id,
            'status' => 'delivered',
        ]);

        ResourceDelivery::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'declared_quantity' => 50,
            'received_quantity' => 48,
            'consumed_quantity' => 40,
            'returned_quantity' => 8,
            'delivered_at' => now()->subDays(2),
        ]);

        $document = Document::create([
            'tenant_id' => 1,
            'title' => 'Aviz livrare timeline',
            'type' => 'delivery_note',
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'contractor_id' => $contractor->id,
            'amount' => 0,
            'issued_at' => now()->subDay()->toDateString(),
            'payment_status' => 'unpaid',
            'file_name' => 'aviz-timeline.pdf',
        ]);

        ResourceDocumentLink::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'document_id' => $document->id,
            'document_role' => 'delivery_note',
            'document_number' => 'AVZ-TL-001',
            'supplier_name' => 'Furnizor Timeline SRL',
            'declared_quantity' => 50,
            'delivered_quantity' => 48,
            'difference_quantity' => 2,
        ]);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=material-timeline');

        $response->assertOk();
        $response->assertJsonFragment(['export_type' => 'material-timeline']);
        $this->assertSame(3, $response->json('rows_count'));

        $charts = $response->json('charts');
        $this->assertCount(1, $charts);
        $this->assertSame('material-timeline_event_type', $charts[0]['key']);
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
