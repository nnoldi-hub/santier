<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Document;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\SiteEquipmentPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
use App\Models\StageTask;
use App\Models\TaskTemplate;
use App\Support\InvoiceOcrService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectAiToolsController extends Controller
{
    public function generateEstimate(Request $request, Project $project): JsonResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'task_template_id' => ['required', 'integer', Rule::exists('task_templates', 'id')->where('tenant_id', $tenantId)],
            'measure_value' => ['required', 'numeric', 'min:0.1'],
            'complexity' => ['nullable', 'in:low,medium,high'],
        ]);

        $template = TaskTemplate::with(['recipe.items.material', 'recipe.laborItems', 'recipe.equipmentItems.equipment', 'recipe.wbsStages'])->findOrFail($validated['task_template_id']);
        abort_unless((int) $template->tenant_id === $tenantId, 404);

        if (!$template->recipe) {
            return response()->json([
                'message' => 'Acest sablon nu are inca o reteta de consum. Creeaza o reteta pentru a putea genera un deviz corect.',
                'needs_recipe' => true,
                'task_template_id' => $template->id,
                'task_template_title' => $template->title,
            ], 422);
        }

        $measureValue = (float) $validated['measure_value'];
        $complexity = $validated['complexity'] ?? 'medium';

        $complexityFactor = match ($complexity) {
            'low' => 0.9,
            'high' => 1.2,
            default => 1.0,
        };

        $recipe = $template->recipe;

        $materials = $recipe->items->map(function ($item) use ($measureValue, $complexityFactor): array {
            $quantity = round((float) $item->quantity_per_unit * $measureValue * $complexityFactor, 2);
            $unitPrice = (float) ($item->material->unit_price ?? 0);

            return [
                'material_id' => $item->material_id,
                'name' => $item->material->name ?? '',
                'unit' => $item->material->unit ?? '',
                'quantity_per_unit' => (float) $item->quantity_per_unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'estimated_cost' => round($quantity * $unitPrice, 2),
            ];
        })->values();

        $laborLines = $recipe->laborItems->map(function ($item) use ($measureValue, $complexityFactor): array {
            $hours = round((float) $item->hours_per_unit * $measureValue * $complexityFactor, 2);
            $hourlyRate = (float) $item->hourly_rate;

            return [
                'role' => $item->role,
                'hours_per_unit' => (float) $item->hours_per_unit,
                'hours' => $hours,
                'hourly_rate' => $hourlyRate,
                'estimated_cost' => round($hours * $hourlyRate, 2),
            ];
        })->values();

        $equipmentLines = $recipe->equipmentItems->map(function ($item) use ($measureValue, $complexityFactor): array {
            $hours = round((float) $item->hours_per_unit * $measureValue * $complexityFactor, 2);
            $hourlyRate = (float) ($item->equipment->cost_per_hour ?? 0);

            return [
                'equipment_id' => $item->equipment_id,
                'name' => $item->equipment->name ?? '',
                'hours_per_unit' => (float) $item->hours_per_unit,
                'hours' => $hours,
                'hourly_rate' => $hourlyRate,
                'estimated_cost' => round($hours * $hourlyRate, 2),
            ];
        })->values();

        $materialsCost = round($materials->sum('estimated_cost'), 2);
        $laborCost = round($laborLines->sum('estimated_cost'), 2);
        $equipmentCost = round($equipmentLines->sum('estimated_cost'), 2);
        $subtotal = round($materialsCost + $laborCost + $equipmentCost, 2);

        $executionHours = round($laborLines->sum('hours'), 2);
        $dryingHours = (float) ($recipe->drying_hours ?? 0);
        $curingHours = (float) ($recipe->curing_hours ?? 0);
        $totalHours = round($executionHours + $dryingHours + $curingHours, 2);

        $wbsStages = collect([
            ['name' => 'Pregatire', 'stage_role' => 'pregatire'],
            ['name' => 'Aprovizionare materiale', 'stage_role' => 'aprovizionare'],
        ]);

        if ($recipe->wbsStages->isNotEmpty()) {
            foreach ($recipe->wbsStages as $customStage) {
                $wbsStages->push([
                    'name' => $customStage->name,
                    'stage_role' => 'executie',
                    'default_tasks' => $customStage->default_tasks ?? [],
                ]);
            }
        } else {
            $wbsStages->push(['name' => "Executie - {$template->title}", 'stage_role' => 'executie']);
        }

        $wbsStages->push(['name' => 'Control calitate', 'stage_role' => 'control_calitate']);
        $wbsStages->push(['name' => 'Predare', 'stage_role' => 'predare']);

        $wbsStages = $wbsStages
            ->map(fn (array $stage) => array_merge(['default_tasks' => []], $stage, ['status' => 'pending']))
            ->values()
            ->all();

        return response()->json([
            'message' => 'Devizul automat a fost generat pe baza retetei de consum si a cantitatii introduse.',
            'estimate' => [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'task_template_id' => $template->id,
                'task_template_title' => $template->title,
                'recipe_unit' => $recipe->unit,
                'measure_value' => $measureValue,
                'complexity' => $complexity,
                'materials' => $materials,
                'labor' => [
                    'lines' => $laborLines,
                    'estimated_cost' => $laborCost,
                ],
                'equipment' => [
                    'lines' => $equipmentLines,
                    'estimated_cost' => $equipmentCost,
                ],
                'timing' => [
                    'execution_hours' => $executionHours,
                    'drying_hours' => $dryingHours,
                    'curing_hours' => $curingHours,
                    'total_hours' => $totalHours,
                ],
                'totals' => [
                    'materials_cost' => $materialsCost,
                    'labor_cost' => $laborCost,
                    'equipment_cost' => $equipmentCost,
                    'total_net' => $subtotal,
                ],
                'wbs_stages' => $wbsStages,
            ],
        ]);
    }

    public function commitEstimate(Request $request, Project $project): JsonResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'total_net' => ['required', 'numeric', 'min:0'],
            'wbs_stages' => ['required', 'array', 'min:1'],
            'wbs_stages.*.name' => ['required', 'string', 'max:255'],
            'wbs_stages.*.stage_role' => ['nullable', 'string', 'max:30'],
            'wbs_stages.*.default_tasks' => ['nullable', 'array'],
            'wbs_stages.*.default_tasks.*' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'estimate_details' => ['nullable', 'array'],
            'estimate_details.task_template_id' => ['nullable', 'integer'],
            'estimate_details.task_template_title' => ['nullable', 'string', 'max:255'],
            'estimate_details.recipe_unit' => ['nullable', 'string', 'max:50'],
            'estimate_details.measure_value' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.complexity' => ['nullable', 'string', 'max:50'],
            'estimate_details.materials' => ['nullable', 'array'],
            'estimate_details.materials.*.material_id' => ['nullable', 'integer'],
            'estimate_details.materials.*.name' => ['nullable', 'string', 'max:255'],
            'estimate_details.materials.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.materials.*.unit' => ['nullable', 'string', 'max:50'],
            'estimate_details.materials.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.materials.*.estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.labor' => ['nullable', 'array'],
            'estimate_details.labor.lines' => ['nullable', 'array'],
            'estimate_details.labor.lines.*.role' => ['nullable', 'string', 'max:100'],
            'estimate_details.labor.lines.*.hours' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.labor.lines.*.hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.labor.lines.*.estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.labor.estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.equipment' => ['nullable', 'array'],
            'estimate_details.equipment.lines' => ['nullable', 'array'],
            'estimate_details.equipment.lines.*.equipment_id' => ['nullable', 'integer'],
            'estimate_details.equipment.lines.*.name' => ['nullable', 'string', 'max:255'],
            'estimate_details.equipment.lines.*.hours' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.equipment.lines.*.hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.equipment.lines.*.estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.equipment.estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.timing' => ['nullable', 'array'],
            'estimate_details.timing.execution_hours' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.timing.drying_hours' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.timing.curing_hours' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.timing.total_hours' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.totals' => ['nullable', 'array'],
            'estimate_details.totals.materials_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.totals.labor_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.totals.equipment_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.totals.total_net' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tvaPct = 21.0;
        $totalNet = (float) $validated['total_net'];
        $totalTva = round($totalNet * ($tvaPct / 100), 2);
        $totalGross = round($totalNet + $totalTva, 2);
        $quoteNotes = $this->composeQuoteNotes(
            $validated['notes'] ?? 'Deviz generat automat din modul AI Tools.',
            $validated['estimate_details'] ?? null
        );

        $nextVersion = (Quote::query()->where('project_id', $project->id)->max('version') ?? 0) + 1;

        $quote = Quote::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'version' => $nextVersion,
            'title' => $validated['title'],
            'status' => 'draft',
            'discount_pct' => 0,
            'tva_pct' => $tvaPct,
            'notes' => $quoteNotes,
            'total_net' => $totalNet,
            'total_tva' => $totalTva,
            'total_gross' => $totalGross,
            'created_by' => $request->user()->id,
        ]);

        $maxOrder = (int) ($project->phases()->max('order') ?? 0);
        $createdStages = [];
        $estimateStageId = null;
        $createdTasks = 0;

        $timing = $validated['estimate_details']['timing'] ?? [];
        $executionDays = max(1, (int) ceil((float) ($timing['execution_hours'] ?? 0) / 8));
        $controlDays = max(1, (int) ceil(
            ((float) ($timing['drying_hours'] ?? 0) + (float) ($timing['curing_hours'] ?? 0)) / 24
        ));
        $executionRoleCount = collect($validated['wbs_stages'])->where('stage_role', 'executie')->count();
        $executionDaysPerStage = max(1, (int) ceil($executionDays / max(1, $executionRoleCount)));
        $dateCursor = now()->startOfDay();

        $firstExecutionPhase = null;
        $materialsPhase = null;

        foreach ($validated['wbs_stages'] as $index => $stageInput) {
            $name = trim($stageInput['name']);
            if ($name === '') {
                continue;
            }

            $role = $stageInput['stage_role'] ?? null;

            $existingPhase = ProjectPhase::query()
                ->where('project_id', $project->id)
                ->where('name', $name)
                ->first();

            if ($existingPhase) {
                if ($role === 'executie' && !$firstExecutionPhase) {
                    $firstExecutionPhase = $existingPhase;
                }
                if ($role === 'aprovizionare' && !$materialsPhase) {
                    $materialsPhase = $existingPhase;
                }
                continue;
            }

            $maxOrder += 1;

            $duration = match ($role) {
                'executie' => $executionDaysPerStage,
                'control_calitate' => $controlDays,
                default => 1,
            };
            $stageStart = $dateCursor->copy();
            $stageEnd = $dateCursor->copy()->addDays($duration - 1);
            $dateCursor = $stageEnd->copy()->addDay();

            $stage = ProjectPhase::create([
                'project_id' => $project->id,
                'name' => $name,
                'type' => 'custom',
                'status' => 'pending',
                'progress_pct' => 0,
                'order' => $maxOrder,
                'notes' => 'Etapa propusa automat de AI Tools.',
                'start_date' => $stageStart->toDateString(),
                'end_date' => $stageEnd->toDateString(),
                'duration_days' => $duration,
            ]);

            $createdStages[] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'position' => $index + 1,
                'start_date' => $stageStart->toDateString(),
                'end_date' => $stageEnd->toDateString(),
            ];

            if ($estimateStageId === null) {
                $estimateStageId = $stage->id;
            }

            if ($role === 'executie' && !$firstExecutionPhase) {
                $firstExecutionPhase = $stage;
            }
            if ($role === 'aprovizionare' && !$materialsPhase) {
                $materialsPhase = $stage;
            }

            foreach ($stageInput['default_tasks'] ?? [] as $taskTitle) {
                $taskTitle = trim($taskTitle);
                if ($taskTitle === '') {
                    continue;
                }

                StageTask::create([
                    'stage_id' => $stage->id,
                    'title' => $taskTitle,
                    'status' => 'todo',
                ]);
                $createdTasks++;
            }
        }

        if ($estimateStageId === null) {
            $estimateStageId = $project->phases()->value('id');
        }

        $estimateDocument = Document::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'stage_id' => $estimateStageId,
            'type' => 'estimate',
            'amount' => $totalNet,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? 'Deviz generat automat din modul AI Tools.',
        ]);

        $createdStaffPlans = 0;
        $createdEquipmentPlans = 0;
        $createdMaterialPlans = 0;
        $planLocked = $project->plan_approved_at !== null;

        if (!$planLocked) {
            $materialLines = $validated['estimate_details']['materials'] ?? [];
            foreach ($materialLines as $line) {
                if (empty($line['material_id'])) {
                    continue;
                }

                SiteMaterialPlan::create([
                    'tenant_id' => $tenantId,
                    'project_id' => $project->id,
                    'phase_id' => $materialsPhase?->id,
                    'material_id' => $line['material_id'],
                    'planned_quantity' => $line['quantity'] ?? 0,
                    'unit_price' => $line['unit_price'] ?? 0,
                    'planned_order_date' => $materialsPhase?->start_date?->toDateString(),
                    'planned_delivery_date' => $materialsPhase?->end_date?->toDateString(),
                    'risk_level' => 'medium',
                    'notes' => 'Generat automat din reteta la commit deviz AI.',
                ]);
                $createdMaterialPlans++;
            }

            $laborLines = $validated['estimate_details']['labor']['lines'] ?? [];
            foreach ($laborLines as $line) {
                if (empty($line['role'])) {
                    continue;
                }

                SiteStaffPlan::create([
                    'tenant_id' => $tenantId,
                    'project_id' => $project->id,
                    'phase_id' => $firstExecutionPhase?->id,
                    'specialty' => $line['role'],
                    'planned_headcount' => 1,
                    'hourly_rate' => $line['hourly_rate'] ?? 0,
                    'planned_start' => $firstExecutionPhase?->start_date?->toDateString(),
                    'planned_end' => $firstExecutionPhase?->end_date?->toDateString(),
                    'risk_level' => 'medium',
                    'notes' => 'Generat automat din reteta la commit deviz AI.',
                ]);
                $createdStaffPlans++;
            }

            $equipmentLines = $validated['estimate_details']['equipment']['lines'] ?? [];
            foreach ($equipmentLines as $line) {
                if (empty($line['equipment_id'])) {
                    continue;
                }

                SiteEquipmentPlan::create([
                    'tenant_id' => $tenantId,
                    'project_id' => $project->id,
                    'phase_id' => $firstExecutionPhase?->id,
                    'equipment_id' => $line['equipment_id'],
                    'quantity' => 1,
                    'hourly_rate' => $line['hourly_rate'] ?? 0,
                    'usage_start' => $firstExecutionPhase?->start_date?->toDateString(),
                    'usage_end' => $firstExecutionPhase?->end_date?->toDateString(),
                    'risk_level' => 'medium',
                    'notes' => 'Generat automat din reteta la commit deviz AI.',
                ]);
                $createdEquipmentPlans++;
            }
        }

        $message = 'Devizul a fost salvat ca oferta draft, document de tip deviz si etapele WBS au fost adaugate in proiect.';
        if ($createdTasks > 0) {
            $message .= " S-au generat automat {$createdTasks} task-uri pe etapele de executie.";
        }
        if ($planLocked) {
            $message .= ' Planul de organizare santier e deja aprobat, deci nu s-au generat automat planuri de personal/utilaje/materiale.';
        } elseif ($createdStaffPlans > 0 || $createdEquipmentPlans > 0 || $createdMaterialPlans > 0) {
            $message .= " S-au generat automat {$createdStaffPlans} planuri de personal, {$createdEquipmentPlans} planuri de utilaje si {$createdMaterialPlans} planuri de materiale in Organizare Santier.";
        }

        return response()->json([
            'message' => $message,
            'quote_id' => $quote->id,
            'document_id' => $estimateDocument->id,
            'created_stages' => $createdStages,
            'created_tasks' => $createdTasks,
            'created_staff_plans' => $createdStaffPlans,
            'created_equipment_plans' => $createdEquipmentPlans,
            'created_material_plans' => $createdMaterialPlans,
        ]);
    }

    public function budgetAlert(Request $request, Project $project): JsonResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'stage_id' => ['required', 'integer', 'exists:project_phases,id'],
            'purchase_amount' => ['required', 'numeric', 'min:0.01'],
            'purchase_source' => ['nullable', 'in:document,material_invoice,equipment,other'],
        ]);

        $stage = $project->phases()->whereKey($validated['stage_id'])->first();
        if (!$stage) {
            return response()->json([
                'message' => 'Etapa selectata nu apartine proiectului.',
            ], 422);
        }

        $purchaseAmount = (float) $validated['purchase_amount'];

        $currentStageDocumentsCost = (float) Document::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $project->id)
            ->where('stage_id', $stage->id)
            ->sum('amount');

        $currentStageMaterialsCost = (float) MaterialInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $project->id)
            ->where('phase_id', $stage->id)
            ->sum('amount_total');

        $currentStageCost = $currentStageDocumentsCost + $currentStageMaterialsCost;

        $projectPhaseCount = max(1, (int) $project->phases()->count());
        $projectBudget = (float) ($project->total_budget ?? 0);
        $stageBudget = $projectBudget > 0 ? ($projectBudget / $projectPhaseCount) : 0;

        $predictedStageCost = $currentStageCost + $purchaseAmount;
        $stageOverrunAmount = max(0, $predictedStageCost - $stageBudget);
        $stageOverrunPct = $stageBudget > 0
            ? round(($stageOverrunAmount / $stageBudget) * 100, 2)
            : 0;

        $currentProjectDocumentsCost = (float) Document::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $project->id)
            ->sum('amount');

        $currentProjectMaterialsCost = (float) MaterialInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('project_id', $project->id)
            ->sum('amount_total');

        $predictedProjectCost = $currentProjectDocumentsCost + $currentProjectMaterialsCost + $purchaseAmount;
        $projectOverrunAmount = max(0, $predictedProjectCost - $projectBudget);
        $profitImpactAmount = -1 * $projectOverrunAmount;
        $profitImpactPct = $projectBudget > 0
            ? round(($projectOverrunAmount / $projectBudget) * 100, 2)
            : 0;

        $recommendation = $this->buildBudgetRecommendation($stageOverrunPct, $projectOverrunAmount);

        return response()->json([
            'message' => 'Calcul AI buget finalizat.',
            'alert' => [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'purchase_amount' => $purchaseAmount,
                'purchase_source' => $validated['purchase_source'] ?? 'other',
                'current_stage_cost' => round($currentStageCost, 2),
                'stage_budget' => round($stageBudget, 2),
                'predicted_stage_cost' => round($predictedStageCost, 2),
                'stage_overrun_amount' => round($stageOverrunAmount, 2),
                'stage_overrun_pct' => $stageOverrunPct,
                'predicted_project_cost' => round($predictedProjectCost, 2),
                'project_budget' => round($projectBudget, 2),
                'project_overrun_amount' => round($projectOverrunAmount, 2),
                'profit_impact_amount' => round($profitImpactAmount, 2),
                'profit_impact_pct' => $profitImpactPct,
                'recommendation' => $recommendation,
            ],
        ]);
    }

    public function extractInvoice(Request $request, Project $project): JsonResponse
    {
        abort_unless((int) $project->tenant_id === TenantContext::id($request->user()), 404);

        $validated = $request->validate([
            'stage_id' => ['required', 'integer', 'exists:project_phases,id'],
            'attachment' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp'],
        ]);

        $stageBelongsToProject = $project->phases()->whereKey($validated['stage_id'])->exists();
        if (!$stageBelongsToProject) {
            return response()->json([
                'message' => 'Etapa selectata nu apartine proiectului.',
            ], 422);
        }

        $file = $request->file('attachment');
        $originalName = $file->getClientOriginalName();
        $tempPath = $file->store('ai-temp-invoices', 'local');

        $absolutePath = Storage::disk('local')->path($tempPath);
        $ocrResult = app(InvoiceOcrService::class)->extractText($absolutePath);
        $ocrText = (string) ($ocrResult['text'] ?? '');

        $supplierGuess = $this->guessSupplierFromText($ocrText) ?: $this->guessSupplierFromFilename($originalName);
        $amountGuess = $this->guessAmountFromText($ocrText !== '' ? $ocrText : $originalName);
        $vatGuess = $this->guessVatFromText($ocrText);

        if ($vatGuess === null && $amountGuess > 0) {
            $vatGuess = round($amountGuess * 0.19, 2);
        }

        $invoiceNumber = $this->guessInvoiceNumberFromText($ocrText !== '' ? $ocrText : $originalName);
        $issuedAtGuess = $this->guessDateFromText($ocrText) ?: now()->toDateString();

        return response()->json([
            'message' => 'Factura a fost prelucrata. Verifica si confirma datele extrase.',
            'draft' => [
                'temp_file_path' => $tempPath,
                'file_name' => $originalName,
                'stage_id' => $validated['stage_id'],
                'supplier_name' => $supplierGuess,
                'amount' => $amountGuess,
                'vat_amount' => $vatGuess,
                'invoice_number' => $invoiceNumber,
                'issued_at' => $issuedAtGuess,
                'payment_status' => 'unpaid',
                'title' => 'Factura ' . $supplierGuess,
                'notes' => 'Draft AI generat automat. Verifica datele inainte de confirmare.',
                'confidence' => [
                    'supplier_name' => $supplierGuess !== '' ? 0.62 : 0.3,
                    'amount' => $amountGuess > 0 ? 0.58 : 0.35,
                    'vat_amount' => $vatGuess !== null ? 0.45 : 0.2,
                    'invoice_number' => $invoiceNumber !== '' ? 0.55 : 0.2,
                ],
                'meta' => [
                    'ocr_provider' => (string) ($ocrResult['provider'] ?? 'mock'),
                    'ocr_confidence' => (float) ($ocrResult['confidence'] ?? 0),
                ],
            ],
        ]);
    }

    public function commitInvoice(Request $request, Project $project): JsonResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'stage_id' => ['required', 'integer', 'exists:project_phases,id'],
            'temp_file_path' => ['required', 'string'],
            'supplier_name' => ['required', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'issued_at' => ['required', 'date'],
            'payment_status' => ['required', 'in:unpaid,partial,paid,cancelled'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $stageBelongsToProject = $project->phases()->whereKey($validated['stage_id'])->exists();
        if (!$stageBelongsToProject) {
            return response()->json([
                'message' => 'Etapa selectata nu apartine proiectului.',
            ], 422);
        }

        if (!Storage::disk('local')->exists($validated['temp_file_path'])) {
            return response()->json([
                'message' => 'Fisierul incarcat nu mai este disponibil. Reincarca documentul.',
            ], 422);
        }

        $contractor = Contractor::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => $validated['supplier_name'],
            ],
            [
                'type' => Contractor::TYPE_MATERIALS_SUPPLIER,
                'active' => true,
            ]
        );

        $extension = pathinfo($validated['temp_file_path'], PATHINFO_EXTENSION) ?: 'bin';
        $finalPath = 'documents/ai-' . Str::uuid() . '.' . $extension;

        Storage::disk('local')->move($validated['temp_file_path'], $finalPath);

        $document = Document::create([
            'tenant_id' => $tenantId,
            'contractor_id' => $contractor->id,
            'project_id' => $project->id,
            'stage_id' => (int) $validated['stage_id'],
            'type' => 'invoice',
            'amount' => (float) $validated['amount'],
            'issued_at' => $validated['issued_at'],
            'payment_status' => $validated['payment_status'],
            'title' => $validated['title'] ?: ('Factura ' . $validated['supplier_name']),
            'invoice_number' => $validated['invoice_number'] ?? null,
            'file_path' => $finalPath,
            'file_name' => basename($finalPath),
            'mime_type' => null,
            'file_size' => Storage::disk('local')->size($finalPath),
            'notes' => trim(($validated['notes'] ?? '')
                . "\nNumar factura: " . ($validated['invoice_number'] ?? '-')
                . "\nTVA estimat: " . number_format((float) ($validated['vat_amount'] ?? 0), 2, '.', '')),
        ]);

        return response()->json([
            'message' => 'Factura a fost inregistrata automat in documente financiare.',
            'document_id' => $document->id,
            'contractor_id' => $contractor->id,
        ]);
    }

    private function guessSupplierFromFilename(string $fileName): string
    {
        $withoutExtension = pathinfo($fileName, PATHINFO_FILENAME);
        $clean = str_replace(['_', '-', '.'], ' ', $withoutExtension);
        $clean = preg_replace('/\s+/', ' ', $clean ?? '');
        $clean = trim((string) $clean);

        if ($clean === '') {
            return 'Furnizor necunoscut';
        }

        return Str::title(Str::limit($clean, 80, ''));
    }

    private function guessAmountFromText(string $text): float
    {
        preg_match('/(\d{2,6}(?:[\.,]\d{1,2})?)/', $text, $matches);

        if (!isset($matches[1])) {
            return 0;
        }

        $value = str_replace(',', '.', $matches[1]);

        return (float) $value;
    }

    private function guessVatFromText(string $text): ?float
    {
        if ($text === '') {
            return null;
        }

        if (preg_match('/(?:TVA|VAT)\s*[:\-]?\s*(\d{1,6}(?:[\.,]\d{1,2})?)/iu', $text, $matches) !== 1) {
            return null;
        }

        return (float) str_replace(',', '.', $matches[1]);
    }

    private function guessInvoiceNumberFromText(string $text): string
    {
        if (preg_match('/(?:factura|invoice|nr\.?|no\.?|numar)\s*[:#\-]?\s*([A-Z0-9\-\/]{3,})/iu', $text, $matches) === 1) {
            return strtoupper(trim($matches[1]));
        }

        if (preg_match('/\b([A-Z]{1,4}-?\d{3,12})\b/u', $text, $matches) === 1) {
            return strtoupper(trim($matches[1]));
        }

        return '';
    }

    private function guessDateFromText(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        if (preg_match('/\b(\d{2})[\.\/-](\d{2})[\.\/-](\d{4})\b/u', $text, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }

        return null;
    }

    private function guessSupplierFromText(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (preg_match('/(?:furnizor|supplier|emitent)\s*[:\-]?\s*([^\n\r]{3,80})/iu', $text, $matches) === 1) {
            return trim($matches[1]);
        }

        return '';
    }

    private function composeQuoteNotes(string $plainNotes, ?array $estimateDetails): string
    {
        if (!$estimateDetails) {
            return $plainNotes;
        }

        $payload = [
            'task_template_id' => $estimateDetails['task_template_id'] ?? null,
            'task_template_title' => $estimateDetails['task_template_title'] ?? null,
            'recipe_unit' => $estimateDetails['recipe_unit'] ?? null,
            'measure_value' => $estimateDetails['measure_value'] ?? null,
            'complexity' => $estimateDetails['complexity'] ?? null,
            'materials' => $estimateDetails['materials'] ?? [],
            'labor' => $estimateDetails['labor'] ?? [],
            'equipment' => $estimateDetails['equipment'] ?? [],
            'timing' => $estimateDetails['timing'] ?? [],
            'totals' => $estimateDetails['totals'] ?? [],
        ];

        return trim($plainNotes) . "\n\n[AI_BREAKDOWN_JSON]\n" . json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    private function buildBudgetRecommendation(float $stageOverrunPct, float $projectOverrunAmount): string
    {
        if ($projectOverrunAmount > 0 && $stageOverrunPct >= 10) {
            return 'Depasire semnificativa. Recomandare: renegociere furnizori + replanificare buget pe etapa inainte de confirmare.';
        }

        if ($stageOverrunPct >= 5) {
            return 'Risc moderat de depasire. Recomandare: reducere consum si verificare alternativa de achizitie.';
        }

        if ($stageOverrunPct > 0) {
            return 'Depasire minora. Recomandare: continua achizitia cu monitorizare zilnica pe etapa.';
        }

        return 'Achizitia se incadreaza in bugetul etapei. Recomandare: continua conform planului curent.';
    }
}
