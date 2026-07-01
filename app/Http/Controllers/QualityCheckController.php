<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualityCheckRequest;
use App\Models\Project;
use App\Models\QualityCheck;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QualityCheckController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(QualityCheck::class, 'quality_check');
    }

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $type = $request->string('check_type')->toString();
        $projectId = $request->integer('project_id');

        $checks = QualityCheck::query()
            ->with(['project:id,name', 'phase:id,name', 'assignee:id,name'])
            ->where('tenant_id', 1)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('check_type', $type))
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 1 WHEN status = 'in_progress' THEN 2 WHEN status = 'failed' THEN 3 WHEN status = 'passed' THEN 4 ELSE 5 END")
            ->orderBy('planned_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('QualityChecks/Index', [
            'checks' => $checks,
            'filters' => [
                'status' => $status,
                'check_type' => $type,
                'project_id' => $projectId > 0 ? $projectId : '',
            ],
            'projects' => Project::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
            'statuses' => QualityCheck::$statusLabels,
            'types' => QualityCheck::$typeLabels,
        ]);
    }

    public function create(Request $request): Response
    {
        $projects = Project::where('tenant_id', 1)
            ->with(['phases:id,project_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $projectId = $request->integer('project_id');

        return Inertia::render('QualityChecks/Create', [
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'selectedProjectId' => $projectId > 0 ? $projectId : null,
            'phasesByProject' => $projects->mapWithKeys(fn ($project) => [
                $project->id => $project->phases->map(fn ($phase) => [
                    'id' => $phase->id,
                    'name' => $phase->name,
                ])->values(),
            ]),
            'statuses' => QualityCheck::$statusLabels,
            'types' => QualityCheck::$typeLabels,
        ]);
    }

    public function store(StoreQualityCheckRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = 1;
        $data['completed_at'] = $data['status'] === 'passed' ? now() : null;

        QualityCheck::create($data);

        return redirect()->route('quality-checks.index')->with('success', 'Verificarea a fost creata.');
    }

    public function edit(QualityCheck $quality_check): Response
    {
        $projects = Project::where('tenant_id', 1)
            ->with(['phases:id,project_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('QualityChecks/Edit', [
            'qualityCheck' => $quality_check,
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'phasesByProject' => $projects->mapWithKeys(fn ($project) => [
                $project->id => $project->phases->map(fn ($phase) => [
                    'id' => $phase->id,
                    'name' => $phase->name,
                ])->values(),
            ]),
            'statuses' => QualityCheck::$statusLabels,
            'types' => QualityCheck::$typeLabels,
        ]);
    }

    public function update(StoreQualityCheckRequest $request, QualityCheck $quality_check): RedirectResponse
    {
        $data = $request->validated();
        $data['completed_at'] = $data['status'] === 'passed'
            ? ($quality_check->completed_at ?? now())
            : null;

        $quality_check->update($data);

        return redirect()->route('quality-checks.index')->with('success', 'Verificarea a fost actualizata.');
    }

    public function destroy(QualityCheck $quality_check): RedirectResponse
    {
        $quality_check->delete();

        return redirect()->route('quality-checks.index')->with('success', 'Verificarea a fost stearsa.');
    }

    public function updateStatus(Request $request, QualityCheck $quality_check): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,passed,failed'],
        ]);

        $quality_check->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'passed' ? ($quality_check->completed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Status verificare actualizat!');
    }
}
