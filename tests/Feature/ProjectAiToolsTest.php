<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\Recipe;
use App\Models\SiteEquipmentPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
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
        ]);

        $generateResponse->assertOk();
        $generateResponse->assertJsonStructure([
            'message',
            'estimate' => [
                'task_template_id',
                'task_template_title',
                'recipe_unit',
                'materials',
                'labor' => ['lines', 'estimated_cost'],
                'equipment' => ['lines', 'estimated_cost'],
                'timing' => ['execution_hours', 'drying_hours', 'curing_hours', 'total_hours'],
                'totals',
                'wbs_stages',
            ],
        ]);

        $generateResponse->assertJsonPath('estimate.totals.materials_cost', 9430);
        $generateResponse->assertJsonPath('estimate.totals.labor_cost', 225);
        $generateResponse->assertJsonPath('estimate.totals.equipment_cost', 70);
        $generateResponse->assertJsonPath('estimate.totals.total_net', 9725);
        $generateResponse->assertJsonPath('estimate.labor.lines.0.role', 'Zidar');
        $generateResponse->assertJsonPath('estimate.labor.lines.0.hours', 5);
        $generateResponse->assertJsonPath('estimate.labor.lines.0.estimated_cost', 225);
        $generateResponse->assertJsonPath('estimate.equipment.lines.0.name', 'Betoniera');
        $generateResponse->assertJsonPath('estimate.equipment.lines.0.hours', 2);
        $generateResponse->assertJsonPath('estimate.equipment.lines.0.estimated_cost', 70);
        $generateResponse->assertJsonPath('estimate.timing.execution_hours', 5);
        $generateResponse->assertJsonPath('estimate.timing.total_hours', 197);
        $generateResponse->assertJsonCount(5, 'estimate.wbs_stages');
        $expectedStages = [
            ['name' => 'Pregatire', 'stage_role' => 'pregatire'],
            ['name' => 'Aprovizionare materiale', 'stage_role' => 'aprovizionare'],
            ['name' => 'Executie - Fundatie beton', 'stage_role' => 'executie'],
            ['name' => 'Control calitate', 'stage_role' => 'control_calitate'],
            ['name' => 'Predare', 'stage_role' => 'predare'],
        ];
        foreach ($expectedStages as $i => $expected) {
            $generateResponse->assertJsonPath("estimate.wbs_stages.{$i}.name", $expected['name']);
            $generateResponse->assertJsonPath("estimate.wbs_stages.{$i}.stage_role", $expected['stage_role']);
            $generateResponse->assertJsonPath("estimate.wbs_stages.{$i}.status", 'pending');
        }

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
                'timing' => $estimate['timing'],
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

        $stagesByName = collect($createdStages)
            ->mapWithKeys(fn ($stage) => [$stage['name'] => ProjectPhase::find($stage['id'])]);

        $this->assertSame(1, $stagesByName['Pregatire']->duration_days);
        $this->assertSame(1, $stagesByName['Aprovizionare materiale']->duration_days);
        $this->assertSame(1, $stagesByName['Executie - Fundatie beton']->duration_days);
        $this->assertSame(8, $stagesByName['Control calitate']->duration_days);
        $this->assertSame(1, $stagesByName['Predare']->duration_days);

        $orderedStages = [
            $stagesByName['Pregatire'],
            $stagesByName['Aprovizionare materiale'],
            $stagesByName['Executie - Fundatie beton'],
            $stagesByName['Control calitate'],
            $stagesByName['Predare'],
        ];

        for ($i = 1; $i < count($orderedStages); $i++) {
            $expectedStart = $orderedStages[$i - 1]->end_date->copy()->addDay()->toDateString();
            $actualStart = $orderedStages[$i]->start_date->toDateString();
            $this->assertSame($expectedStart, $actualStart);
        }

        $executionStage = $stagesByName['Executie - Fundatie beton'];
        $mixer = Equipment::where('name', 'Betoniera')->firstOrFail();

        $this->assertSame(1, $commitResponse->json('created_staff_plans'));
        $this->assertSame(1, $commitResponse->json('created_equipment_plans'));

        $this->assertDatabaseHas('site_staff_plans', [
            'project_id' => $project->id,
            'phase_id' => $executionStage->id,
            'specialty' => 'Zidar',
            'planned_headcount' => 1,
        ]);

        $staffPlan = SiteStaffPlan::where('project_id', $project->id)->firstOrFail();
        $this->assertSame($executionStage->start_date->toDateString(), $staffPlan->planned_start->toDateString());
        $this->assertSame($executionStage->end_date->toDateString(), $staffPlan->planned_end->toDateString());

        $this->assertDatabaseHas('site_equipment_plans', [
            'project_id' => $project->id,
            'phase_id' => $executionStage->id,
            'equipment_id' => $mixer->id,
            'quantity' => 1,
        ]);

        $equipmentPlan = SiteEquipmentPlan::where('project_id', $project->id)->firstOrFail();
        $this->assertSame($executionStage->start_date->toDateString(), $equipmentPlan->usage_start->toDateString());
        $this->assertSame($executionStage->end_date->toDateString(), $equipmentPlan->usage_end->toDateString());

        $materialsStage = $stagesByName['Aprovizionare materiale'];
        $beton = Material::where('code', 'AI-BETON')->firstOrFail();
        $otel = Material::where('code', 'AI-OTEL')->firstOrFail();

        $this->assertSame(2, $commitResponse->json('created_material_plans'));
        $this->assertDatabaseCount('site_material_plans', 2);

        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'phase_id' => $materialsStage->id,
            'material_id' => $beton->id,
            'planned_quantity' => 10,
        ]);
        $this->assertDatabaseHas('site_material_plans', [
            'project_id' => $project->id,
            'phase_id' => $materialsStage->id,
            'material_id' => $otel->id,
            'planned_quantity' => 850,
        ]);

        $materialPlan = SiteMaterialPlan::where('project_id', $project->id)->firstOrFail();
        $this->assertSame($materialsStage->start_date->toDateString(), $materialPlan->planned_order_date->toDateString());
        $this->assertSame($materialsStage->end_date->toDateString(), $materialPlan->planned_delivery_date->toDateString());
    }

    public function test_ai_estimate_uses_recipe_wbs_template_and_generates_stage_tasks(): void
    {
        $user = $this->createOnboardedUser();
        [$project] = $this->seedProjectContext($user);
        [$template] = $this->seedTaskTemplateWithRecipeAndWbsTemplate();

        $generateResponse = $this->actingAs($user)->post(route('projects.ai.estimate.generate', $project), [
            'task_template_id' => $template->id,
            'measure_value' => 10,
            'complexity' => 'medium',
        ]);

        $generateResponse->assertOk();
        $generateResponse->assertJsonCount(6, 'estimate.wbs_stages');

        $expectedStages = [
            ['name' => 'Pregatire', 'stage_role' => 'pregatire'],
            ['name' => 'Aprovizionare materiale', 'stage_role' => 'aprovizionare'],
            ['name' => 'Sapatura', 'stage_role' => 'executie'],
            ['name' => 'Turnare', 'stage_role' => 'executie'],
            ['name' => 'Control calitate', 'stage_role' => 'control_calitate'],
            ['name' => 'Predare', 'stage_role' => 'predare'],
        ];
        foreach ($expectedStages as $i => $expected) {
            $generateResponse->assertJsonPath("estimate.wbs_stages.{$i}.name", $expected['name']);
            $generateResponse->assertJsonPath("estimate.wbs_stages.{$i}.stage_role", $expected['stage_role']);
        }

        $generateResponse->assertJsonPath('estimate.wbs_stages.2.default_tasks', ['Trasare sant', 'Excavare manuala']);
        $generateResponse->assertJsonPath('estimate.wbs_stages.3.default_tasks', ['Pregatire mixer']);

        $estimate = $generateResponse->json('estimate');

        $commitResponse = $this->actingAs($user)->post(route('projects.ai.estimate.commit', $project), [
            'title' => 'Deviz cu etape proprii',
            'total_net' => $estimate['totals']['total_net'],
            'wbs_stages' => $estimate['wbs_stages'],
            'estimate_details' => [
                'materials' => $estimate['materials'],
                'labor' => $estimate['labor'],
                'equipment' => $estimate['equipment'],
                'timing' => $estimate['timing'],
                'totals' => $estimate['totals'],
            ],
        ]);

        $commitResponse->assertOk();
        $this->assertCount(6, $commitResponse->json('created_stages'));
        $this->assertSame(3, $commitResponse->json('created_tasks'));

        $sapaturaStage = ProjectPhase::where('project_id', $project->id)->where('name', 'Sapatura')->firstOrFail();
        $turnareStage = ProjectPhase::where('project_id', $project->id)->where('name', 'Turnare')->firstOrFail();

        // 20h executie totale / 8h/zi = 3 zile, impartite pe 2 sub-etape -> ceil(3/2) = 2 zile fiecare
        $this->assertSame(2, $sapaturaStage->duration_days);
        $this->assertSame(2, $turnareStage->duration_days);

        $this->assertDatabaseCount('stage_tasks', 3);
        $this->assertDatabaseHas('stage_tasks', ['stage_id' => $sapaturaStage->id, 'title' => 'Trasare sant', 'status' => 'todo']);
        $this->assertDatabaseHas('stage_tasks', ['stage_id' => $sapaturaStage->id, 'title' => 'Excavare manuala', 'status' => 'todo']);
        $this->assertDatabaseHas('stage_tasks', ['stage_id' => $turnareStage->id, 'title' => 'Pregatire mixer', 'status' => 'todo']);

        // personalul/utilajele se leaga de PRIMA etapa de executie (Sapatura), nu de a doua (Turnare)
        $this->assertDatabaseHas('site_staff_plans', [
            'project_id' => $project->id,
            'phase_id' => $sapaturaStage->id,
            'specialty' => 'Zidar',
        ]);
        $this->assertDatabaseHas('site_equipment_plans', [
            'project_id' => $project->id,
            'phase_id' => $sapaturaStage->id,
        ]);
    }

    public function test_ai_estimate_commit_skips_staff_equipment_plans_when_plan_is_locked(): void
    {
        $user = $this->createOnboardedUser();
        [$project] = $this->seedProjectContext($user);
        [$template] = $this->seedTaskTemplateWithRecipe();
        $project->update(['plan_approved_at' => now(), 'plan_approved_by' => $user->id]);

        $generateResponse = $this->actingAs($user)->post(route('projects.ai.estimate.generate', $project), [
            'task_template_id' => $template->id,
            'measure_value' => 10,
            'complexity' => 'medium',
        ]);
        $estimate = $generateResponse->json('estimate');

        $commitResponse = $this->actingAs($user)->post(route('projects.ai.estimate.commit', $project), [
            'title' => 'Deviz plan blocat',
            'total_net' => $estimate['totals']['total_net'],
            'wbs_stages' => $estimate['wbs_stages'],
            'estimate_details' => [
                'materials' => $estimate['materials'],
                'labor' => $estimate['labor'],
                'equipment' => $estimate['equipment'],
                'timing' => $estimate['timing'],
                'totals' => $estimate['totals'],
            ],
        ]);

        $commitResponse->assertOk();
        $this->assertNotNull($commitResponse->json('quote_id'));
        $this->assertSame(0, $commitResponse->json('created_staff_plans'));
        $this->assertSame(0, $commitResponse->json('created_equipment_plans'));
        $this->assertSame(0, $commitResponse->json('created_material_plans'));
        $this->assertDatabaseCount('site_staff_plans', 0);
        $this->assertDatabaseCount('site_equipment_plans', 0);
        $this->assertDatabaseCount('site_material_plans', 0);
    }

    public function test_ai_estimate_commit_defaults_to_one_day_stages_without_timing_data(): void
    {
        $user = $this->createOnboardedUser();
        [$project] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post(route('projects.ai.estimate.commit', $project), [
            'title' => 'Deviz fara timing',
            'total_net' => 1000,
            'wbs_stages' => [
                ['name' => 'Pregatire'],
                ['name' => 'Aprovizionare materiale'],
                ['name' => 'Executie'],
                ['name' => 'Control calitate'],
                ['name' => 'Predare'],
            ],
        ]);

        $response->assertOk();

        $createdStages = $response->json('created_stages');
        foreach ($createdStages as $stage) {
            $this->assertDatabaseHas('project_phases', [
                'id' => $stage['id'],
                'duration_days' => 1,
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

    public function test_project_page_loads_with_task_templates_that_have_a_recipe(): void
    {
        $user = $this->createOnboardedUser();
        [$project] = $this->seedProjectContext($user);
        $this->seedTaskTemplateWithRecipe();

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertOk();
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

        $mixer = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Betoniera',
            'type' => 'concrete_mixer',
            'cost_per_hour' => 35,
            'availability_status' => 'available',
            'active' => true,
        ]);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Fundatie beton',
            'unit' => 'mc',
            'drying_hours' => 24,
            'curing_hours' => 168,
        ]);
        $recipe->items()->create(['material_id' => $beton->id, 'quantity_per_unit' => 1.0]);
        $recipe->items()->create(['material_id' => $otel->id, 'quantity_per_unit' => 85]);
        $recipe->laborItems()->create(['role' => 'Zidar', 'hours_per_unit' => 0.5, 'hourly_rate' => 45]);
        $recipe->equipmentItems()->create(['equipment_id' => $mixer->id, 'hours_per_unit' => 0.2]);

        return [$template, $recipe];
    }

    private function seedTaskTemplateWithRecipeAndWbsTemplate(): array
    {
        $template = TaskTemplate::create(['tenant_id' => 1, 'title' => 'Fundatie cu etape']);

        $ciment = Material::create([
            'tenant_id' => 1,
            'code' => 'AI-WBS-CIMENT',
            'name' => 'Ciment',
            'unit' => 'kg',
            'unit_price' => 1.2,
            'active' => true,
        ]);

        $mixer = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Mixer WBS',
            'type' => 'concrete_mixer',
            'cost_per_hour' => 30,
            'availability_status' => 'available',
            'active' => true,
        ]);

        $recipe = Recipe::create([
            'tenant_id' => 1,
            'subject_type' => 'task_template',
            'subject_id' => $template->id,
            'name' => 'Fundatie cu etape',
            'unit' => 'mc',
        ]);
        $recipe->items()->create(['material_id' => $ciment->id, 'quantity_per_unit' => 10]);
        $recipe->laborItems()->create(['role' => 'Zidar', 'hours_per_unit' => 2.0, 'hourly_rate' => 45]);
        $recipe->equipmentItems()->create(['equipment_id' => $mixer->id, 'hours_per_unit' => 0.5]);
        $recipe->wbsStages()->create(['name' => 'Sapatura', 'order' => 0, 'default_tasks' => ['Trasare sant', 'Excavare manuala']]);
        $recipe->wbsStages()->create(['name' => 'Turnare', 'order' => 1, 'default_tasks' => ['Pregatire mixer']]);

        return [$template, $recipe, $mixer, $ciment];
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
