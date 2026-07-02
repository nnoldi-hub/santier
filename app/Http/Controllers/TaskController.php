<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id');

        $tasks = Task::query()
            ->with(['project:id,name', 'phase:id,name', 'assignee:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'done' THEN 3 WHEN 'cancelled' THEN 4 ELSE 5 END")
            ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 ELSE 4 END")
            ->orderBy('deadline')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'status' => $status,
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

        return Inertia::render('Tasks/Create', [
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

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $data = $request->validated();
        $data['tenant_id'] = $tenantId;
        $data['created_by'] = $request->user()->id;

        if ($data['status'] === 'done') {
            $data['completed_at'] = now();
        }

        Task::create($data);

        return redirect()->route('tasks.index')->with('success', 'Task creat cu succes!');
    }

    public function edit(Task $task): Response
    {
        $tenantId = TenantContext::id(request()->user());

        $projects = Project::where('tenant_id', $tenantId)
            ->with(['phases:id,project_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Tasks/Edit', [
            'task' => $task,
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

    public function update(StoreTaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();

        $data['completed_at'] = $data['status'] === 'done'
            ? ($task->completed_at ?? now())
            : null;

        $task->update($data);

        return redirect()->route('tasks.index')->with('success', 'Task actualizat cu succes!');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task sters!');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done,cancelled'],
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? ($task->completed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Status task actualizat!');
    }
}
