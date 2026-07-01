<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Document;
use App\Models\MaterialInvoice;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectAiToolsController extends Controller
{
    public function budgetAlert(Request $request, Project $project): JsonResponse
    {
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
            ->where('tenant_id', 1)
            ->where('project_id', $project->id)
            ->where('stage_id', $stage->id)
            ->sum('amount');

        $currentStageMaterialsCost = (float) MaterialInvoice::query()
            ->where('tenant_id', 1)
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
            ->where('tenant_id', 1)
            ->where('project_id', $project->id)
            ->sum('amount');

        $currentProjectMaterialsCost = (float) MaterialInvoice::query()
            ->where('tenant_id', 1)
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

        $supplierGuess = $this->guessSupplierFromFilename($originalName);
        $amountGuess = $this->guessAmountFromText($originalName);
        $vatGuess = $amountGuess > 0 ? round($amountGuess * 0.19, 2) : null;

        return response()->json([
            'message' => 'Factura a fost prelucrata. Verifica si confirma datele extrase.',
            'draft' => [
                'temp_file_path' => $tempPath,
                'file_name' => $originalName,
                'stage_id' => $validated['stage_id'],
                'supplier_name' => $supplierGuess,
                'amount' => $amountGuess,
                'vat_amount' => $vatGuess,
                'issued_at' => now()->toDateString(),
                'payment_status' => 'unpaid',
                'title' => 'Factura ' . $supplierGuess,
                'notes' => 'Draft AI generat automat. Verifica datele inainte de confirmare.',
                'confidence' => [
                    'supplier_name' => 0.62,
                    'amount' => $amountGuess > 0 ? 0.58 : 0.35,
                    'vat_amount' => $amountGuess > 0 ? 0.45 : 0.2,
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
            'file_path' => $finalPath,
            'file_name' => basename($finalPath),
            'mime_type' => null,
            'file_size' => Storage::disk('local')->size($finalPath),
            'notes' => trim(($validated['notes'] ?? '') . "\nTVA estimat: " . number_format((float) ($validated['vat_amount'] ?? 0), 2, '.', '')),
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
