<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\Recipe;
use App\Models\TaskTemplate;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\IamSeeder;
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
        [$template] = $this->seedTaskTemplateWithRecipe();

        $generateResponse = $this->actingAs($user)->post(route('projects.ai.estimate.generate', $project), [
            'task_template_id' => $template->id,
            'measure_value' => 10,
            'complexity' => 'medium',
            'labor_unit_cost' => 120,
            'equipment_unit_cost' => 70,
        ]);

        $generateResponse->assertOk();
        $generateResponse->assertJsonStructure([
            'message',
            'estimate' => [
                'task_template_id',
                'task_template_title',
                'recipe_unit',
                'materials',
                'labor',
                'equipment',
                'totals',
                'wbs_stages',
            ],
        ]);

        $generateResponse->assertJsonPath('estimate.totals.materials_cost', 9430);
        $generateResponse->assertJsonPath('estimate.totals.labor_cost', 1200);
        $generateResponse->assertJsonPath('estimate.totals.equipment_cost', 700);
        $generateResponse->assertJsonPath('estimate.totals.total_net', 11330);
        $generateResponse->assertJsonPath('estimate.wbs_stages', [
            ['name' => 'Pregatire', 'status' => 'pending'],
            ['name' => 'Aprovizionare materiale', 'status' => 'pending'],
            ['name' => 'Executie - Fundatie beton', 'status' => 'pending'],
            ['name' => 'Control calitate', 'status' => 'pending'],
            ['name' => 'Predare', 'status' => 'pending'],
        ]);

        $totalNet = (float) $generateResponse->json('estimate.totals.total_net');
        $wbsStages = $generateResponse->json('estimate.wbs_stages');
        $estimate = $generateResponse->json('estimate');

        $commitResponse = $this->actingAs($user)->post(route('projects.ai.estimate.commit', $project), [
            'title' => 'Deviz AI fundatie',
            'total_net' => $totalNet,
            'wbs_stages' => $wbsStages,
            'notes' => 'Test commit deviz AI',
            'estimate_details' => [
                'task_template_id' => $estimate['task_template_id'],
                'task_template_title' => $estimate['task_template_title'],
                'recipe_unit' => $estimate['recipe_unit'],
                'measure_value' => 10,
                'complexity' => 'medium',
                'materials' => $estimate['materials'],
                'labor' => $estimate['labor'],
                'equipment' => $estimate['equipment'],
                'totals' => $estimate['totals'],
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

    public function test_ai_estimate_generation_is_blocked_when_task_template_has_no_recipe(): void
    {
        $user = $this->createOnboardedUser();
        [$project] = $this->seedProjectContext($user);

        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Zugravit fara reteta']);

        $response = $this->actingAs($user)->post(route('projects.ai.estimate.generate', $project), [
            'task_template_id' => $template->id,
            'measure_value' => 10,
            'complexity' => 'medium',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('needs_recipe', true);
        $response->assertJsonPath('task_template_id', $template->id);
    }

    public function test_ai_tools_endpoints_are_blocked_for_a_project_belonging_to_another_tenant(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        Tenant::create([
            'id' => 2,
            'name' => 'Tenant intrus',
            'slug' => 'tenant-intrus',
            'billing_plan' => 'free',
            'status' => 'active',
            'module_flags' => [],
        ]);
        $intruder = User::factory()->create([
            'tenant_id' => 2,
            'current_tenant_id' => 2,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $this->actingAs($intruder)->post(route('projects.ai.budget-alert', $project), [
            'stage_id' => $phase->id,
            'purchase_amount' => 1000,
        ])->assertNotFound();

        $this->actingAs($intruder)->post(route('projects.ai.estimate.generate', $project), [
            'task_template_id' => 1,
            'measure_value' => 10,
        ])->assertNotFound();

        $this->actingAs($intruder)->post(route('projects.ai.estimate.commit', $project), [
            'title' => 'Deviz intrus',
            'total_net' => 100,
            'wbs_stages' => [['name' => 'Etapa']],
        ])->assertNotFound();

        $this->actingAs($intruder)->post(route('projects.ai.invoice.extract', $project), [
            'stage_id' => $phase->id,
            'attachment' => UploadedFile::fake()->image('factura.jpg'),
        ])->assertNotFound();

        $this->actingAs($intruder)->post(route('projects.ai.invoice.commit', $project), [
            'stage_id' => $phase->id,
            'temp_file_path' => 'ai-temp-invoices/nu-exista.jpg',
            'supplier_name' => 'Furnizor intrus',
            'amount' => 100,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ])->assertNotFound();

        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseMissing('project_phases', ['project_id' => $project->id, 'name' => 'Etapa']);
    }

    private function seedTaskTemplateWithRecipe(): array
    {
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Fundatie beton']);

        $beton = Material::create([
            'tenant_id' => 1,
            'code' => 'AI-BETON',
            'name' => 'Beton C25/30',
            'unit' => 'mc',
            'unit_price' => 450,
            'active' => true,
        ]);

        $otel = Material::create([
            'tenant_id' => 1,
            'code' => 'AI-OTEL',
            'name' => 'Otel beton',
            'unit' => 'kg',
            'unit_price' => 5.8,
            'active' => true,
        ]);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Fundatie beton',
            'unit' => 'mc',
        ]);
        $recipe->items()->create(['material_id' => $beton->id, 'quantity_per_unit' => 1.0]);
        $recipe->items()->create(['material_id' => $otel->id, 'quantity_per_unit' => 85]);

        return [$template, $recipe];
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
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);

        $this->seed(IamSeeder::class);

        return $user->fresh();
    }
}
