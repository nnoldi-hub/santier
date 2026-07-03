<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDefectRequest;
use App\Models\Defect;
use App\Models\Project;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DefectController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $status = $request->string('status')->toString();
        $priority = $request->string('priority')->toString();
        $projectId = $request->integer('project_id');

        $defects = Defect::query()
            ->with(['project:id,name', 'phase:id,name', 'reporter:id,name', 'assignee:id,name'])
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
            'users' => User::orderBy('name')->get(['id', 'name']),
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
        unset($data['photo']);
        $data['tenant_id'] = $tenantId;
        $data['reported_by'] = $request->user()->id;
        $data['resolved_at'] = $data['status'] === 'resolved' ? now() : null;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $data['photo_path'] = $file->store('defects', 'public');
            $data['photo_name'] = $file->getClientOriginalName();
        }

        Defect::create($data);

        return redirect()->route('defects.index')->with('success', 'Defect creat cu succes!');
    }

    public function edit(Defect $defect): Response
    {
        $tenantId = TenantContext::id(request()->user());

        $projects = Project::where('tenant_id', $tenantId)
            ->with(['phases:id,project_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Defects/Edit', [
            'defect' => [
                ...$defect->toArray(),
                'photo_url' => $defect->photo_path ? Storage::url($defect->photo_path) : null,
            ],
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
        ]);
    }

    public function update(StoreDefectRequest $request, Defect $defect): RedirectResponse
    {
        $data = $request->validated();
        unset($data['photo']);
        $data['resolved_at'] = $data['status'] === 'resolved'
            ? ($defect->resolved_at ?? now())
            : null;

        if ($request->hasFile('photo')) {
            if ($defect->photo_path) {
                Storage::disk('public')->delete($defect->photo_path);
            }

            $file = $request->file('photo');
            $data['photo_path'] = $file->store('defects', 'public');
            $data['photo_name'] = $file->getClientOriginalName();
        }

        $defect->update($data);

        return redirect()->route('defects.index')->with('success', 'Defect actualizat!');
    }

    public function destroy(Defect $defect): RedirectResponse
    {
        if ($defect->photo_path) {
            Storage::disk('public')->delete($defect->photo_path);
        }

        $defect->delete();

        return redirect()->route('defects.index')->with('success', 'Defect sters!');
    }

    public function updateStatus(Request $request, Defect $defect): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,rejected'],
        ]);

        $defect->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolved' ? ($defect->resolved_at ?? now()) : null,
        ]);

        return back()->with('success', 'Status defect actualizat!');
    }
}
