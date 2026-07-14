<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\SiteContractorPlan;
use App\Models\SiteEquipmentPlan;
use App\Models\SiteMaterialPlan;
use App\Models\SiteStaffPlan;
use App\Models\StageEquipment;
use App\Models\Team;
use App\Support\EquipmentCostEstimator;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SiteOrganizationController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        return Inertia::render('SiteOrganization/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'phases' => $project->phases()->orderBy('order')->get(['id', 'name', 'order']),
            ],
            'teams' => Team::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'staffPlans' => SiteStaffPlan::where('project_id', $project->id)
                ->with(['phase:id,name', 'team:id,name', 'contractor:id,name'])
                ->latest('id')
                ->get(),
            'riskLevels' => SiteStaffPlan::$riskLabels,
            'contractorPlans' => $this->contractorPlansWithOverlap($project),
            'contractStatusLabels' => SiteContractorPlan::$contractStatusLabels,
            'availabilityLabels' => SiteContractorPlan::$availabilityLabels,
            'materialPlans' => SiteMaterialPlan::where('project_id', $project->id)
                ->with(['phase:id,name', 'material:id,name,code,unit'])
                ->latest('id')
                ->get(),
            'materials' => Material::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'code', 'unit']),
            'materialRiskLevels' => SiteMaterialPlan::$riskLabels,
            'equipmentPlans' => $this->equipmentPlansWithEstimates($project),
            'equipmentCatalog' => Equipment::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'type', 'cost_per_hour', 'availability_status']),
            'equipmentRiskLevels' => SiteEquipmentPlan::$riskLabels,
        ]);
    }

    public function storeStaffPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

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

        $validated = $this->validateStaffPlan($request, $project);

        $staffPlan->update($validated);

        return back()->with('success', 'Planul de personal a fost actualizat.');
    }

    public function destroyStaffPlan(Request $request, Project $project, SiteStaffPlan $staffPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $staffPlan->project_id === $project->id, 404);

        $staffPlan->delete();

        return back()->with('success', 'Planul de personal a fost sters.');
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

        $validated = $this->validateContractorPlan($request, $project);

        $contractorPlan->update($validated);

        return back()->with('success', 'Planul de subcontractor a fost actualizat.');
    }

    public function destroyContractorPlan(Request $request, Project $project, SiteContractorPlan $contractorPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $contractorPlan->project_id === $project->id, 404);

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

        $validated = $this->validateMaterialPlan($request, $project);

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

        $validated = $this->validateMaterialPlan($request, $project);

        $materialPlan->update($validated);

        return back()->with('success', 'Planul de material a fost actualizat.');
    }

    public function destroyMaterialPlan(Request $request, Project $project, SiteMaterialPlan $materialPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $materialPlan->project_id === $project->id, 404);

        $materialPlan->delete();

        return back()->with('success', 'Planul de material a fost sters.');
    }

    private function validateMaterialPlan(Request $request, Project $project): array
    {
        $tenantId = TenantContext::id($request->user());

        return $request->validate([
            'phase_id' => ['nullable', 'integer', Rule::exists('project_phases', 'id')->where('project_id', $project->id)],
            'material_id' => ['required', 'integer', Rule::exists('materials', 'id')->where('tenant_id', $tenantId)],
            'planned_quantity' => ['required', 'numeric', 'min:0'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'planned_order_date' => ['nullable', 'date'],
            'planned_delivery_date' => ['nullable', 'date'],
            'risk_level' => ['required', 'in:' . implode(',', array_keys(SiteMaterialPlan::$riskLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    public function storeEquipmentPlan(Request $request, Project $project): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);

        $validated = $this->validateEquipmentPlan($request, $project);

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

        $validated = $this->validateEquipmentPlan($request, $project);

        $equipmentPlan->update($validated);

        return back()->with('success', 'Planul de utilaj a fost actualizat.');
    }

    public function destroyEquipmentPlan(Request $request, Project $project, SiteEquipmentPlan $equipmentPlan): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $project->tenant_id === $tenantId, 404);
        abort_unless((int) $equipmentPlan->project_id === $project->id, 404);

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
            'usage_start' => ['nullable', 'date'],
            'usage_end' => ['nullable', 'date'],
            'risk_level' => ['required', 'in:' . implode(',', array_keys(SiteEquipmentPlan::$riskLabels))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
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
