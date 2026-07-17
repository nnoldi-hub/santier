<?php

namespace App\Support;

use App\Models\Contractor;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceOrder;
use App\Models\SiteCompliancePlan;
use App\Models\StageEquipment;
use App\Models\StageTask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyBriefingBuilder
{
    public static function build(Project $project, ?Carbon $date = null): array
    {
        // The app runs on UTC (config('app.timezone')) but "today" for a
        // construction site in Romania should follow local wall-clock time.
        $date = ($date ?? now('Europe/Bucharest'))->toDateString();
        $phaseIds = $project->phases()->pluck('id');

        $teams = self::teamsToday($project, $date);
        $subcontractors = self::subcontractorsToday($project, $date);
        $materials = self::materialsToday($project, $date);
        $equipment = self::equipmentToday($phaseIds, $date);
        $documents = self::documentsToday($project, $date);
        $tasks = self::tasksToday($project, $phaseIds, $date);
        $blockers = self::computeBlockers($teams, $subcontractors, $materials, $documents, $tasks);

        return [
            'date' => $date,
            'teams' => $teams->values()->all(),
            'subcontractors' => $subcontractors->values()->all(),
            'materials' => $materials->values()->all(),
            'equipment' => $equipment->values()->all(),
            'documents' => $documents->values()->all(),
            'tasks' => $tasks->values()->all(),
            'blockers' => $blockers,
            'recommendations' => DailyBriefingAdvisor::suggest([
                'teams' => $teams,
                'subcontractors' => $subcontractors,
                'materials' => $materials,
                'documents' => $documents,
                'tasks' => $tasks,
            ]),
        ];
    }

    private static function dateWindowQuery(Builder $query, string $date, string $startColumn = 'start_date', string $endColumn = 'end_date'): Builder
    {
        return $query->where(function ($query) use ($date, $startColumn, $endColumn) {
            $query
                ->whereBetween($startColumn, [$date, $date])
                ->orWhereBetween($endColumn, [$date, $date])
                ->orWhere(function ($inner) use ($date, $startColumn, $endColumn) {
                    $inner
                        ->where(function ($startQuery) use ($date, $startColumn) {
                            $startQuery->whereNull($startColumn)->orWhereDate($startColumn, '<=', $date);
                        })
                        ->where(function ($endQuery) use ($date, $endColumn) {
                            $endQuery->whereNull($endColumn)->orWhereDate($endColumn, '>=', $date);
                        });
                });
        });
    }

    private static function teamsToday(Project $project, string $date): Collection
    {
        $query = PhaseTeamAssignment::query()
            ->with(['team:id,name', 'phase:id,project_id,name'])
            ->whereHas('phase', fn ($q) => $q->where('project_id', $project->id));

        return self::dateWindowQuery($query, $date)
            ->get()
            ->map(function (PhaseTeamAssignment $assignment) {
                $needed = (int) $assignment->workers_needed;
                $assigned = (int) $assignment->workers_assigned;

                return [
                    'id' => $assignment->id,
                    'team_id' => $assignment->team_id,
                    'team_name' => $assignment->team?->name ?? 'Echipa',
                    'phase_name' => $assignment->phase?->name,
                    'workers_needed' => $needed,
                    'workers_assigned' => $assigned,
                    'start_date' => optional($assignment->start_date)->toDateString(),
                    'end_date' => optional($assignment->end_date)->toDateString(),
                    'notes' => $assignment->notes,
                    'confirmation_status' => $assigned >= $needed ? 'confirmat' : 'risc',
                ];
            });
    }

    private static function subcontractorsToday(Project $project, string $date): Collection
    {
        $query = ProjectPhase::query()
            ->with(['contractor:id,name,type'])
            ->where('project_id', $project->id)
            ->whereNotNull('contractor_id')
            ->whereHas('contractor', fn ($q) => $q->whereIn('type', [Contractor::TYPE_SUBCONTRACTOR, Contractor::TYPE_PFA]));

        return self::dateWindowQuery($query, $date)
            ->get(['id', 'project_id', 'name', 'contractor_id', 'start_date', 'end_date', 'status'])
            ->map(function (ProjectPhase $phase) {
                $status = match ($phase->status) {
                    'in_progress', 'completed' => 'confirmat',
                    'blocked' => 'risc',
                    default => 'planificat',
                };

                return [
                    'id' => $phase->id,
                    'contractor_id' => $phase->contractor_id,
                    'contractor_name' => $phase->contractor?->name ?? 'Subcontractor',
                    'phase_name' => $phase->name,
                    'phase_status' => $phase->status,
                    'start_date' => optional($phase->start_date)->toDateString(),
                    'end_date' => optional($phase->end_date)->toDateString(),
                    'confirmation_status' => $status,
                ];
            });
    }

    private static function materialsToday(Project $project, string $date): Collection
    {
        return ResourceOrder::query()
            ->with(['material:id,name', 'phase:id,name'])
            ->where('project_id', $project->id)
            ->where('resource_type', 'material')
            ->whereDate('delivery_date', $date)
            ->get()
            ->map(function (ResourceOrder $order) {
                $status = match ($order->status) {
                    'ordered', 'delivered', 'verified', 'financial_review', 'approved' => 'confirmat',
                    'blocked_payment', 'rejected' => 'risc',
                    default => 'neconfirmat',
                };

                return [
                    'id' => $order->id,
                    'material_name' => $order->material?->name ?? $order->supplier_name,
                    'supplier_name' => $order->supplier_name,
                    'ordered_quantity' => $order->ordered_quantity,
                    'ordered_unit' => $order->ordered_unit,
                    'phase_name' => $order->phase?->name,
                    'status' => $order->status,
                    'status_label' => ResourceOrder::$statusLabels[$order->status] ?? $order->status,
                    'confirmation_status' => $status,
                ];
            });
    }

    private static function equipmentToday(Collection $phaseIds, string $date): Collection
    {
        $query = StageEquipment::query()
            ->with(['equipment:id,name', 'phase:id,name'])
            ->whereIn('stage_id', $phaseIds);

        return self::dateWindowQuery($query, $date, 'usage_start', 'usage_end')
            ->get()
            ->map(fn (StageEquipment $reservation) => [
                'id' => $reservation->id,
                'equipment_id' => $reservation->equipment_id,
                'equipment_name' => $reservation->equipment?->name ?? 'Utilaj',
                'phase_name' => $reservation->phase?->name,
                'quantity' => $reservation->quantity,
                'usage_start' => optional($reservation->usage_start)->toDateString(),
                'usage_end' => optional($reservation->usage_end)->toDateString(),
                'confirmation_status' => 'planificat',
            ]);
    }

    private static function documentsToday(Project $project, string $date): Collection
    {
        return SiteCompliancePlan::query()
            ->where('project_id', $project->id)
            ->whereDate('due_date', $date)
            ->get()
            ->map(fn (SiteCompliancePlan $plan) => [
                'id' => $plan->id,
                'title' => $plan->title,
                'item_type' => $plan->item_type,
                'item_type_label' => SiteCompliancePlan::$itemTypeLabels[$plan->item_type] ?? $plan->item_type,
                'status' => $plan->status,
                'status_label' => SiteCompliancePlan::$statusLabels[$plan->status] ?? $plan->status,
                'due_date' => optional($plan->due_date)->toDateString(),
            ]);
    }

    private static function tasksToday(Project $project, Collection $phaseIds, string $date): Collection
    {
        $tasks = Task::query()
            ->with(['assignee:id,name'])
            ->where('project_id', $project->id)
            ->whereDate('deadline', $date)
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'source' => 'task',
                'title' => $task->title,
                'status' => $task->status,
                'priority' => $task->priority,
                'phase_name' => null,
                'assignee_name' => $task->assignee?->name,
                'deadline' => optional($task->deadline)->toDateTimeString(),
            ]);

        $stageTasks = StageTask::query()
            ->with(['stage:id,name'])
            ->whereIn('stage_id', $phaseIds)
            ->whereDate('deadline', $date)
            ->get()
            ->map(fn (StageTask $task) => [
                'id' => $task->id,
                'source' => 'stage_task',
                'title' => $task->title,
                'status' => $task->status,
                'priority' => null,
                'phase_name' => $task->stage?->name,
                'assignee_name' => null,
                'deadline' => optional($task->deadline)->toDateTimeString(),
            ]);

        return $tasks->concat($stageTasks);
    }

    private static function computeBlockers(
        Collection $teams,
        Collection $subcontractors,
        Collection $materials,
        Collection $documents,
        Collection $tasks
    ): array {
        $blockers = [];

        foreach ($teams as $team) {
            if ($team['confirmation_status'] === 'risc') {
                $blockers[] = "Echipa {$team['team_name']} are doar {$team['workers_assigned']}/{$team['workers_needed']} muncitori alocati azi.";
            }
        }

        foreach ($subcontractors as $sub) {
            if ($sub['confirmation_status'] === 'risc') {
                $blockers[] = "Subcontractorul {$sub['contractor_name']} are etapa \"{$sub['phase_name']}\" blocata.";
            }
        }

        foreach ($materials as $material) {
            if ($material['confirmation_status'] === 'risc') {
                $blockers[] = "Comanda de material \"{$material['material_name']}\" este blocata la plata, livrare azi.";
            }
        }

        foreach ($documents as $doc) {
            if (in_array($doc['status'], ['expired', 'missing'], true)) {
                $label = $doc['status'] === 'expired' ? 'expirat' : 'lipsa';
                $blockers[] = "Documentul \"{$doc['title']}\" este {$label}, scadenta azi.";
            }
        }

        foreach ($tasks as $task) {
            if ($task['status'] === 'blocked') {
                $blockers[] = "Taskul \"{$task['title']}\" este blocat, termen azi.";
            }
        }

        return $blockers;
    }
}
