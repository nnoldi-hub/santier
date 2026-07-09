<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceConfirmation;
use App\Models\ResourceDocumentLink;
use App\Models\ResourceOrder;
use App\Models\Task;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ResourceOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_orders_index_renders_inertia_page(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'status' => 'ordered',
        ]);

        $response = $this->actingAs($user)->get('/resource-orders');

        $expectedType = 'material';
        $expectedMaterialName = 'Beton C25/30';
        $expectedStatus = 'ordered';

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($expectedType, $expectedMaterialName, $expectedStatus): void {
            $page->component('ResourceOrders/Index')
            ->where('orders.data.0.resource_type', $expectedType)
            ->where('orders.data.0.material.name', $expectedMaterialName)
            ->where('orders.data.0.status', $expectedStatus);
        });
    }

    public function test_resource_order_show_renders_timeline_and_confirmations(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'status' => 'ordered',
            'responsible_user_id' => $user->id,
        ]);

        $document = Document::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'type' => 'delivery_note',
            'amount' => 0,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'paid',
            'title' => 'Aviz livrare',
        ]);

        ResourceDocumentLink::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'document_id' => $document->id,
            'document_role' => 'delivery_note',
            'declared_quantity' => 10,
            'delivered_quantity' => 9,
            'difference_quantity' => 1,
        ]);

        ResourceConfirmation::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'confirmation_role' => 'site_manager',
            'confirmed_by' => $user->id,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/resource-orders/' . $order->id);

        $expectedLinkedRole = 'delivery_note';
        $expectedConfirmationRole = 'site_manager';

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ResourceOrders/Show')
            ->where('order.id', $order->id)
            ->where('linkedDocuments.0.role', $expectedLinkedRole)
            ->where('confirmations.0.role', $expectedConfirmationRole)
        );
    }

    public function test_material_resource_order_can_be_created(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $response = $this->actingAs($user)->post('/resource-orders', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'carrier_name' => 'Transport local',
            'equipment_name' => 'Pompa 42m',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'delivery_date' => '2026-07-09',
            'responsible_user_id' => $user->id,
            'status' => 'ordered',
            'notes' => 'Prima turnare',
        ]);

        $response->assertRedirect('/resource-orders');

        $this->assertDatabaseHas('resource_orders', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_unit' => 'mc',
            'status' => 'ordered',
        ]);
    }

    public function test_resource_order_can_create_linked_documents(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $response = $this->actingAs($user)->post('/resource-orders', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'carrier_name' => 'Transport local',
            'equipment_name' => 'Pompa 42m',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'delivery_date' => '2026-07-09',
            'responsible_user_id' => $user->id,
            'status' => 'ordered',
            'documents' => [
                [
                    'title' => 'Aviz pompa 8.5 mc',
                    'type' => 'pump_note',
                    'document_number' => 'PMP-001',
                    'declared_quantity' => 10,
                    'delivered_quantity' => 8.5,
                    'notes' => 'Cantitate confirmata in santier',
                    'attachment' => UploadedFile::fake()->create('aviz-pompa.pdf', 120, 'application/pdf'),
                ],
            ],
        ]);

        $response->assertRedirect('/resource-orders');

        $order = ResourceOrder::query()->latest('id')->first();
        $document = Document::query()->latest('id')->first();
        $link = ResourceDocumentLink::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertNotNull($document);
        $this->assertNotNull($link);

        $this->assertSame('pump_note', $document->type);
        $this->assertSame('PMP-001', $document->invoice_number);
        $this->assertSame($order->id, $link->resource_order_id);
        $this->assertSame($document->id, $link->document_id);
        $this->assertEquals('1.50', number_format((float) $link->difference_quantity, 2, '.', ''));
        $this->assertSame('blocked_payment', (string) $order->status);
        Storage::disk('local')->assertExists($document->file_path);

        $task = Task::query()->latest('id')->first();
        $this->assertNotNull($task);
        $this->assertSame('high', $task->priority);
        $this->assertStringContainsString('diferenta de cantitate', mb_strtolower($task->title));

        $notification = $user->notifications()->latest()->first();
        $this->assertNotNull($notification);
        $this->assertSame(OperationalReminderNotification::class, $notification->type);
        $this->assertSame('resource_discrepancy', (string) ($notification->data['event'] ?? null));
    }

    public function test_small_difference_stays_unblocked_and_does_not_create_task(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $response = $this->actingAs($user)->post('/resource-orders', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'delivery_date' => '2026-07-09',
            'responsible_user_id' => $user->id,
            'status' => 'ordered',
            'documents' => [
                [
                    'title' => 'Aviz livrare 9.9 mc',
                    'type' => 'delivery_note',
                    'document_number' => 'DLV-001',
                    'declared_quantity' => 10,
                    'delivered_quantity' => 9.9,
                    'attachment' => UploadedFile::fake()->create('aviz-livrare.pdf', 80, 'application/pdf'),
                ],
            ],
        ]);

        $response->assertRedirect('/resource-orders');

        $order = ResourceOrder::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('ordered', (string) $order->status);

        $taskCount = Task::query()->where('project_id', $project->id)->count();
        $this->assertSame(0, $taskCount);
    }

    public function test_resource_order_confirmation_endpoint_updates_role_status(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 5,
            'ordered_unit' => 'mc',
            'status' => 'ordered',
        ]);

        $response = $this->actingAs($user)->patch('/resource-orders/' . $order->id . '/confirmations', [
            'confirmation_role' => 'quality_manager',
            'status' => 'confirmed',
            'notes' => 'Conform pe receptie',
        ]);

        $response->assertRedirect('/resource-orders/' . $order->id);

        $this->assertDatabaseHas('resource_confirmations', [
            'resource_order_id' => $order->id,
            'confirmation_role' => 'quality_manager',
            'status' => 'confirmed',
            'notes' => 'Conform pe receptie',
        ]);

        $order->refresh();
        $this->assertSame('verified', (string) $order->status);
    }

    public function test_confirmation_lifecycle_transitions_to_financial_review_and_approved(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 5,
            'ordered_unit' => 'mc',
            'status' => 'ordered',
        ]);

        $this->actingAs($user)->patch('/resource-orders/' . $order->id . '/confirmations', [
            'confirmation_role' => 'site_manager',
            'status' => 'confirmed',
        ])->assertRedirect('/resource-orders/' . $order->id);

        $order->refresh();
        $this->assertSame('verified', (string) $order->status);

        $this->actingAs($user)->patch('/resource-orders/' . $order->id . '/confirmations', [
            'confirmation_role' => 'execution_manager',
            'status' => 'confirmed',
        ])->assertRedirect('/resource-orders/' . $order->id);

        $this->actingAs($user)->patch('/resource-orders/' . $order->id . '/confirmations', [
            'confirmation_role' => 'quality_manager',
            'status' => 'confirmed',
        ])->assertRedirect('/resource-orders/' . $order->id);

        $order->refresh();
        $this->assertSame('financial_review', (string) $order->status);

        $this->actingAs($user)->patch('/resource-orders/' . $order->id . '/confirmations', [
            'confirmation_role' => 'financial_manager',
            'status' => 'confirmed',
        ])->assertRedirect('/resource-orders/' . $order->id);

        $order->refresh();
        $this->assertSame('approved', (string) $order->status);
    }

    public function test_blocked_payment_status_has_precedence_over_confirmation_flow(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $this->actingAs($user)->post('/resource-orders', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'delivery_date' => '2026-07-09',
            'responsible_user_id' => $user->id,
            'status' => 'ordered',
            'documents' => [
                [
                    'title' => 'Aviz pompa 8.5 mc',
                    'type' => 'pump_note',
                    'document_number' => 'PMP-010',
                    'declared_quantity' => 10,
                    'delivered_quantity' => 8.5,
                    'attachment' => UploadedFile::fake()->create('aviz-pompa-10.pdf', 120, 'application/pdf'),
                ],
            ],
        ])->assertRedirect('/resource-orders');

        $order = ResourceOrder::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('blocked_payment', (string) $order->status);

        $this->actingAs($user)->patch('/resource-orders/' . $order->id . '/confirmations', [
            'confirmation_role' => 'site_manager',
            'status' => 'confirmed',
        ])->assertRedirect('/resource-orders/' . $order->id);

        $order->refresh();
        $this->assertSame('blocked_payment', (string) $order->status);
    }

    public function test_document_can_be_attached_from_resource_order_detail_page(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'status' => 'ordered',
            'responsible_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post('/resource-orders/' . $order->id . '/documents', [
            'title' => 'Factura beton iulie',
            'type' => 'resource_invoice',
            'document_number' => 'INV-100',
            'declared_quantity' => 10,
            'delivered_quantity' => 9,
            'attachment' => UploadedFile::fake()->create('factura-beton.pdf', 120, 'application/pdf'),
            'notes' => 'Factura pentru turnare placa',
        ]);

        $response->assertRedirect('/resource-orders/' . $order->id);

        $this->assertDatabaseHas('resource_document_links', [
            'resource_order_id' => $order->id,
            'document_number' => 'INV-100',
            'document_role' => 'resource_invoice',
        ]);

        $order->refresh();
        $this->assertSame('blocked_payment', (string) $order->status);
    }

    public function test_linked_document_can_be_deleted_from_resource_order_detail_page(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'status' => 'ordered',
            'responsible_user_id' => $user->id,
        ]);

        $document = Document::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'type' => 'delivery_note',
            'amount' => 0,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'paid',
            'title' => 'Aviz livrare',
            'file_path' => UploadedFile::fake()->create('aviz.pdf', 80, 'application/pdf')->store('documents', 'local'),
            'file_name' => 'aviz.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 80 * 1024,
        ]);

        $link = ResourceDocumentLink::create([
            'tenant_id' => 1,
            'resource_order_id' => $order->id,
            'document_id' => $document->id,
            'document_role' => 'delivery_note',
            'declared_quantity' => 10,
            'delivered_quantity' => 8,
            'difference_quantity' => 2,
        ]);

        $this->actingAs($user)->delete('/resource-orders/' . $order->id . '/documents/' . $link->id)
            ->assertRedirect('/resource-orders/' . $order->id);

        $this->assertSoftDeleted('resource_document_links', ['id' => $link->id]);
        $this->assertSoftDeleted('documents', ['id' => $document->id]);

        $order->refresh();
        $this->assertSame('ordered', (string) $order->status);
    }

    public function test_resource_order_rejects_phase_that_does_not_belong_to_project(): void
    {
        $user = $this->createOnboardedUser();
        [$projectA, $phaseA, $material] = $this->seedContext($user);

        $clientB = Client::create([
            'tenant_id' => 1,
            'name' => 'Client B',
            'type' => 'company',
            'active' => true,
        ]);

        $projectB = Project::create([
            'tenant_id' => 1,
            'client_id' => $clientB->id,
            'created_by' => $user->id,
            'name' => 'Proiect B',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->from('/resource-orders/create')
            ->post('/resource-orders', [
                'project_id' => $projectB->id,
                'phase_id' => $phaseA->id,
                'resource_type' => 'material',
                'material_id' => $material->id,
                'ordered_quantity' => 10,
                'ordered_unit' => 'mc',
                'status' => 'ordered',
            ]);

        $response->assertRedirect('/resource-orders/create');
        $response->assertSessionHasErrors('phase_id');
    }

    public function test_resource_order_can_be_deleted_from_index_list(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $order = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'status' => 'ordered',
        ]);

        $this->actingAs($user)
            ->delete('/resource-orders/' . $order->id)
            ->assertRedirect('/resource-orders');

        $this->assertSoftDeleted('resource_orders', ['id' => $order->id]);
    }

    private function seedContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Resurse',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Resurse',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Turnare fundatie',
            'type' => 'structura',
            'status' => 'in_progress',
            'progress_pct' => 15,
        ]);

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

        Equipment::create([
            'tenant_id' => 1,
            'name' => 'Pompa 42m',
            'type' => 'custom',
            'supplier_name' => 'Utilaje SRL',
            'cost_per_hour' => 300,
            'availability_status' => 'available',
            'active' => true,
        ]);

        return [$project, $phase, $material];
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