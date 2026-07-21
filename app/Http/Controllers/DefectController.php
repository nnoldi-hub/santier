<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDefectRequest;
use App\Models\Defect;
use App\Models\DefectPhoto;
use App\Models\Project;
use App\Models\User;
use App\Support\DocumentBranding;
use App\Support\ExportAudit;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DefectController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Defect::class, 'defect');
    }

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $projectId = $request->integer('project_id');

        $defects = Defect::query()
            ->with(['project:id,name', 'phase:id,name', 'reporter:id,name', 'assignee:id,name', 'photos'])
            ->where('tenant_id', $tenantId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($priority !== '', fn ($q) => $q->where('priority', $priority))
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'resolved' THEN 3 WHEN 'rejected' THEN 4 ELSE 5 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->latest('id')
            ->paginate(20)
            ->through(fn (Defect $defect) => [
                'id' => $defect->id,
                'project_id' => $defect->project_id,
                'phase_id' => $defect->phase_id,
                'assigned_to' => $defect->assigned_to,
                'title' => $defect->title,
                'description' => $defect->description,
                'location' => $defect->location,
                'status' => $defect->status,
                'priority' => $defect->priority,
                'due_date' => optional($defect->due_date)?->toDateString(),
                'resolved_at' => optional($defect->resolved_at)?->toDateTimeString(),
                'photo_path' => $defect->photo_path,
                'photo_name' => $defect->photo_name,
                'photo_url' => $defect->photo_path ? Storage::url($defect->photo_path) : null,
                'photos' => $defect->photos->map(fn (DefectPhoto $photo) => [
                    'id' => $photo->id,
                    'name' => $photo->name,
                    'url' => Storage::url($photo->path),
                ]),
                'project' => $defect->project,
                'phase' => $defect->phase,
                'assignee' => $defect->assignee,
                'reporter' => $defect->reporter,
            ])
            ->withQueryString();

        return Inertia::render('Defects/Index', [
            'defects' => $defects,
            'filters' => [
                'status' => $status,
                'priority' => $priority,
                'project_id' => $projectId > 0 ? $projectId : '',
            ],
            'projects' => Project::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
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

        return Inertia::render('Defects/Create', [
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
        ]);
    }

    public function store(StoreDefectRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $data = $request->validated();
        unset($data['photos']);
        $data['tenant_id'] = $tenantId;
        $data['reported_by'] = $request->user()->id;
        $data['resolved_at'] = $data['status'] === 'resolved' ? now() : null;
        $data['resolved_by'] = $data['status'] === 'resolved' ? $request->user()->id : null;
        $data = $this->persistSignature($data, null);

        $defect = Defect::create($data);
        $this->persistPhotos($defect, $request, $tenantId);

        return redirect()->route('defects.index')->with('success', 'Defect creat cu succes!');
    }

    public function edit(Defect $defect): Response
    {
        $tenantId = TenantContext::id(request()->user());

        $projects = Project::where('tenant_id', $tenantId)
            ->with(['phases:id,project_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $defect->load('photos');

        return Inertia::render('Defects/Edit', [
            'defect' => [
                ...$defect->toArray(),
                'photo_url' => $defect->photo_path ? Storage::url($defect->photo_path) : null,
                'photos' => $defect->photos->map(fn (DefectPhoto $photo) => [
                    'id' => $photo->id,
                    'name' => $photo->name,
                    'url' => Storage::url($photo->path),
                ]),
            ],
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
        ]);
    }

    public function update(StoreDefectRequest $request, Defect $defect): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $data = $request->validated();
        unset($data['photos']);
        $data['resolved_at'] = $data['status'] === 'resolved'
            ? ($defect->resolved_at ?? now())
            : null;
        $data['resolved_by'] = $data['status'] === 'resolved'
            ? ($defect->resolved_by ?? $request->user()->id)
            : null;
        $data = $this->persistSignature($data, $defect);

        $defect->update($data);
        $this->persistPhotos($defect, $request, $tenantId);

        return redirect()->route('defects.index')->with('success', 'Defect actualizat!');
    }

    public function destroy(Defect $defect): RedirectResponse
    {
        if ($defect->photo_path) {
            Storage::disk('public')->delete($defect->photo_path);
        }

        foreach ($defect->photos as $photo) {
            Storage::disk('public')->delete($photo->path);
        }

        if ($defect->signature_path) {
            Storage::disk('public')->delete($defect->signature_path);
        }

        $defect->delete();

        return redirect()->route('defects.index')->with('success', 'Defect sters!');
    }

    public function updateStatus(Request $request, Defect $defect): RedirectResponse
    {
        $this->authorize('update', $defect);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,rejected'],
        ]);

        if ($validated['status'] === 'resolved' && $defect->photos()->doesntExist()) {
            return back()->with('error', 'Este necesara cel putin o poza pentru a marca defectul ca Rezolvat. Editeaza defectul si adauga o poza.');
        }

        $defect->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolved' ? ($defect->resolved_at ?? now()) : null,
            'resolved_by' => $validated['status'] === 'resolved' ? ($defect->resolved_by ?? $request->user()->id) : null,
        ]);

        return back()->with('success', 'Status defect actualizat!');
    }

    public function destroyPhoto(Defect $defect, DefectPhoto $defect_photo): RedirectResponse
    {
        $this->authorize('update', $defect);
        abort_unless((int) $defect_photo->defect_id === $defect->id, 404);

        Storage::disk('public')->delete($defect_photo->path);
        $defect_photo->delete();

        return back()->with('success', 'Poza a fost stearsa.');
    }

    public function pdf(Defect $defect): HttpResponse
    {
        $this->authorize('view', $defect);

        $defect->loadMissing([
            'project:id,name',
            'phase:id,name',
            'reporter:id,name',
            'assignee:id,name',
            'resolvedBy:id,name',
            'photos',
        ]);

        $pdf = Pdf::loadView('defects.pdf', [
            'defect' => $defect,
        ])->setPaper('a4');

        return $pdf->stream(sprintf('raport-defect-%d.pdf', $defect->id));
    }

    public function projectReport(Request $request): HttpResponse
    {
        $this->authorize('viewAny', Defect::class);

        $tenantId = TenantContext::id($request->user());
        $project = Project::where('tenant_id', $tenantId)->findOrFail($request->integer('project_id'));

        $defects = Defect::query()
            ->with(['phase:id,name', 'assignee:id,name', 'photos'])
            ->where('tenant_id', $tenantId)
            ->where('project_id', $project->id)
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'resolved' THEN 3 WHEN 'rejected' THEN 4 ELSE 5 END")
            ->get();

        $summary = [
            'total' => $defects->count(),
            'open' => $defects->where('status', 'open')->count(),
            'in_progress' => $defects->where('status', 'in_progress')->count(),
            'resolved' => $defects->where('status', 'resolved')->count(),
            'rejected' => $defects->where('status', 'rejected')->count(),
        ];

        $branding = DocumentBranding::resolve($tenantId);
        $fileName = sprintf('raport-defecte-%s.pdf', Str::slug($project->name));

        ExportAudit::log('defects-report-pdf', 'pdf', ['project_id' => $project->id], [
            'file_name' => $fileName,
        ]);

        $pdf = Pdf::loadView('defects.project-report', [
            'project' => $project,
            'defects' => $defects,
            'summary' => $summary,
            'branding' => $branding,
            'generatedAt' => now()->toDateTimeString(),
        ])->setPaper('a4');

        return $pdf->stream($fileName);
    }

    private function persistSignature(array $data, ?Defect $existing): array
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
        $path = 'defects/signatures/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $binary);

        $data['signature_path'] = $path;
        $data['signed_at'] = now();

        return $data;
    }

    private function persistPhotos(Defect $defect, Request $request, int $tenantId): void
    {
        foreach ($request->file('photos', []) as $file) {
            if ($file === null) {
                continue;
            }

            DefectPhoto::create([
                'tenant_id' => $tenantId,
                'defect_id' => $defect->id,
                'path' => $file->store('defects/photos', 'public'),
                'name' => $file->getClientOriginalName(),
            ]);
        }
    }
}
