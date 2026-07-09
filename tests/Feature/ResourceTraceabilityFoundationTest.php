<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceConfirmation;
use App\Models\ResourceDelivery;
use App\Models\ResourceDocumentLink;
use App\Models\ResourceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceTraceabilityFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_note_document_type_can_be_stored_in_existing_registry(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/documents', [
            'title' => 'Aviz beton B250',
            'type' => 'delivery_note',
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'amount' => 0,
            'issued_at' => '2026-07-09',
            'payment_status' => 'unpaid',
            'notes' => 'Aviz pentru turnare placa.',
        ]);

        $response->assertRedirect('/documents');

        $this->assertDatabaseHas('documents', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'type' => 'delivery_note',
            'title' => 'Aviz beton B250',
        ]);
    }

    public function test_resource_order_relations_capture_delivery_confirmation_and_document_link(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);
        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'BET-250',
            'name' => 'Beton C25/30',
            'category' => 'Structura',
            'unit' => 'mc',
            'unit_price' => 500,
            'supplier' => 'Statie beton',
            'active' => true,
        ]);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'carrier_name' => 'Transport local',
            'equipment_name' => 'Pompa Putzmeister',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'delivery_date' => '2026-07-09',
            'responsible_user_id' => $user->id,
            'status' => 'ordered',
        ]);

        $document = Document::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'type' => 'pump_note',
            'amount' => 0,
            'issued_at' => '2026-07-09',
            'payment_status' => 'unpaid',
            'title' => 'Aviz pompa 8.5 mc',
        ]);

        ResourceDelivery::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'declared_quantity' => 10,
            'received_quantity' => 8.5,
            'equipment_reported_quantity' => 8.5,
            'consumed_quantity' => 8.5,
            'delivered_at' => '2026-07-09 10:30:00',
        ]);

        ResourceConfirmation::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'confirmation_role' => 'site_manager',
            'confirmed_by' => $user->id,
            'status' => 'confirmed',
            'confirmed_at' => '2026-07-09 11:00:00',
        ]);

        ResourceDocumentLink::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'document_id' => $document->id,
            'document_role' => 'pump_note',
            'document_number' => 'PMP-001',
            'supplier_name' => 'Statie beton',
            'carrier_name' => 'Transport local',
            'equipment_name' => 'Pompa Putzmeister',
            'declared_quantity' => 10,
            'delivered_quantity' => 8.5,
            'difference_quantity' => 1.5,
        ]);

        $order->load(['deliveries', 'confirmations', 'documentLinks.document']);

        $this->assertCount(1, $order->deliveries);
        $this->assertSame('site_manager', $order->confirmations->first()?->confirmation_role);
        $this->assertSame('pump_note', $order->documentLinks->first()?->document?->type);
        $this->assertEquals('1.50', number_format((float) $order->documentLinks->first()?->difference_quantity, 2, '.', ''));
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Trasabilitate',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Trasabilitate',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Turnare placa',
            'type' => 'structura',
            'status' => 'in_progress',
            'progress_pct' => 20,
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
