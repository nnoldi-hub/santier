<?php

namespace App\Http\Controllers;

use App\Exports\SitePlanningWorkbookExport;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Recipe;
use App\Models\ResourceOrder;
use App\Models\SiteBudgetPlan;
use App\Models\SiteCompliancePlan;
use App\Models\SiteContractorPlan;
use App\Models\SiteEquipmentPlan;
use App\Models\SiteLogisticsPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SitePlanApproval;
use App\Models\SiteStaffPlan;
use App\Models\SiteStaffTimeEntry;
use App\Models\StageEquipment;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\Team;
use App\Support\DocumentBranding;
use App\Support\EquipmentCostEstimator;
use App\Support\ExportAudit;
use App\Support\LaborCostEstimator;
use App\Support\SitePlanningAIAdvisor;
use App\Support\SitePlanningExporter;
use App\Support\SiteReadinessCalculator;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class SiteOrganizationController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $data = $this->gatherPlanningData($project);

        return Inertia::render('SiteOrganization/Index', array_merge([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'total_budget' => $project->total_budget,
                'phases' => $data['phases'],
                'plan_approved_at' => $project->plan_approved_at?->toDateTimeString(),
                'plan_approved_by_name' => $project->planApprovedBy?->name,
            ],
            'teams' => Team::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'riskLevels' => SiteStaffPlan::$riskLabels,
            'contractStatusLabels' => SiteContractorPlan::$contractStatusLabels,
            'availabilityLabels' => SiteContractorPlan::$availabilityLabels,
            'materials' => Material::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'code', 'unit', 'unit_price']),
            'suppliers' => Supplier::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'materialRiskLevels' => SiteMaterialPlan::$riskLabels,
            'recipes' => Recipe::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'unit']),
            'equipmentCatalog' => Equipment::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'type', 'cost_per_hour', 'availability_status']),
            'equipmentRiskLevels' => SiteEquipmentPlan::$riskLabels,
            'logisticsCategories' => SiteLogisticsPlan::$categoryLabels,
            'logisticsRiskLevels' => SiteLogisticsPlan::$riskLabels,
            'complianceItemTypeLabels' => SiteCompliancePlan::$itemTypeLabels,
            'complianceStatusLabels' => SiteCompliancePlan::$statusLabels,
            'budgetCategories' => SiteBudgetPlan::$categoryLabels,
        ], $data));
    }

    public function exportPdf(Request $request, Project $project)
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $data = $this->gatherPlanningData($project);
        $sections = SitePlanningExporter::buildSections($data);
        $fileName = 'plan-organizare-' . Str::slug($project->name) . '-' . now()->format('Ymd_His') . '.pdf';

        $branding = DocumentBranding::resolve($tenantId);

        ExportAudit::log('site-planning-pdf', 'pdf', ['project_id' => $project->id], [
            'file_name' => $fileName,
        ]);

        return Pdf::loadView('exports.managerial-pdf', [
            'title' => 'Plan organizare santier - ' . $project->name,
            'branding' => [
                'company_name' => $branding['company_name'] ?? config('exports.company_name'),
                'company_email' => $branding['support_email'] ?? config('exports.company_email'),
                'company_phone' => $branding['company_phone'] ?? config('exports.company_phone'),
                'company_address' => $branding['company_address'] ?? '',
                'document_logo_url' => $branding['document_logo_url'] ?? '',
                'brand_color' => $branding['document_brand_color'] ?? config('exports.brand_color'),
                'white_label' => $branding['white_label'],
            ],
            'generatedAt' => now()->toDateTimeString(),
            'filters' => ['project' => $project->name],
            'sections' => $sections,
        ])->setOptions([
            'isRemoteEnabled' => true,
        ])->setPaper('a4')->download($fileName);
    }

    public function exportXlsx(Request $request, Project $project)
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $data = $this->gatherPlanningData($project);
        $sections = SitePlanningExporter::buildSections($data);
        $fileName = 'plan-organizare-' . Str::slug($project->name) . '-' . now()->format('Ymd_His') . '.xlsx';

        ExportAudit::log('site-planning-xlsx', 'xlsx', ['project_id' => $project->id], [
            'file_name' => $fileName,
        ]);

        return Excel::download(new SitePlanningWorkbookExport($sections), $fileName);
    }

    public function approvePlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_if($project->plan_approved_at !== null, 422, 'Planul este deja aprobat.');

        $data = $this->gatherPlanningData($project);
        $userId = $request->user()->id;

        DB::transaction(function () use ($project, $tenantId, $userId, $data) {
            $tasksCreated = 0;

            foreach ($data['staffPlans'] as $plan) {
                Task::create([
                    'tenant_id' => $tenantId,
                    'project_id' => $project->id,
                    'phase_id' => $plan->phase_id,
                    'assigned_to' => null,
                    'created_by' => $userId,
                    'title' => 'Aloca personal: ' . $plan->specialty . ' (' . $plan->planned_headcount . ' persoane)',
                    'description' => 'Responsabil planificat: ' . ($plan->team?->name ?? $plan->contractor?->name ?? 'nespecificat'),
                    'status' => 'todo',
                    'priority' => 'medium',
                    'deadline' => $plan->planned_start,
                ]);
                $tasksCreated++;
            }

            $contractorsAssigned = 0;

            foreach ($data['contractorPlans'] as $plan) {
                if ($plan->contract_status === 'signed' && $plan->phase_id) {
                    ProjectPhase::where('id', $plan->phase_id)->update(['contractor_id' => $plan->contractor_id]);
                    $contractorsAssigned++;
                }
            }

            $ordersCreated = 0;

            foreach ($data['materialPlans'] as $plan) {
                ResourceOrder::create([
                    'tenant_id' => $tenantId,
                    'project_id' => $project->id,
                    'phase_id' => $plan->phase_id,
                    'resource_type' => 'material',
                    'material_id' => $plan->material_id,
                    'supplier_id' => $plan->supplier_id,
                    'supplier_name' => $plan->supplier_name,
                    'ordered_quantity' => $plan->planned_quantity,
                    'ordered_unit' => $plan->material?->unit,
                    'unit_price' => $plan->unit_price ?? 0,
                    'delivery_date' => $plan->planned_delivery_date,
                    'responsible_user_id' => $userId,
                    'status' => 'draft',
                    'notes' => $plan->notes,
                ]);
                $ordersCreated++;
            }

            $reservationsCreated = 0;

            foreach ($data['equipmentPlans'] as $plan) {
                if ($plan->phase_id) {
                    StageEquipment::create([
                        'stage_id' => $plan->phase_id,
                        'equipment_id' => $plan->equipment_id,
                        'quantity' => $plan->quantity,
                        'usage_start' => $plan->usage_start,
                        'usage_end' => $plan->usage_end,
                        'notes' => $plan->notes,
                    ]);
                    $reservationsCreated++;
                }
            }

            if (empty($project->total_budget)) {
                $project->total_budget = $data['budgetSummary']['total_estimated'];
            }

            $project->plan_approved_at = now();
            $project->plan_approved_by = $userId;
            $project->save();

            SitePlanApproval::create([
                'tenant_id' => $tenantId,
                'project_id' => $project->id,
                'user_id' => $userId,
                'action' => 'approved',
                'notes' => "Generat: {$tasksCreated} sarcini personal, {$contractorsAssigned} alocari subcontractori, {$ordersCreated} comenzi materiale, {$reservationsCreated} rezervari utilaje.",
            ]);
        });

        return back()->with('success', 'Planul a fost aprobat si s-au generat elementele de executie.');
    }

    public function unapprovePlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_if($project->plan_approved_at === null, 422, 'Planul nu este aprobat.');

        $project->plan_approved_at = null;
        $project->plan_approved_by = null;
        $project->save();

        SitePlanApproval::create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'action' => 'unapproved',
        ]);

        return back()->with('success', 'Aprobarea planului a fost anulata. Editarea este din nou permisa.');
    }

    private function abortIfPlanLocked(Project $project): void
    {
        abort_if($project->plan_approved_at !== null, 423, 'Planul a fost aprobat si editarea este blocata.');
    }

    private function gatherPlanningData(Project $project): array
    {
        $staffPlans = $this->staffPlansWithEstimates($project);

        $contractorPlans = $this->contractorPlansWithOverlap($project);

        $materialPlans = $this->materialPlansWithEstimates($project);

        $equipmentPlans = $this->equipmentPlansWithEstimates($project);

        $logisticsPlans = SiteLogisticsPlan::where('project_id', $project->id)
            ->with('phase:id,name')
            ->latest('id')
            ->get();

        $compliancePlans = SiteCompliancePlan::where('project_id', $project->id)
            ->with(['phase:id,name', 'contractor:id,name'])
            ->latest('id')
            ->get();

        $budgetPlans = SiteBudgetPlan::where('project_id', $project->id)
            ->with('phase:id,name')
            ->latest('id')
            ->get();

        $budgetSummary = $this->buildBudgetSummary($project, $staffPlans, $materialPlans, $equipmentPlans);

        $phases = $project->phases()->orderBy('order')->get(['id', 'name', 'order', 'type', 'duration_days']);

        return [
            'phases' => $phases,
            'staffPlans' => $staffPlans,
            'contractorPlans' => $contractorPlans,
            'materialPlans' => $materialPlans,
            'equipmentPlans' => $equipmentPlans,
            'logisticsPlans' => $logisticsPlans,
            'compliancePlans' => $compliancePlans,
            'budgetPlans' => $budgetPlans,
            'budgetSummary' => $budgetSummary,
            'readiness' => SiteReadinessCalculator::calculate(
                $staffPlans,
                $contractorPlans,
                $materialPlans,
                $equipmentPlans,
                $logisticsPlans,
                $compliancePlans,
                $budgetSummary
            ),
            'aiSuggestions' => SitePlanningAIAdvisor::suggest($phases, $staffPlans, $materialPlans),
        ];
    }

    public function storeStaffPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateStaffPlan($request, $project);

        SiteStaffPlan::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Planul de personal a fost adaugat.');
    }

    public function updateStaffPlan(Request $request, Project $project, SiteStaffPlan $staffPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $staffPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateStaffPlan($request, $project);

        $staffPlan->update($validated);

        return back()->with('success', 'Planul de personal a fost actualizat.');
    }

    public function destroyStaffPlan(Request $request, Project $project, SiteStaffPlan $staffPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $staffPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $staffPlan->delete();

        return back()->with('success', 'Planul de personal a fost sters.');
    }

    public function storeTimeEntry(Request $request, Project $project, SiteStaffPlan $staffPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $staffPlan->project_id === $project->id, 404);

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'hours_worked' => ['required', 'numeric', 'min:0.1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        SiteStaffTimeEntry::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'staff_plan_id' => $staffPlan->id,
        ]);

        return back()->with('success', 'Pontajul a fost inregistrat.');
    }

    public function destroyTimeEntry(Request $request, Project $project, SiteStaffPlan $staffPlan, SiteStaffTimeEntry $timeEntry): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $staffPlan->project_id === $project->id, 404);
        abort_unless((int) $timeEntry->staff_plan_id === $staffPlan->id, 404);

        $timeEntry->delete();

        return back()->with('success', 'Inregistrarea de pontaj a fost stearsa.');
    }

    private function validateStaffPlan(Request $request, Project $project): array
    {
        $tenantId = TenantContext::id($request->user());

        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'team_id' => ['nullable', 'integer', Rule::exists('teams', 'id')->where('tenant_id', $tenantId)],
            'contractor_id' => ['nullable', 'integer', Rule::exists('contractors', 'id')->where('tenant_id', $tenantId)],
            'specialty' => ['required', 'string', 'max:120'],
            'planned_headcount' => ['required', 'integer', 'min:1'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'planned_start' => ['nullable', 'date'],
            'planned_end' => ['nullable', 'date'],
            'risk_level' => ['required', 'in:' . implode(',', array_keys(SiteStaffPlan::$riskLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function storeContractorPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateContractorPlan($request, $project);

        SiteContractorPlan::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Planul de subcontractor a fost adaugat.');
    }

    public function updateContractorPlan(Request $request, Project $project, SiteContractorPlan $contractorPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $contractorPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateContractorPlan($request, $project);

        $contractorPlan->update($validated);

        return back()->with('success', 'Planul de subcontractor a fost actualizat.');
    }

    public function destroyContractorPlan(Request $request, Project $project, SiteContractorPlan $contractorPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $contractorPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $contractorPlan->delete();

        return back()->with('success', 'Planul de subcontractor a fost sters.');
    }

    private function validateContractorPlan(Request $request, Project $project): array
    {
        $tenantId = TenantContext::id($request->user());

        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'contractor_id' => ['required', 'integer', Rule::exists('contractors', 'id')->where('tenant_id', $tenantId)],
            'contract_status' => ['required', 'in:' . implode(',', array_keys(SiteContractorPlan::$contractStatusLabels))],
            'availability_status' => ['required', 'in:' . implode(',', array_keys(SiteContractorPlan::$availabilityLabels))],
            'planned_start' => ['nullable', 'date'],
            'planned_end' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function storeMaterialPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateMaterialPlan($request, $project);

        if (! array_key_exists('unit_price', $validated)) {
            $validated['unit_price'] = Material::find($validated['material_id'])?->unit_price ?? 0;
        }

        $validated = $this->applySupplierSnapshot($validated);
        $validated = $this->applyLeadTimeOrderDate($validated);

        SiteMaterialPlan::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Planul de material a fost adaugat.');
    }

    public function updateMaterialPlan(Request $request, Project $project, SiteMaterialPlan $materialPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $materialPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateMaterialPlan($request, $project);
        $validated = $this->applySupplierSnapshot($validated);
        $validated = $this->applyLeadTimeOrderDate($validated);

        $materialPlan->update($validated);

        return back()->with('success', 'Planul de material a fost actualizat.');
    }

    public function destroyMaterialPlan(Request $request, Project $project, SiteMaterialPlan $materialPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $materialPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $materialPlan->delete();

        return back()->with('success', 'Planul de material a fost sters.');
    }

    public function applyMaterialRecipe(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $request->validate([
            'recipe_id' => ['required', 'integer', Rule::exists('recipes', 'id')->where('tenant_id', $tenantId)],
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'work_quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $recipe = Recipe::with('items.material:id,unit_price')->findOrFail($validated['recipe_id']);

        foreach ($recipe->items as $item) {
            SiteMaterialPlan::create([
                'tenant_id' => $tenantId,
                'project_id' => $project->id,
                'phase_id' => $validated['phase_id'] ?? null,
                'material_id' => $item->material_id,
                'planned_quantity' => round((float) $item->quantity_per_unit * $validated['work_quantity'], 2),
                'unit_price' => $item->material?->unit_price ?? 0,
                'risk_level' => 'medium',
            ]);
        }

        return back()->with('success', 'Planuri de materiale generate din reteta.');
    }

    private function validateMaterialPlan(Request $request, Project $project): array
    {
        $tenantId = TenantContext::id($request->user());

        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'material_id' => ['required', 'integer', Rule::exists('materials', 'id')->where('tenant_id', $tenantId)],
            'planned_quantity' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('tenant_id', $tenantId)],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'planned_order_date' => ['nullable', 'date'],
            'planned_delivery_date' => ['nullable', 'date'],
            'risk_level' => ['required', 'in:' . implode(',', array_keys(SiteMaterialPlan::$riskLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function applySupplierSnapshot(array $validated): array
    {
        if (! empty($validated['supplier_id'])) {
            $validated['supplier_name'] = Supplier::find($validated['supplier_id'])?->name ?? ($validated['supplier_name'] ?? null);
        }

        return $validated;
    }

    private function applyLeadTimeOrderDate(array $validated): array
    {
        if (! array_key_exists('planned_order_date', $validated)
            && ! empty($validated['planned_delivery_date'])
            && ! empty($validated['lead_time_days'])) {
            $validated['planned_order_date'] = Carbon::parse($validated['planned_delivery_date'])
                ->subDays((int) $validated['lead_time_days'])
                ->toDateString();
        }

        return $validated;
    }

    public function storeEquipmentPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateEquipmentPlan($request, $project);

        if (! array_key_exists('hourly_rate', $validated)) {
            $validated['hourly_rate'] = Equipment::find($validated['equipment_id'])?->cost_per_hour ?? 0;
        }

        SiteEquipmentPlan::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Planul de utilaj a fost adaugat.');
    }

    public function updateEquipmentPlan(Request $request, Project $project, SiteEquipmentPlan $equipmentPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $equipmentPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateEquipmentPlan($request, $project);

        $equipmentPlan->update($validated);

        return back()->with('success', 'Planul de utilaj a fost actualizat.');
    }

    public function destroyEquipmentPlan(Request $request, Project $project, SiteEquipmentPlan $equipmentPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $equipmentPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $equipmentPlan->delete();

        return back()->with('success', 'Planul de utilaj a fost sters.');
    }

    private function validateEquipmentPlan(Request $request, Project $project): array
    {
        $tenantId = TenantContext::id($request->user());

        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'equipment_id' => ['required', 'integer', Rule::exists('equipment', 'id')->where('tenant_id', $tenantId)],
            'quantity' => ['required', 'integer', 'min:1'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'usage_start' => ['nullable', 'date'],
            'usage_end' => ['nullable', 'date'],
            'risk_level' => ['required', 'in:' . implode(',', array_keys(SiteEquipmentPlan::$riskLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function staffPlansWithEstimates(Project $project): Collection
    {
        $plans = SiteStaffPlan::where('project_id', $project->id)
            ->with(['phase:id,name', 'team:id,name', 'contractor:id,name', 'timeEntries'])
            ->latest('id')
            ->get();

        return $plans->map(function (SiteStaffPlan $plan) {
            $plan->setAttribute('estimated_hours', LaborCostEstimator::estimatedHours($plan));
            $plan->setAttribute('estimated_cost', LaborCostEstimator::estimate($plan));

            $teamOverlapCount = 0;

            if ($plan->team_id && $plan->planned_start && $plan->planned_end) {
                $teamOverlapCount = PhaseTeamAssignment::where('team_id', $plan->team_id)
                    ->where('start_date', '<=', $plan->planned_end)
                    ->where('end_date', '>=', $plan->planned_start)
                    ->count();
            }

            $plan->setAttribute('team_overlap_count', $teamOverlapCount);

            $actualHours = (float) $plan->timeEntries->sum('hours_worked');
            $plan->setAttribute('actual_hours', $actualHours);
            $plan->setAttribute('actual_cost', round($actualHours * (float) $plan->hourly_rate, 2));

            return $plan;
        });
    }

    private function equipmentPlansWithEstimates(Project $project): Collection
    {
        $plans = SiteEquipmentPlan::where('project_id', $project->id)
            ->with(['phase:id,name', 'equipment:id,name,type,cost_per_hour'])
            ->latest('id')
            ->get();

        return $plans->map(function (SiteEquipmentPlan $plan) {
            $plan->setAttribute('estimated_cost', EquipmentCostEstimator::estimate($plan));
            $plan->setAttribute('reserved_days', EquipmentCostEstimator::reservedDays($plan));

            $reservedElsewhereCount = 0;

            if ($plan->usage_start && $plan->usage_end) {
                $reservedElsewhereCount = StageEquipment::where('equipment_id', $plan->equipment_id)
                    ->where('usage_start', '<=', $plan->usage_end)
                    ->where('usage_end', '>=', $plan->usage_start)
                    ->count();
            }

            $plan->setAttribute('reserved_elsewhere_count', $reservedElsewhereCount);

            return $plan;
        });
    }

    private function materialPlansWithEstimates(Project $project): Collection
    {
        $plans = SiteMaterialPlan::where('project_id', $project->id)
            ->with(['phase:id,name', 'material:id,name,code,unit,unit_price'])
            ->latest('id')
            ->get();

        return $plans->map(function (SiteMaterialPlan $plan) {
            $plan->setAttribute('estimated_cost', round((float) $plan->planned_quantity * (float) ($plan->unit_price ?? 0), 2));

            return $plan;
        });
    }

    public function storeLogisticsPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateLogisticsPlan($request, $project);

        SiteLogisticsPlan::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Planul de logistica a fost adaugat.');
    }

    public function updateLogisticsPlan(Request $request, Project $project, SiteLogisticsPlan $logisticsPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $logisticsPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateLogisticsPlan($request, $project);

        $logisticsPlan->update($validated);

        return back()->with('success', 'Planul de logistica a fost actualizat.');
    }

    public function destroyLogisticsPlan(Request $request, Project $project, SiteLogisticsPlan $logisticsPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $logisticsPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $logisticsPlan->delete();

        return back()->with('success', 'Planul de logistica a fost sters.');
    }

    private function validateLogisticsPlan(Request $request, Project $project): array
    {
        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'category' => ['required', 'in:' . implode(',', array_keys(SiteLogisticsPlan::$categoryLabels))],
            'title' => ['required', 'string', 'max:150'],
            'location_description' => ['nullable', 'string', 'max:255'],
            'capacity_notes' => ['nullable', 'string', 'max:150'],
            'risk_level' => ['required', 'in:' . implode(',', array_keys(SiteLogisticsPlan::$riskLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function storeCompliancePlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateCompliancePlan($request, $project);

        SiteCompliancePlan::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Elementul de conformitate a fost adaugat.');
    }

    public function updateCompliancePlan(Request $request, Project $project, SiteCompliancePlan $compliancePlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $compliancePlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateCompliancePlan($request, $project);

        $compliancePlan->update($validated);

        return back()->with('success', 'Elementul de conformitate a fost actualizat.');
    }

    public function destroyCompliancePlan(Request $request, Project $project, SiteCompliancePlan $compliancePlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $compliancePlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $compliancePlan->delete();

        return back()->with('success', 'Elementul de conformitate a fost sters.');
    }

    private function validateCompliancePlan(Request $request, Project $project): array
    {
        $tenantId = TenantContext::id($request->user());

        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'contractor_id' => ['nullable', 'integer', Rule::exists('contractors', 'id')->where('tenant_id', $tenantId)],
            'item_type' => ['required', 'in:' . implode(',', array_keys(SiteCompliancePlan::$itemTypeLabels))],
            'title' => ['required', 'string', 'max:150'],
            'status' => ['required', 'in:' . implode(',', array_keys(SiteCompliancePlan::$statusLabels))],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function storeBudgetPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateBudgetPlan($request, $project);

        SiteBudgetPlan::create([
            ...$validated,
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
        ]);

        return back()->with('success', 'Linia bugetara a fost adaugata.');
    }

    public function updateBudgetPlan(Request $request, Project $project, SiteBudgetPlan $budgetPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $budgetPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $validated = $this->validateBudgetPlan($request, $project);

        $budgetPlan->update($validated);

        return back()->with('success', 'Linia bugetara a fost actualizata.');
    }

    public function destroyBudgetPlan(Request $request, Project $project, SiteBudgetPlan $budgetPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $budgetPlan->project_id === $project->id, 404);
        $this->abortIfPlanLocked($project);

        $budgetPlan->delete();

        return back()->with('success', 'Linia bugetara a fost stearsa.');
    }

    private function validateBudgetPlan(Request $request, Project $project): array
    {
        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'category' => ['required', 'in:' . implode(',', array_keys(SiteBudgetPlan::$categoryLabels))],
            'description' => ['required', 'string', 'max:200'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function buildBudgetSummary(Project $project, Collection $staffPlans, Collection $materialPlans, Collection $equipmentPlans): array
    {
        $laborCost = $staffPlans->sum(function (SiteStaffPlan $plan) {
            return (float) $plan->estimated_cost;
        });

        $laborCostActual = $staffPlans->sum(function (SiteStaffPlan $plan) {
            return (float) $plan->actual_cost;
        });

        $materialsCost = $materialPlans->sum(function (SiteMaterialPlan $plan) {
            return (float) $plan->estimated_cost;
        });

        $equipmentCost = $equipmentPlans->sum(function (SiteEquipmentPlan $plan) {
            return (float) $plan->estimated_cost;
        });

        // 'labor' is excluded here: once LaborCostEstimator can compute it automatically
        // from SiteStaffPlan, a manual "Manopera" line would double-count it in the total -
        // same reasoning materials/equipment were never offered as manual categories.
        $manualCost = (float) SiteBudgetPlan::where('project_id', $project->id)
            ->where('category', '!=', 'labor')
            ->sum('estimated_cost');

        $totalEstimated = $laborCost + $materialsCost + $equipmentCost + $manualCost;
        $projectBudget = (float) $project->total_budget;

        return [
            'labor_cost' => round($laborCost, 2),
            'labor_cost_actual' => round($laborCostActual, 2),
            'materials_cost' => round($materialsCost, 2),
            'equipment_cost' => round($equipmentCost, 2),
            'manual_cost' => round($manualCost, 2),
            'total_estimated' => round($totalEstimated, 2),
            'project_budget' => round($projectBudget, 2),
            'difference' => round($projectBudget - $totalEstimated, 2),
        ];
    }

    private function contractorPlansWithOverlap(Project $project): Collection
    {
        $plans = SiteContractorPlan::where('project_id', $project->id)
            ->with(['phase:id,name', 'contractor:id,name'])
            ->latest('id')
            ->get();

        $overlapCounts = [];

        return $plans->map(function (SiteContractorPlan $plan) use ($project, &$overlapCounts) {
            $contractorId = (int) $plan->contractor_id;

            if (! array_key_exists($contractorId, $overlapCounts)) {
                $overlapCounts[$contractorId] = ProjectPhase::where('contractor_id', $contractorId)
                    ->where('project_id', '!=', $project->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->distinct()
                    ->count('project_id');
            }

            $plan->setAttribute('parallel_projects_count', $overlapCounts[$contractorId]);

            return $plan;
        });
    }
}
