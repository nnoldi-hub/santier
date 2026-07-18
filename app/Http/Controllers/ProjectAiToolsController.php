<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Document;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
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

        $validated = $request->validate([
            'task_template_id' => ['required', 'integer', Rule::exists('task_templates', 'id')->where('tenant_id', $tenantId)],
            'measure_value' => ['required', 'numeric', 'min:0.1'],
            'complexity' => ['nullable', 'in:low,medium,high'],
            'labor_unit_cost' => ['nullable', 'numeric', 'min:0'],
            'equipment_unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $template = TaskTemplate::with('recipe.items.material')->findOrFail($validated['task_template_id']);
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
        $laborUnitCost = (float) ($validated['labor_unit_cost'] ?? 0);
        $equipmentUnitCost = (float) ($validated['equipment_unit_cost'] ?? 0);

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

        $materialsCost = round($materials->sum('estimated_cost'), 2);
        $laborCost = round($laborUnitCost * $measureValue * $complexityFactor, 2);
        $equipmentCost = round($equipmentUnitCost * $measureValue * $complexityFactor, 2);
        $subtotal = round($materialsCost + $laborCost + $equipmentCost, 2);

        $wbsStages = collect(['Pregatire', 'Aprovizionare materiale', "Executie - {$template->title}", 'Control calitate', 'Predare'])
            ->map(fn (string $name) => ['name' => $name, 'status' => 'pending'])
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
                    'unit_cost' => $laborUnitCost,
                    'estimated_cost' => $laborCost,
                ],
                'equipment' => [
                    'unit_cost' => $equipmentUnitCost,
                    'estimated_cost' => $equipmentCost,
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
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'total_net' => ['required', 'numeric', 'min:0'],
            'wbs_stages' => ['required', 'array', 'min:1'],
            'wbs_stages.*.name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'estimate_details' => ['nullable', 'array'],
            'estimate_details.task_template_id' => ['nullable', 'integer'],
            'estimate_details.task_template_title' => ['nullable', 'string', 'max:255'],
            'estimate_details.recipe_unit' => ['nullable', 'string', 'max:50'],
            'estimate_details.measure_value' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.complexity' => ['nullable', 'string', 'max:50'],
            'estimate_details.materials' => ['nullable', 'array'],
            'estimate_details.materials.*.name' => ['nullable', 'string', 'max:255'],
            'estimate_details.materials.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.materials.*.unit' => ['nullable', 'string', 'max:50'],
            'estimate_details.materials.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.materials.*.estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.labor' => ['nullable', 'array'],
            'estimate_details.labor.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.labor.estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.equipment' => ['nullable', 'array'],
            'estimate_details.equipment.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'estimate_details.equipment.estimated_cost' => ['nullable', 'numeric', 'min:0'],
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
            'tenant_id' => 1,
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

        foreach ($validated['wbs_stages'] as $index => $stageInput) {
            $name = trim($stageInput['name']);
            if ($name === '') {
                continue;
            }

            $exists = ProjectPhase::query()
                ->where('project_id', $project->id)
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            $maxOrder += 1;

            $stage = ProjectPhase::create([
                'project_id' => $project->id,
                'name' => $name,
                'type' => 'custom',
                'status' => 'pending',
                'progress_pct' => 0,
                'order' => $maxOrder,
                'notes' => 'Etapa propusa automat de AI Tools.',
            ]);

            $createdStages[] = [
                'id' => $stage->id,
                'name' => $stage->name,
                'position' => $index + 1,
            ];

            if ($estimateStageId === null) {
                $estimateStageId = $stage->id;
            }
        }

        if ($estimateStageId === null) {
            $estimateStageId = $project->phases()->value('id');
        }

        $estimateDocument = Document::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'stage_id' => $estimateStageId,
            'type' => 'estimate',
            'amount' => $totalNet,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? 'Deviz generat automat din modul AI Tools.',
        ]);

        return response()->json([
            'message' => 'Devizul a fost salvat ca oferta draft, document de tip deviz si etapele WBS au fost adaugate in proiect.',
            'quote_id' => $quote->id,
            'document_id' => $estimateDocument->id,
            'created_stages' => $createdStages,
        ]);
    }

    public function budgetAlert(Request $request, Project $project): JsonResponse
    {
        $tenantId = TenantContext::id($request->user());

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
                'tenant_id' => 1,
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
            'tenant_id' => 1,
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
