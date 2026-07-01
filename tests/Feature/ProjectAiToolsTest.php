<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectAiToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_invoice_flow_extracts_and_commits_document(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $extractResponse = $this->actingAs($user)->post(route('projects.ai.invoice.extract', $project), [
            'stage_id' => $phase->id,
            'attachment' => UploadedFile::fake()->image('factura-furnizor-demo-1250.jpg'),
        ]);

        $extractResponse->assertOk();
        $extractResponse->assertJsonStructure([
            'message',
            'draft' => [
                'temp_file_path',
                'file_name',
                'stage_id',
                'supplier_name',
                'amount',
                'vat_amount',
            ],
        ]);

        $tempFilePath = $extractResponse->json('draft.temp_file_path');
        $this->assertNotNull($tempFilePath);
        Storage::disk('local')->assertExists($tempFilePath);

        $commitResponse = $this->actingAs($user)->post(route('projects.ai.invoice.commit', $project), [
            'stage_id' => $phase->id,
            'temp_file_path' => $tempFilePath,
            'supplier_name' => 'Furnizor Demo AI',
            'invoice_number' => 'INV-2026-0001',
            'amount' => 1250,
            'vat_amount' => 237.5,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
            'title' => 'Factura test AI',
            'notes' => 'Confirmare manuala pentru test.',
        ]);

        $commitResponse->assertOk();
        $commitResponse->assertJsonStructure(['message', 'document_id', 'contractor_id']);

        $document = Document::query()->find($commitResponse->json('document_id'));

        $this->assertNotNull($document);
        $this->assertSame('Factura test AI', $document->title);
        $this->assertSame($project->id, $document->project_id);
        $this->assertSame($phase->id, $document->stage_id);
        $this->assertSame('invoice', $document->type);
        $this->assertSame('INV-2026-0001', $document->invoice_number);
        $this->assertSame('unpaid', $document->payment_status);
        $this->assertStringContainsString('TVA estimat: 237.50', (string) $document->notes);

        Storage::disk('local')->assertMissing($tempFilePath);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_ai_budget_alert_returns_stage_and_profit_impact(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $project->update(['total_budget' => 20000]);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Factura baza etapa',
            'type' => 'invoice',
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'amount' => 12000,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ]);

        $material = Material::create([
            'tenant_id' => 1,
            'code' => 'AI-TEST-MAT',
            'name' => 'Material AI test',
            'category' => 'Structura',
            'unit' => 'kg',
            'unit_price' => 5,
            'supplier' => 'Supplier test',
            'active' => true,
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'material_id' => $material->id,
            'issue_date' => now()->toDateString(),
            'amount_net' => 2500,
            'amount_vat' => 500,
            'amount_total' => 3000,
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($user)->post(route('projects.ai.budget-alert', $project), [
            'stage_id' => $phase->id,
            'purchase_amount' => 7000,
            'purchase_source' => 'material_invoice',
        ]);

        $response->assertOk();
        $response->assertJsonPath('alert.stage_name', $phase->name);
        $response->assertJsonPath('alert.stage_overrun_amount', 2000);
        $response->assertJsonPath('alert.stage_overrun_pct', 10);
        $response->assertJsonPath('alert.project_overrun_amount', 2000);
        $response->assertJsonPath('alert.profit_impact_amount', -2000);
    }

    public function test_ai_estimate_flow_generates_and_commits_quote_with_wbs_stages(): void
    {
        $user = $this->createOnboardedUser();
        [$project] = $this->seedProjectContext($user);

        $generateResponse = $this->actingAs($user)->post(route('projects.ai.estimate.generate', $project), [
            'work_type' => 'foundation',
            'measure_type' => 'volume',
            'measure_value' => 10,
            'complexity' => 'medium',
        ]);

        $generateResponse->assertOk();
        $generateResponse->assertJsonStructure([
            'message',
            'estimate' => [
                'materials',
                'labor',
                'equipment',
                'totals',
                'wbs_stages',
            ],
        ]);

        $totalNet = (float) $generateResponse->json('estimate.totals.total_net');
        $wbsStages = $generateResponse->json('estimate.wbs_stages');

        $this->assertGreaterThan(0, $totalNet);
        $this->assertIsArray($wbsStages);
        $this->assertNotEmpty($wbsStages);

        $commitResponse = $this->actingAs($user)->post(route('projects.ai.estimate.commit', $project), [
            'title' => 'Deviz AI fundatie',
            'total_net' => $totalNet,
            'wbs_stages' => $wbsStages,
            'notes' => 'Test commit deviz AI',
            'estimate_details' => [
                'work_type' => 'foundation',
                'measure_type' => 'volume',
                'measure_value' => 10,
                'complexity' => 'medium',
                'materials' => [
                    [
                        'name' => 'Beton C25/30',
                        'quantity' => 10,
                        'unit' => 'mc',
                        'unit_price' => 450,
                        'estimated_cost' => 4500,
                    ],
                ],
                'labor' => [
                    [
                        'name' => 'Manopera foundation',
                        'estimated_hours' => 13,
                        'hour_rate' => 92,
                        'estimated_cost' => 1196,
                    ],
                ],
                'totals' => [
                    'materials_cost' => 3300,
                    'labor_cost' => 1200,
                    'equipment_cost' => 700,
                    'total_net' => $totalNet,
                ],
            ],
        ]);

        $commitResponse->assertOk();
        $commitResponse->assertJsonStructure(['message', 'quote_id', 'document_id', 'created_stages']);

        $quote = Quote::query()->find($commitResponse->json('quote_id'));
        $this->assertNotNull($quote);
        $this->assertSame($project->id, $quote->project_id);
        $this->assertSame('draft', $quote->status);
        $this->assertSame('21.00', (string) $quote->tva_pct);
        $this->assertGreaterThan(0, (float) $quote->total_net);
        $this->assertStringContainsString('[AI_BREAKDOWN_JSON]', (string) $quote->notes);

        $estimateDocument = Document::query()->find($commitResponse->json('document_id'));
        $this->assertNotNull($estimateDocument);
        $this->assertSame('estimate', $estimateDocument->type);
        $this->assertSame($project->id, $estimateDocument->project_id);

        $pdfResponse = $this->actingAs($user)->get(route('quotes.pdf', $quote));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');

        $acceptResponse = $this->actingAs($user)->patch(route('quotes.accept', $quote));
        $acceptResponse->assertRedirect(route('quotes.index'));
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => 'accepted',
        ]);

        $createdStages = $commitResponse->json('created_stages');
        $this->assertNotEmpty($createdStages);

        foreach ($createdStages as $stage) {
            $this->assertDatabaseHas('project_phases', [
                'id' => $stage['id'],
                'project_id' => $project->id,
                'name' => $stage['name'],
            ]);
        }
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client AI Tools',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect AI Tools',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa AI',
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
