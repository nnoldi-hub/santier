<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ResourceOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierCatalogLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_picks_up_supplier_name_from_catalog(): void
    {
        $user = $this->createOnboardedUser();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Dedeman SRL', 'active' => true]);

        $this->actingAs($user)->post('/materials', [
            'name' => 'Ciment',
            'unit' => 'sac',
            'unit_price' => 25,
            'active' => true,
            'supplier_id' => $supplier->id,
        ]);

        $this->assertDatabaseHas('materials', [
            'name' => 'Ciment',
            'supplier_id' => $supplier->id,
            'supplier' => 'Dedeman SRL',
        ]);
    }

    public function test_material_supplier_name_is_frozen_after_supplier_is_renamed(): void
    {
        $user = $this->createOnboardedUser();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Dedeman SRL', 'active' => true]);

        $this->actingAs($user)->post('/materials', [
            'name' => 'Ciment',
            'unit' => 'sac',
            'unit_price' => 25,
            'active' => true,
            'supplier_id' => $supplier->id,
        ]);

        $supplier->update(['name' => 'Dedeman Nume Nou SRL']);

        $material = Material::where('name', 'Ciment')->first();
        $this->assertSame('Dedeman SRL', $material->supplier);
    }

    public function test_equipment_picks_up_supplier_name_from_catalog(): void
    {
        $user = $this->createOnboardedUser();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Utilaje Grele SRL', 'active' => true]);

        $this->actingAs($user)->post('/equipment', [
            'name' => 'Excavator CAT 320',
            'type' => 'excavator',
            'cost_per_hour' => 150,
            'availability_status' => 'available',
            'active' => true,
            'supplier_id' => $supplier->id,
        ]);

        $this->assertDatabaseHas('equipment', [
            'name' => 'Excavator CAT 320',
            'supplier_id' => $supplier->id,
            'supplier_name' => 'Utilaje Grele SRL',
        ]);
    }

    public function test_equipment_supplier_name_is_frozen_after_supplier_is_renamed(): void
    {
        $user = $this->createOnboardedUser();
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Utilaje Grele SRL', 'active' => true]);

        $this->actingAs($user)->post('/equipment', [
            'name' => 'Excavator CAT 320',
            'type' => 'excavator',
            'cost_per_hour' => 150,
            'availability_status' => 'available',
            'active' => true,
            'supplier_id' => $supplier->id,
        ]);

        $supplier->update(['name' => 'Utilaje Grele Nume Nou SRL']);

        $equipment = Equipment::where('name', 'Excavator CAT 320')->first();
        $this->assertSame('Utilaje Grele SRL', $equipment->supplier_name);
    }

    public function test_material_invoice_picks_up_supplier_name_from_catalog(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Beton Mix SRL', 'active' => true]);

        $this->actingAs($user)->post('/material-invoices', [
            'project_id' => $project->id,
            'issue_date' => '2026-01-10',
            'amount_net' => 1000,
            'amount_vat' => 190,
            'amount_total' => 1190,
            'payment_status' => 'unpaid',
            'supplier_id' => $supplier->id,
        ]);

        $this->assertDatabaseHas('material_invoices', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => 'Beton Mix SRL',
        ]);
    }

    public function test_material_invoice_supplier_name_is_frozen_after_supplier_is_renamed(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Beton Mix SRL', 'active' => true]);

        $this->actingAs($user)->post('/material-invoices', [
            'project_id' => $project->id,
            'issue_date' => '2026-01-10',
            'amount_net' => 1000,
            'amount_vat' => 190,
            'amount_total' => 1190,
            'payment_status' => 'unpaid',
            'supplier_id' => $supplier->id,
        ]);

        $supplier->update(['name' => 'Beton Mix Nume Nou SRL']);

        $invoice = MaterialInvoice::where('project_id', $project->id)->first();
        $this->assertSame('Beton Mix SRL', $invoice->supplier_name);
    }

    public function test_resource_order_picks_up_supplier_name_from_catalog(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = Material::create(['tenant_id' => 1, 'name' => 'Beton C25/30', 'unit' => 'mc', 'unit_price' => 450, 'active' => true]);
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Statie Beton SRL', 'active' => true]);

        $this->actingAs($user)->post('/resource-orders', [
            'project_id' => $project->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'status' => 'ordered',
            'supplier_id' => $supplier->id,
        ]);

        $this->assertDatabaseHas('resource_orders', [
            'project_id' => $project->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => 'Statie Beton SRL',
        ]);
    }

    public function test_resource_order_supplier_name_is_frozen_after_supplier_is_renamed(): void
    {
        $user = $this->createOnboardedUser();
        $project = $this->createProject($user);
        $material = Material::create(['tenant_id' => 1, 'name' => 'Beton C25/30', 'unit' => 'mc', 'unit_price' => 450, 'active' => true]);
        $supplier = Supplier::create(['tenant_id' => 1, 'name' => 'Statie Beton SRL', 'active' => true]);

        $this->actingAs($user)->post('/resource-orders', [
            'project_id' => $project->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'status' => 'ordered',
            'supplier_id' => $supplier->id,
        ]);

        $supplier->update(['name' => 'Statie Beton Nume Nou SRL']);

        $order = ResourceOrder::where('project_id', $project->id)->first();
        $this->assertSame('Statie Beton SRL', $order->supplier_name);
    }

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Furnizori SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Furnizori',
            'status' => 'active',
            'total_budget' => 50000,
            'start_date' => now()->toDateString(),
        ]);
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
