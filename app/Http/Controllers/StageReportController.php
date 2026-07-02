<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStageReportRequest;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageReport;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StageReportController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StageReport::class, 'stage_report');
    }

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $filters = [
            'q' => $request->string('q')->toString(),
            'project_id' => $request->integer('project_id') > 0 ? $request->integer('project_id') : null,
            'stage_id' => $request->integer('stage_id') > 0 ? $request->integer('stage_id') : null,
            'contractor_id' => $request->integer('contractor_id') > 0 ? $request->integer('contractor_id') : null,
        ];

        $reports = StageReport::query()
            ->with(['stage:id,project_id,name', 'stage.project:id,name', 'contractor:id,name', 'creator:id,name'])
            ->whereHas('stage.project', fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('activities', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('issues', 'like', '%' . $filters['q'] . '%');
                });
            })
            ->when($filters['project_id'], fn ($query, $value) => $query->whereHas('stage', fn ($q) => $q->where('project_id', $value)))
            ->when($filters['stage_id'], fn ($query, $value) => $query->where('stage_id', $value))
            ->when($filters['contractor_id'], fn ($query, $value) => $query->where('contractor_id', $value))
            ->orderByDesc('report_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('StageReports/Index', [
            'reports' => $reports,
            'filters' => $filters,
            'projects' => Project::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        return Inertia::render('StageReports/Create', [
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreStageReportRequest $request): RedirectResponse
    {
        StageReport::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('stage-reports.index')->with('success', 'Raportul de etapa a fost salvat.');
    }

    public function edit(Request $request, StageReport $stage_report): Response
    {
        $tenantId = TenantContext::id($request->user());

        return Inertia::render('StageReports/Edit', [
            'report' => $stage_report,
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(StoreStageReportRequest $request, StageReport $stage_report): RedirectResponse
    {
        $stage_report->update($request->validated());

        return redirect()->route('stage-reports.index')->with('success', 'Raportul de etapa a fost actualizat.');
    }

    public function destroy(StageReport $stage_report): RedirectResponse
    {
        $stage_report->delete();

        return redirect()->route('stage-reports.index')->with('success', 'Raportul de etapa a fost sters.');
    }
}
