<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQualityCheckRequest;
use App\Models\Project;
use App\Models\QualityCheck;
use App\Models\QualityCheckPhoto;
use App\Models\Recipe;
use App\Models\StageTask;
use App\Models\User;
use App\Support\DocumentBranding;
use App\Support\ExportAudit;
use App\Support\QualityCheckAutoStatus;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'users' => User::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
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
            'recipes' => $this->recipesWithChecklist($tenantId),
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
        $data = $this->persistSignature($data, null);
        unset($data['photos']);

        $qualityCheck = QualityCheck::create($data);
        $this->persistPhotos($qualityCheck, $request, $tenantId);
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

        $quality_check->load('photos');

        return Inertia::render('QualityChecks/Edit', [
            'qualityCheck' => $quality_check,
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'users' => User::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'phasesByProject' => $projects->mapWithKeys(fn ($project) => [
                $project->id => $project->phases->map(fn ($phase) => [
                    'id' => $phase->id,
                    'name' => $phase->name,
                ])->values(),
            ]),
            'statuses' => QualityCheck::$statusLabels,
            'types' => QualityCheck::$typeLabels,
            'receptionTypes' => QualityCheck::$receptionTypeLabels,
            'recipes' => $this->recipesWithChecklist($tenantId),
            'photos' => $quality_check->photos->map(fn (QualityCheckPhoto $photo) => [
                'id' => $photo->id,
                'name' => $photo->name,
                'url' => Storage::url($photo->path),
            ]),
        ]);
    }

    public function update(StoreQualityCheckRequest $request, QualityCheck $quality_check): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $data = $request->validated();
        $data['checklist'] = $this->normalizeChecklist($data['checklist'] ?? []);
        $data = $this->applyAutoCompletionFromStageTasks($data, $quality_check);
        $data['completed_at'] = $data['status'] === 'passed'
            ? ($quality_check->completed_at ?? now())
            : null;
        $data = $this->persistSignature($data, $quality_check);
        unset($data['photos']);

        $quality_check->update($data);
        $this->persistPhotos($quality_check, $request, $tenantId);
        QualityCheckAutoStatus::applyForPhase((int) ($quality_check->phase_id ?? 0));

        return redirect()->route('quality-checks.index')->with('success', 'Verificarea a fost actualizata.');
    }

    public function destroy(QualityCheck $quality_check): RedirectResponse
    {
        foreach ($quality_check->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        if ($quality_check->signature_path) {
            Storage::disk('public')->delete($quality_check->signature_path);
        }

        $quality_check->delete();

        return redirect()->route('quality-checks.index')->with('success', 'Verificarea a fost stearsa.');
    }

    public function destroyPhoto(QualityCheck $quality_check, QualityCheckPhoto $quality_check_photo): RedirectResponse
    {
        $this->authorize('update', $quality_check);
        abort_unless((int) $quality_check_photo->quality_check_id === $quality_check->id, 404);

        Storage::disk('public')->delete($quality_check_photo->path);
        $quality_check_photo->delete();

        return back()->with('success', 'Poza a fost stearsa.');
    }

    public function projectReport(Request $request): HttpResponse
    {
        $this->authorize('viewAny', QualityCheck::class);

        $tenantId = TenantContext::id($request->user());
        $project = Project::where('tenant_id', $tenantId)->findOrFail($request->integer('project_id'));

        $checks = QualityCheck::query()
            ->with(['phase:id,name', 'assignee:id,name', 'photos'])
            ->where('tenant_id', $tenantId)
            ->where('project_id', $project->id)
            ->orderBy('planned_at')
            ->get();

        $summary = [
            'total' => $checks->count(),
            'passed' => $checks->where('status', 'passed')->count(),
            'failed' => $checks->where('status', 'failed')->count(),
            'pending' => $checks->whereIn('status', ['pending', 'in_progress'])->count(),
        ];

        $branding = DocumentBranding::resolve($tenantId);
        $fileName = sprintf('raport-calitate-%s.pdf', Str::slug($project->name));

        ExportAudit::log('quality-report-pdf', 'pdf', ['project_id' => $project->id], [
            'file_name' => $fileName,
        ]);

        $pdf = Pdf::loadView('quality_checks.project-report', [
            'project' => $project,
            'checks' => $checks,
            'summary' => $summary,
            'statusLabels' => QualityCheck::$statusLabels,
            'branding' => $branding,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4');

        return $pdf->stream($fileName);
    }

    public function updateStatus(Request $request, QualityCheck $quality_check): RedirectResponse
    {
        $this->authorize('update', $quality_check);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,in_progress,passed,failed'],
        ]);

        if (in_array($validated['status'], ['passed', 'failed'], true) && $quality_check->photos()->doesntExist()) {
            return back()->with('error', 'Este necesara cel putin o poza pentru a finaliza verificarea. Editeaza verificarea si adauga o poza.');
        }

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
            'photos',
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

        return $pdf->stream(sprintf('raport-calitate-%d.pdf', $quality_check->id));
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

    private function persistSignature(array $data, ?QualityCheck $existing): array
    {
        $dataUrl = $data['signature_data_url'] ?? null;
        unset($data['signature_data_url']);

        if (empty($dataUrl) || !preg_match('/^data:image\/(png|jpe?g);base64,(.+)$/', $dataUrl, $matches)) {
            return $data;
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            return $data;
        }

        if ($existing?->signature_path) {
            Storage::disk('public')->delete($existing->signature_path);
        }

        $extension = $matches[1] === 'jpg' ? 'jpeg' : $matches[1];
        $path = 'quality-checks/signatures/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $binary);

        $data['signature_path'] = $path;
        $data['signed_at'] = now();

        return $data;
    }

    private function persistPhotos(QualityCheck $qualityCheck, Request $request, int $tenantId): void
    {
        foreach ($request->file('photos', []) as $file) {
            if ($file === null) {
                continue;
            }

            QualityCheckPhoto::create([
                'tenant_id' => $tenantId,
                'quality_check_id' => $qualityCheck->id,
                'path' => $file->store('quality-checks/photos', 'public'),
                'name' => $file->getClientOriginalName(),
            ]);
        }
    }

    private function recipesWithChecklist(int $tenantId): array
    {
        return Recipe::where('tenant_id', $tenantId)
            ->whereNotNull('default_checklist')
            ->orderBy('name')
            ->get(['id', 'name', 'default_checklist'])
            ->filter(fn (Recipe $recipe) => !empty($recipe->default_checklist))
            ->map(fn (Recipe $recipe) => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'default_checklist' => $recipe->default_checklist,
            ])
            ->values()
            ->all();
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
