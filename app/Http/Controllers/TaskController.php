<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Material;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Task::class, 'task');
    }

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id');
        $specialFilter = $request->string('special_filter')->toString();
        $today = now()->toDateString();
        $soonDate = now()->addDays(3)->toDateString();

        $tasks = Task::query()
            ->with(['project:id,name', 'phase:id,name,status', 'assignee:id,name', 'materials:id,name,unit'])
            ->where('tenant_id', $tenantId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->when($specialFilter === 'critical', function ($query) use ($today, $soonDate) {
                $query->where('priority', 'high')
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->where(function ($deadlineQuery) use ($today, $soonDate) {
                        $deadlineQuery->whereNull('deadline')
                            ->orWhereDate('deadline', '<=', $soonDate)
                            ->orWhereDate('deadline', '<', $today);
                    });
            })
            ->when($specialFilter === 'blocked', function ($query) use ($today) {
                $query->whereNotIn('status', ['done', 'cancelled'])
                    ->where(function ($blockedQuery) use ($today) {
                        $blockedQuery->whereHas('phase', fn ($phaseQuery) => $phaseQuery->where('status', 'blocked'))
                            ->orWhere(function ($deadlineQuery) use ($today) {
                                $deadlineQuery->whereNotNull('deadline')->whereDate('deadline', '<', $today);
                            });
                    });
            })
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
                'special_filter' => $specialFilter,
            ],
            'projects' => Project::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'materials' => Material::where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'unit', 'unit_price']),
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
            'users' => User::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'selectedProjectId' => $projectId > 0 ? $projectId : null,
            'phasesByProject' => $projects->mapWithKeys(fn ($project) => [
                $project->id => $project->phases->map(fn ($phase) => [
                    'id' => $phase->id,
                    'name' => $phase->name,
                ])->values(),
            ]),
            'materials' => Material::where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'unit', 'unit_price']),
            'taskTemplates' => $this->taskTemplatesPayload($tenantId),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $data = $request->validated();
        $taskMaterials = $this->normalizeTaskMaterials($data['task_materials'] ?? []);
        $data['tenant_id'] = $tenantId;
        $data['created_by'] = $request->user()->id;
        $data['checklist'] = $this->normalizeChecklist($data['checklist'] ?? []);
        unset($data['task_materials']);

        if ($data['status'] === 'done') {
            $data['completed_at'] = now();
        }

        $task = Task::create($data);
        $this->syncTaskMaterials($task, $taskMaterials);

        return redirect()->route('tasks.index')->with('success', 'Task creat cu succes!');
    }

    public function edit(Task $task): Response
    {
        $tenantId = TenantContext::id(request()->user());
        $task->load('materials:id,name,unit');

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
            'users' => User::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'phasesByProject' => $projects->mapWithKeys(fn ($project) => [
                $project->id => $project->phases->map(fn ($phase) => [
                    'id' => $phase->id,
                    'name' => $phase->name,
                ])->values(),
            ]),
            'materials' => Material::where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'unit', 'unit_price']),
            'taskMaterials' => $task->materials->map(fn ($material) => [
                'material_id' => $material->id,
                'quantity' => (float) ($material->pivot->quantity ?? 0),
                'unit_override' => $material->pivot->unit_override,
                'unit_price' => $material->pivot->unit_price,
            ])->values(),
            'taskTemplates' => $this->taskTemplatesPayload($tenantId),
        ]);
    }

    public function update(StoreTaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();
        $taskMaterials = $this->normalizeTaskMaterials($data['task_materials'] ?? []);
        $data['checklist'] = $this->normalizeChecklist($data['checklist'] ?? []);
        unset($data['task_materials']);

        $data['completed_at'] = $data['status'] === 'done'
            ? ($task->completed_at ?? now())
            : null;

        $task->update($data);
        $this->syncTaskMaterials($task, $taskMaterials);

        return redirect()->route('tasks.index')->with('success', 'Task actualizat cu succes!');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()->route('tasks.index')->with('success', 'Task sters!');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'status' => ['required', 'in:todo,in_progress,done,cancelled'],
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? ($task->completed_at ?? now()) : null,
        ]);

        return back()->with('success', 'Status task actualizat!');
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

    private function normalizeTaskMaterials(array $taskMaterials): array
    {
        return collect($taskMaterials)
            ->map(function ($item): ?array {
                $materialId = (int) ($item['material_id'] ?? 0);
                $quantity = (float) ($item['quantity'] ?? 0);

                if ($materialId <= 0 || $quantity <= 0) {
                    return null;
                }

                $unitOverride = trim((string) ($item['unit_override'] ?? ''));

                return [
                    'material_id' => $materialId,
                    'quantity' => $quantity,
                    'unit_override' => $unitOverride !== '' ? $unitOverride : null,
                    'unit_price' => isset($item['unit_price']) && $item['unit_price'] !== '' ? (float) $item['unit_price'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function syncTaskMaterials(Task $task, array $taskMaterials): void
    {
        $syncPayload = collect($taskMaterials)
            ->mapWithKeys(fn ($item) => [
                $item['material_id'] => [
                    'quantity' => $item['quantity'],
                    'unit_override' => $item['unit_override'],
                    'unit_price' => $item['unit_price'],
                ],
            ])
            ->all();

        $task->materials()->sync($syncPayload);
    }

    private function taskTemplatesPayload(int $tenantId)
    {
        return TaskTemplate::where('tenant_id', $tenantId)
            ->with(['recipe.items.material:id,name,unit'])
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (TaskTemplate $template) => [
                'id' => $template->id,
                'title' => $template->title,
                'recipe' => $template->recipe ? [
                    'id' => $template->recipe->id,
                    'unit' => $template->recipe->unit,
                    'items' => $template->recipe->items->map(fn ($item) => [
                        'material_id' => $item->material_id,
                        'material_name' => $item->material?->name,
                        'quantity_per_unit' => (float) $item->quantity_per_unit,
                        'unit' => $item->material?->unit,
                    ]),
                ] : null,
            ]);
    }
}
