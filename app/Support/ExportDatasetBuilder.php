<?php

namespace App\Support;

use App\Models\Defect;
use App\Models\Document;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\StageEquipment;
use App\Models\StageReport;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use App\Support\DemoScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class ExportDatasetBuilder
{
    public static function build(string $exportType, array $filters): array
    {
        return match ($exportType) {
            'projects' => self::projects($filters),
            'quotes' => self::quotes($filters),
            'materials' => self::materials($filters),
            'costs' => self::costs($filters),
            'teams' => self::teams($filters),
            'tasks' => self::tasks($filters),
            'defects' => self::defects($filters),
            'wbs' => self::wbs($filters),
            'equipment' => self::equipment($filters),
            'documents' => self::documents($filters),
            'stage-reports' => self::stageReports($filters),
            'stage-tasks' => self::stageTasks($filters),
            'stage-progress' => self::stageProgress($filters),
            default => ['rows' => collect(), 'meta' => []],
        };
    }

    private static function stageProgress(array $filters): array
    {
        $query = ProjectPhase::query()
            ->with(['project:id,name,tenant_id', 'contractor:id,name', 'parent:id,name'])
            ->whereHas('project', fn ($q) => $q->where('tenant_id', 1));

        self::applyProjectFilter($query, $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['contractor_id'])) {
            $query->where('contractor_id', $filters['contractor_id']);
        }

        if (!empty($filters['q'])) {
            $text = $filters['q'];
            $query->where(function ($inner) use ($text) {
                $inner->where('name', 'like', "%{$text}%")
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$text}%"))
                    ->orWhereHas('contractor', fn ($contractorQuery) => $contractorQuery->where('name', 'like', "%{$text}%"));
            });
        }

        return [
            'rows' => $query
                ->orderByDesc('progress_pct')
                ->orderBy('project_id')
                ->orderBy('order')
                ->latest('id')
                ->get()
                ->map(function (ProjectPhase $phase) {
                    return [
                        'phase_id' => $phase->id,
                        'project' => $phase->project?->name,
                        'phase' => $phase->name,
                        'parent' => $phase->parent?->name,
                        'contractor' => $phase->contractor?->name,
                        'status' => $phase->status,
                        'progress_pct' => $phase->progress_pct,
                        'start_date' => optional($phase->start_date)->format('Y-m-d'),
                        'end_date' => optional($phase->end_date)->format('Y-m-d'),
                        'order' => $phase->order,
                    ];
                }),
            'meta' => ['title' => 'Progres etape'],
        ];
    }

    private static function stageReports(array $filters): array
    {
        $query = StageReport::query()
            ->with(['stage:id,project_id,name', 'stage.project:id,name,tenant_id', 'contractor:id,name', 'creator:id,name'])
            ->whereHas('stage.project', fn ($q) => $q->where('tenant_id', 1));

        self::applyDateRange($query, 'report_date', $filters);

        if (!empty($filters['project_id'])) {
            $query->whereHas('stage', fn ($q) => $q->where('project_id', $filters['project_id']));
        }

        if (!empty($filters['q'])) {
            $text = $filters['q'];
            $query->where(function ($inner) use ($text) {
                $inner->where('activities', 'like', "%{$text}%")
                    ->orWhere('issues', 'like', "%{$text}%");
            });
        }

        return [
            'rows' => $query->orderByDesc('report_date')->latest('id')->get(),
            'meta' => ['title' => 'Rapoarte etapa'],
        ];
    }

    private static function stageTasks(array $filters): array
    {
        $query = StageTask::query()
            ->with(['stage:id,project_id,name', 'stage.project:id,name,tenant_id', 'userAssignee:id,name', 'teamAssignee:id,name', 'contractorAssignee:id,name'])
            ->whereHas('stage.project', fn ($q) => $q->where('tenant_id', 1));

        self::applyDateRange($query, 'deadline', $filters);

        if (!empty($filters['project_id'])) {
            $query->whereHas('stage', fn ($q) => $q->where('project_id', $filters['project_id']));
        }

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['q'])) {
            $text = $filters['q'];
            $query->where(function ($inner) use ($text) {
                $inner->where('title', 'like', "%{$text}%")
                    ->orWhere('description', 'like', "%{$text}%");
            });
        }

        return [
            'rows' => $query
                ->orderByRaw("CASE WHEN status = 'todo' THEN 1 WHEN status = 'in_progress' THEN 2 WHEN status = 'blocked' THEN 3 WHEN status = 'done' THEN 4 ELSE 5 END")
                ->orderBy('deadline')
                ->latest('id')
                ->get(),
            'meta' => ['title' => 'Taskuri etapa'],
        ];
    }

    private static function documents(array $filters): array
    {
        $query = Document::query()
            ->where('tenant_id', 1)
            ->with(['project:id,name', 'stage:id,name', 'contractor:id,name']);

        self::applyDateRange($query, 'issued_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'notes', 'file_name'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('payment_status', $filters['status']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Documente financiare'],
        ];
    }

    private static function equipment(array $filters): array
    {
        $query = StageEquipment::query()
            ->with([
                'equipment:id,name,type,supplier_name,cost_per_hour,availability_status',
                'phase:id,project_id,name,status',
                'phase.project:id,name,tenant_id',
            ])
            ->whereHas('phase.project', fn ($q) => $q->where('tenant_id', 1));

        self::applyDateRange($query, 'usage_start', $filters);
        self::applyProjectFilter($query, $filters);

        if (!empty($filters['q'])) {
            $text = $filters['q'];
            $query->where(function ($inner) use ($text) {
                $inner->whereHas('equipment', function ($equipmentQuery) use ($text) {
                    $equipmentQuery->where('name', 'like', "%{$text}%")
                        ->orWhere('supplier_name', 'like', "%{$text}%");
                })->orWhereHas('phase', function ($phaseQuery) use ($text) {
                    $phaseQuery->where('name', 'like', "%{$text}%");
                });
            });
        }

        $rows = $query
            ->latest('id')
            ->get()
            ->map(function (StageEquipment $reservation) {
                $days = 1;
                if ($reservation->usage_start && $reservation->usage_end) {
                    $days = Carbon::parse($reservation->usage_start)
                        ->diffInDays(Carbon::parse($reservation->usage_end)) + 1;
                }

                $dailyHours = 8;
                $hourlyCost = (float) ($reservation->equipment?->cost_per_hour ?? 0);
                $estimatedCost = $hourlyCost * max(1, (int) $reservation->quantity) * max(1, $days) * $dailyHours;

                return [
                    'reservation_id' => $reservation->id,
                    'project' => $reservation->phase?->project?->name,
                    'phase' => $reservation->phase?->name,
                    'phase_status' => $reservation->phase?->status,
                    'equipment' => $reservation->equipment?->name,
                    'equipment_type' => $reservation->equipment?->type,
                    'supplier' => $reservation->equipment?->supplier_name,
                    'availability_status' => $reservation->equipment?->availability_status,
                    'quantity' => $reservation->quantity,
                    'usage_start' => optional($reservation->usage_start)->format('Y-m-d'),
                    'usage_end' => optional($reservation->usage_end)->format('Y-m-d'),
                    'days' => $days,
                    'hourly_cost' => $hourlyCost,
                    'estimated_cost' => round($estimatedCost, 2),
                ];
            });

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Utilaje rezervate pe etape'],
        ];
    }

    private static function wbs(array $filters): array
    {
        $query = ProjectPhase::query()
            ->with([
                'project:id,name',
                'contractor:id,name',
                'parent:id,name,parent_id',
            ])
            ->whereHas('project', fn ($q) => $q->where('tenant_id', 1));

        self::applyDateRange($query, 'created_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['name', 'type', 'notes'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        return [
            'rows' => $query
                ->orderBy('project_id')
                ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->map(function (ProjectPhase $phase) {
                    $level = self::resolvePhaseLevel($phase);

                    return [
                        'id' => $phase->id,
                        'project' => $phase->project?->name,
                        'name' => $phase->name,
                        'level' => $level,
                        'wbs_path' => str_repeat('>', max(0, $level - 1)) . ($level > 1 ? ' ' : '') . $phase->name,
                        'parent' => $phase->parent?->name,
                        'status' => $phase->status,
                        'progress_pct' => $phase->progress_pct,
                        'contractor' => $phase->contractor?->name,
                        'start_date' => optional($phase->start_date)->format('Y-m-d'),
                        'end_date' => optional($phase->end_date)->format('Y-m-d'),
                    ];
                }),
            'meta' => ['title' => 'WBS Etape'],
        ];
    }

    private static function resolvePhaseLevel(ProjectPhase $phase): int
    {
        $level = 1;
        $parentId = $phase->parent_id;
        $safety = 0;

        while ($parentId && $safety < 20) {
            $level++;
            $parentId = ProjectPhase::query()->where('id', $parentId)->value('parent_id');
            $safety++;
        }

        return $level;
    }

    private static function projects(array $filters): array
    {
        $query = Project::query()->with('client:id,name')->where('tenant_id', 1);

        self::applyDateRange($query, 'created_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['name', 'address', 'description'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Proiecte'],
        ];
    }

    private static function quotes(array $filters): array
    {
        $query = Quote::query()->with('project:id,name')->where('tenant_id', 1);

        self::applyDateRange($query, 'created_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'notes'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Oferte si Devize'],
        ];
    }

    private static function materials(array $filters): array
    {
        $query = Material::query()->where('tenant_id', 1);

        self::applyDateRange($query, 'created_at', $filters);
        self::applyTextFilter($query, ['name', 'code', 'supplier', 'category'], $filters);

        if (!$filters['include_inactive']) {
            $query->where('active', true);
        }

        return [
            'rows' => $query->orderBy('name')->get(),
            'meta' => ['title' => 'Materiale'],
        ];
    }

    private static function costs(array $filters): array
    {
        $user = auth()->user();

        $projectsQuery = Project::query();
        DemoScope::applyProjectScope($projectsQuery, $user);

        $projectsQuery->with(['quotes' => function ($query) use ($filters) {
                self::applyDateRange($query, 'created_at', $filters);
                if (!empty($filters['status'])) {
                    $query->whereIn('status', $filters['status']);
                }
            }]);

        self::applyProjectFilter($projectsQuery, $filters);
        self::applyTextFilter($projectsQuery, ['name'], $filters);

        $rows = $projectsQuery->get()->map(function (Project $project) {
            $quotes = $project->quotes;
            $gross = (float) $quotes->sum('total_gross');
            $budget = $project->total_budget !== null ? (float) $project->total_budget : null;

            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'budget' => $budget,
                'quotes_count' => $quotes->count(),
                'total_net' => (float) $quotes->sum('total_net'),
                'total_tva' => (float) $quotes->sum('total_tva'),
                'total_gross' => $gross,
                'accepted_total_gross' => (float) $quotes->where('status', 'accepted')->sum('total_gross'),
                'diff_vs_budget' => $budget !== null ? round($gross - $budget, 2) : null,
            ];
        });

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Costuri proiecte'],
        ];
    }

    private static function teams(array $filters): array
    {
        $query = Team::query()->where('tenant_id', 1)
            ->with([
                'leader:id,name',
                'members.user:id,name',
                'assignments.phase.project:id,name',
            ]);

        self::applyTextFilter($query, ['name', 'specialty'], $filters);

        if (!$filters['include_inactive']) {
            $query->where('active', true);
        }

        if (!empty($filters['team_id'])) {
            $query->where('id', $filters['team_id']);
        }

        $rows = collect();

        foreach ($query->orderBy('name')->get() as $team) {
            $projects = $team->assignments
                ->map(fn ($assignment) => $assignment->phase?->project?->name)
                ->filter()
                ->unique()
                ->values()
                ->implode(' | ');

            if ($team->members->isEmpty()) {
                $rows->push([
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'leader' => $team->leader?->name,
                    'member' => null,
                    'member_role' => null,
                    'specialty' => $team->specialty,
                    'active' => $team->active,
                    'assignments_count' => $team->assignments->count(),
                    'projects' => $projects,
                ]);
                continue;
            }

            foreach ($team->members as $member) {
                $rows->push([
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'leader' => $team->leader?->name,
                    'member' => $member->user?->name,
                    'member_role' => $member->role,
                    'specialty' => $team->specialty,
                    'active' => $team->active,
                    'assignments_count' => $team->assignments->count(),
                    'projects' => $projects,
                ]);
            }
        }

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Echipe si responsabilitati'],
        ];
    }

    private static function tasks(array $filters): array
    {
        $query = Task::query()->where('tenant_id', 1)
            ->with(['project:id,name', 'phase:id,name', 'assignee:id,name']);

        self::applyDateRange($query, 'deadline', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'description'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->whereIn('priority', $filters['priority']);
        }

        if (!empty($filters['assignee_ids'])) {
            $query->whereIn('assigned_to', $filters['assignee_ids']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Taskuri'],
        ];
    }

    private static function defects(array $filters): array
    {
        $query = Defect::query()->where('tenant_id', 1)
            ->with(['project:id,name', 'phase:id,name', 'assignee:id,name']);

        self::applyDateRange($query, 'due_date', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'description', 'location'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->whereIn('priority', $filters['priority']);
        }

        if (!empty($filters['assignee_ids'])) {
            $query->whereIn('assigned_to', $filters['assignee_ids']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Defecte'],
        ];
    }

    private static function applyDateRange(Builder|Relation $query, string $column, array $filters): void
    {
        if (!empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }

    private static function applyProjectFilter(Builder|Relation $query, array $filters): void
    {
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
    }

    private static function applyTextFilter(Builder|Relation $query, array $columns, array $filters): void
    {
        if (empty($filters['q'])) {
            return;
        }

        $text = $filters['q'];
        $query->where(function ($inner) use ($columns, $text) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $inner->where($column, 'like', "%{$text}%");
                } else {
                    $inner->orWhere($column, 'like', "%{$text}%");
                }
            }
        });
    }
}
