<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaterialTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_traceability_shows_aggregated_totals(): void
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

        ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'ordered_quantity' => 5,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'status' => 'ordered',
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'supplier_name' => 'Statie beton',
            'invoice_no' => 'F-001',
            'issue_date' => now()->toDateString(),
            'amount_net' => 6300,
            'amount_vat' => 1197,
            'amount_total' => 7497,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get('/trasabilitate-materiale');

        $response->assertOk();
        $response->assertInertia(function (Assert $page): void {
            $page->component('MaterialTraceability/Index')
                ->where('summary.materials_tracked', 1)
                ->where('materials.data.0.orders_count', 2)
                ->where('materials.data.0.total_ordered', 15.0)
                ->where('materials.data.0.total_ordered_value', 7500.0)
                ->where('materials.data.0.status', 'ok')
                ->where('materials.data.0.invoices.total', 7497.0)
                ->where('materials.data.0.invoices.unpaid_total', 7497.0);
        });
    }

    public function test_material_traceability_marks_worst_order_status_on_material(): void
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
            'status' => 'blocked_payment',
        ]);

        $response = $this->actingAs($user)->get('/trasabilitate-materiale');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('MaterialTraceability/Index')
            ->where('summary.with_discrepancies', 1)
            ->where('materials.data.0.status', 'blocked'));
    }

    public function test_material_traceability_does_not_leak_across_tenants(): void
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
        [$project, $phase, $material] = $this->seedContext($user);

        ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => $material->id,
            'ordered_quantity' => 10,
            'ordered_unit' => 'mc',
            'unit_price' => 500,
            'status' => 'ordered',
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
        $otherMaterial = Material::create([
            'tenant_id' => 2,
            'name' => 'Material tenant 2',
            'unit' => 'buc',
            'active' => true,
        ]);
        ResourceOrder::create([
            'tenant_id' => 2,
            'project_id' => $otherProject->id,
            'phase_id' => $otherPhase->id,
            'resource_type' => 'material',
            'material_id' => $otherMaterial->id,
            'ordered_quantity' => 99,
            'ordered_unit' => 'buc',
            'unit_price' => 10,
            'status' => 'ordered',
        ]);

        $response = $this->actingAs($user)->get('/trasabilitate-materiale');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('MaterialTraceability/Index')
            ->where('summary.materials_tracked', 1)
            ->where('materials.data.0.name', 'Beton C25/30'));
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
            'name' => 'Beton C25/30',
            'unit' => 'mc',
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
