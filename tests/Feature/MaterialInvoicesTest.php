<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaterialInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_invoice_can_be_created(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        $response = $this->actingAs($user)->post('/material-invoices', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'supplier_name' => 'Furnizor beton',
            'invoice_no' => 'MAT-INV-001',
            'issue_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'amount_net' => 1000,
            'amount_vat' => 190,
            'amount_total' => 1190,
            'payment_status' => 'unpaid',
            'notes' => 'Factura beton C25/30',
        ]);

        $response->assertRedirect('/material-invoices');

        $this->assertDatabaseHas('material_invoices', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'invoice_no' => 'MAT-INV-001',
            'payment_status' => 'unpaid',
            'amount_total' => 1190,
        ]);
    }

    public function test_material_invoices_can_be_filtered_by_payment_status(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $material] = $this->seedContext($user);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'invoice_no' => 'PAID-1',
            'issue_date' => now()->toDateString(),
            'amount_net' => 500,
            'amount_vat' => 95,
            'amount_total' => 595,
            'payment_status' => 'paid',
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'invoice_no' => 'UNPAID-1',
            'issue_date' => now()->toDateString(),
            'amount_net' => 700,
            'amount_vat' => 133,
            'amount_total' => 833,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->get('/material-invoices?payment_status=unpaid');
        $expectedInvoiceNo = 'UNPAID-1';

        $response->assertOk();
        $response->assertInertia(function (Assert $page) use ($expectedInvoiceNo): void {
            $page->component('MaterialInvoices/Index')
            ->where('invoices.data.0.invoice_no', $expectedInvoiceNo)
                ->where('summary.total_count', 2);
        });
    }

    public function test_material_invoice_rejects_phase_that_does_not_belong_to_project(): void
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
            ->from('/material-invoices/create')
            ->post('/material-invoices', [
                'project_id' => $projectB->id,
                'phase_id' => $phaseA->id,
                'material_id' => $material->id,
                'issue_date' => '2026-07-01',
                'amount_net' => 100,
                'amount_vat' => 19,
                'amount_total' => 119,
                'payment_status' => 'unpaid',
            ]);

        $response->assertRedirect('/material-invoices/create');
        $response->assertSessionHasErrors('phase_id');

        $this->assertDatabaseMissing('material_invoices', [
            'project_id' => $projectB->id,
            'phase_id' => $phaseA->id,
            'amount_total' => 119,
        ]);
    }

    private function seedContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Facturi Materiale',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Facturi Materiale',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa materiale',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 25,
        ]);

        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-100',
            'name' => 'Beton B250',
            'category' => 'Structura',
            'unit' => 'mc',
            'unit_price' => 500,
            'supplier' => 'Furnizor Beton',
            'active' => true,
        ]);

        return [$project, $phase, $material];
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
