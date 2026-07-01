<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Document;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FinancialFlowSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_flow_pages_are_accessible_and_render_priority_sections(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase, $contractor, $material] = $this->seedFinancialContext($user);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Factura instalatii HVAC',
            'type' => 'invoice',
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'contractor_id' => $contractor->id,
            'amount' => 12500,
            'issued_at' => now()->subDays(10)->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'supplier_name' => 'Furnizor materiale demo',
            'invoice_no' => 'SMOKE-MAT-001',
            'issue_date' => now()->subDays(7)->toDateString(),
            'due_date' => now()->subDay()->toDateString(),
            'amount_net' => 2500,
            'amount_vat' => 475,
            'amount_total' => 2975,
            'payment_status' => 'unpaid',
        ]);

        Quote::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'version' => 1,
            'title' => 'Oferta executie',
            'status' => 'accepted',
            'total_net' => 8000,
            'total_tva' => 1520,
            'total_gross' => 9520,
            'created_by' => $user->id,
        ]);

        $documentsResponse = $this->actingAs($user)->get('/documents');
        $expectedDocumentTitle = 'Factura instalatii HVAC';

        $documentsResponse->assertOk();
        $documentsResponse->assertInertia(function (Assert $page) use ($expectedDocumentTitle): void {
            $page->component('Documents/Index')
                ->where('documents.data.0.title', $expectedDocumentTitle)
                ->where('financialInsights.unpaid_count', 1)
                ->where('summaryByStage.0.documents_count', 1);
        });

        $materialInvoicesResponse = $this->actingAs($user)->get('/material-invoices');
        $expectedInvoiceNo = 'SMOKE-MAT-001';

        $materialInvoicesResponse->assertOk();
        $materialInvoicesResponse->assertInertia(function (Assert $page) use ($expectedInvoiceNo): void {
            $page->component('MaterialInvoices/Index')
                ->where('invoices.data.0.invoice_no', $expectedInvoiceNo)
                ->where('summary.total_count', 1)
                ->where('summary.unpaid_exposure', 2975);
        });

        $costTrackingResponse = $this->actingAs($user)->get('/cost-tracking');
        $costTrackingResponse->assertOk();
        $costTrackingResponse->assertInertia(fn (Assert $page) => $page
            ->component('CostTracking/Index')
            ->where('summary.projects_count', 1)
            ->where('summary.quotes_total', 9520)
            ->where('summary.accepted_total', 9520)
        );
    }

    private function seedFinancialContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Financial Smoke',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Financial Smoke',
            'status' => 'active',
            'total_budget' => 20000,
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa structurala',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 20,
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Financial Smoke',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'SMOKE-MAT',
            'name' => 'Material test smoke',
            'category' => 'Structura',
            'unit' => 'kg',
            'unit_price' => 12,
            'supplier' => 'Furnizor materiale demo',
            'active' => true,
        ]);

        return [$project, $phase, $contractor, $material];
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
