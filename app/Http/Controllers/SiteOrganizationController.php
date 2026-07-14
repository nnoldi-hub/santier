<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\SiteStaffPlan;
use App\Models\Team;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
