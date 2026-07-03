<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualityCheckRequest;
use App\Models\Project;
use App\Models\QualityCheck;
use App\Models\StageTask;
use App\Models\User;
use App\Support\QualityCheckAutoStatus;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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
        $tenantId = TenantContext::id($request->user());
        $status = $request->string('status')->toString();
        $type = $request->string('check_type')->toString();
        $projectId = $request->integer('project_id');

        $checks = QualityCheck::query()
            ->with(['project:id,name', 'phase:id,name', 'assignee:id,name'])
            ->where('tenant_id', $tenantId)
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
            'projects' => Project::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'statuses' => QualityCheck::$statusLabels,
            'types' => QualityCheck::$typeLabels,
            'receptionTypes' => QualityCheck::$receptionTypeLabels,
            'aiInsights' => $this->buildAiInsights($tenantId),
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        $projects = Project::where('tenant_id', $tenantId)
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
            'receptionTypes' => QualityCheck::$receptionTypeLabels,
        ]);
    }

    public function store(StoreQualityCheckRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $data = $request->validated();
        $data['checklist'] = $this->normalizeChecklist($data['checklist'] ?? []);
        $data = $this->applyAutoCompletionFromStageTasks($data, null);
        $data['tenant_id'] = $tenantId;
        $data['completed_at'] = $data['status'] === 'passed' ? now() : null;

        $qualityCheck = QualityCheck::create($data);
        QualityCheckAutoStatus::applyForPhase((int) ($qualityCheck->phase_id ?? 0));

        return redirect()->route('quality-checks.index')->with('success', 'Verificarea a fost creata.');
    }

    public function edit(QualityCheck $quality_check): Response
    {
        $tenantId = TenantContext::id(request()->user());

        $projects = Project::where('tenant_id', $tenantId)
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
            'receptionTypes' => QualityCheck::$receptionTypeLabels,
        ]);
    }

    public function update(StoreQualityCheckRequest $request, QualityCheck $quality_check): RedirectResponse
    {
        $data = $request->validated();
        $data['checklist'] = $this->normalizeChecklist($data['checklist'] ?? []);
        $data = $this->applyAutoCompletionFromStageTasks($data, $quality_check);
        $data['completed_at'] = $data['status'] === 'passed'
            ? ($quality_check->completed_at ?? now())
            : null;

        $quality_check->update($data);
        QualityCheckAutoStatus::applyForPhase((int) ($quality_check->phase_id ?? 0));

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

        QualityCheckAutoStatus::applyForPhase((int) ($quality_check->phase_id ?? 0));

        return back()->with('success', 'Status verificare actualizat!');
    }

    public function pdf(QualityCheck $quality_check): HttpResponse
    {
        $this->authorize('view', $quality_check);

        $quality_check->loadMissing([
            'project:id,name',
            'phase:id,name',
            'assignee:id,name',
        ]);

        $unfinishedChecks = QualityCheck::query()
            ->where('tenant_id', $quality_check->tenant_id)
            ->where('phase_id', $quality_check->phase_id)
            ->whereIn('status', ['pending', 'in_progress', 'failed'])
            ->count();

        $aiInsight = $quality_check->phase_id
            ? sprintf('Aceasta etapa are %d verificari nefinalizate.', $unfinishedChecks)
            : 'Verificarea nu este asociata unei etape specifice.';

        $pdf = Pdf::loadView('quality_checks.pdf', [
            'qualityCheck' => $quality_check,
            'statusLabels' => QualityCheck::$statusLabels,
            'aiInsight' => $aiInsight,
        ])->setPaper('a4');

        return $pdf->download(sprintf('raport-calitate-%d.pdf', $quality_check->id));
    }

    private function normalizeChecklist(array $checklist): array
    {
        return collect($checklist)
            ->map(function ($item): ?array {
                $text = trim((string) ($item['text'] ?? ''));

                if ($text === '') {
                    return null;
                }

                return [
                    'text' => $text,
                    'done' => (bool) ($item['done'] ?? false),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function applyAutoCompletionFromStageTasks(array $data, ?QualityCheck $existingCheck): array
    {
        $phaseId = (int) ($data['phase_id'] ?? $existingCheck?->phase_id ?? 0);

        if ($phaseId <= 0) {
            return $data;
        }

        if (! in_array($data['status'] ?? '', ['pending', 'in_progress'], true)) {
            return $data;
        }

        $totalTasks = StageTask::query()->where('stage_id', $phaseId)->count();
        $openTasks = StageTask::query()
            ->where('stage_id', $phaseId)
            ->whereIn('status', ['todo', 'in_progress', 'blocked'])
            ->count();

        if ($totalTasks > 0 && $openTasks === 0) {
            $data['status'] = 'passed';
            $note = 'Status automat: verificarea a fost setata pe Conform pentru ca toate taskurile etapei sunt inchise.';
            $notes = trim((string) ($data['notes'] ?? $existingCheck?->notes ?? ''));
            $data['notes'] = $notes === '' ? $note : ($notes . "\n" . $note);
        }

        return $data;
    }

    private function buildAiInsights(int $tenantId): array
    {
        return QualityCheck::query()
            ->with(['phase:id,name,project_id', 'phase.project:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('phase_id')
            ->whereIn('status', ['pending', 'in_progress', 'failed'])
            ->get(['id', 'phase_id', 'status'])
            ->groupBy('phase_id')
            ->map(function ($rows, $phaseId) {
                $first = $rows->first();

                return [
                    'phase_id' => (int) $phaseId,
                    'phase_name' => $first?->phase?->name ?? 'Etapa',
                    'project_name' => $first?->phase?->project?->name,
                    'unfinished_checks' => $rows->count(),
                    'message' => sprintf('Aceasta etapa are %d verificari nefinalizate.', $rows->count()),
                    'url' => route('quality-checks.index', ['project_id' => $first?->phase?->project_id]),
                ];
            })
            ->sortByDesc('unfinished_checks')
            ->take(5)
            ->values()
            ->all();
    }
}
