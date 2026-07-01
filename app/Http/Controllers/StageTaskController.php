<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStageTaskRequest;
use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageTask;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StageTaskController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(StageTask::class, 'stage_task');
    }

    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->string('status')->toString(),
            'project_id' => $request->integer('project_id') > 0 ? $request->integer('project_id') : null,
            'stage_id' => $request->integer('stage_id') > 0 ? $request->integer('stage_id') : null,
        ];

        $tasks = StageTask::query()
            ->with(['stage:id,project_id,name', 'stage.project:id,name', 'userAssignee:id,name', 'teamAssignee:id,name', 'contractorAssignee:id,name'])
            ->whereHas('stage.project', fn ($query) => $query->where('tenant_id', 1))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['project_id'], fn ($query, $value) => $query->whereHas('stage', fn ($q) => $q->where('project_id', $value)))
            ->when($filters['stage_id'], fn ($query, $value) => $query->where('stage_id', $value))
            ->orderByRaw("CASE WHEN status = 'todo' THEN 1 WHEN status = 'in_progress' THEN 2 WHEN status = 'blocked' THEN 3 WHEN status = 'done' THEN 4 ELSE 5 END")
            ->orderBy('deadline')
            ->latest('id')
            ->paginate(20)
            ->through(fn (StageTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'deadline' => optional($task->deadline)?->format('Y-m-d H:i:s'),
                'assignee_type' => $task->assignee_type,
                'assignee_name' => $this->resolveAssigneeName($task),
                'stage' => [
                    'id' => $task->stage?->id,
                    'name' => $task->stage?->name,
                    'project_name' => $task->stage?->project?->name,
                ],
            ])
            ->withQueryString();

        return Inertia::render('StageTasks/Index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'projects' => Project::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => $query->where('tenant_id', 1))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statusLabels' => StageTask::$statusLabels,
            'assigneeTypes' => StageTask::$assigneeTypes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('StageTasks/Create', [
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => $query->where('tenant_id', 1))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statusLabels' => StageTask::$statusLabels,
            'assigneeTypes' => StageTask::$assigneeTypes,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'teams' => Team::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreStageTaskRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        if (empty($payload['assignee_type'])) {
            $payload['assignee_type'] = null;
            $payload['assignee_id'] = null;
        }

        StageTask::create($payload);

        return redirect()->route('stage-tasks.index')->with('success', 'Taskul de etapa a fost creat.');
    }

    public function edit(StageTask $stage_task): Response
    {
        return Inertia::render('StageTasks/Edit', [
            'task' => $stage_task,
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => $query->where('tenant_id', 1))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statusLabels' => StageTask::$statusLabels,
            'assigneeTypes' => StageTask::$assigneeTypes,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'teams' => Team::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(StoreStageTaskRequest $request, StageTask $stage_task): RedirectResponse
    {
        $payload = $request->validated();

        if (empty($payload['assignee_type'])) {
            $payload['assignee_type'] = null;
            $payload['assignee_id'] = null;
        }

        $stage_task->update($payload);

        return redirect()->route('stage-tasks.index')->with('success', 'Taskul de etapa a fost actualizat.');
    }

    public function destroy(StageTask $stage_task): RedirectResponse
    {
        $stage_task->delete();

        return redirect()->route('stage-tasks.index')->with('success', 'Taskul de etapa a fost sters.');
    }

    private function resolveAssigneeName(StageTask $task): ?string
    {
        return match ($task->assignee_type) {
            'user' => $task->userAssignee?->name,
            'team' => $task->teamAssignee?->name,
            'contractor' => $task->contractorAssignee?->name,
            default => null,
        };
    }
}
