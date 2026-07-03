<?php

namespace App\Console\Commands;

use App\Models\Defect;
use App\Models\ProjectPhase as PhaseModel;
use App\Models\Task;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

class SendOperationalRemindersCommand extends Command
{
    protected $signature = 'notifications:send-operational-reminders';

    protected $description = 'Send operational reminder notifications for overdue tasks, delayed phases and critical defects';

    public function handle(): int
    {
        $today = now()->toDateString();
        $tenantIds = $this->collectTenantIds();
        $sent = 0;

        foreach ($tenantIds as $tenantId) {
            $sent += $this->sendOverdueTaskNotifications($tenantId, $today);
            $sent += $this->sendDelayedPhaseNotifications($tenantId, $today);
            $sent += $this->sendCriticalDefectNotifications($tenantId, $today);
        }

        $this->info($sent > 0
            ? 'Sent ' . $sent . ' operational reminder notification(s).'
            : 'No operational reminders were due.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function collectTenantIds(): array
    {
        $taskTenantIds = Task::query()->distinct()->pluck('tenant_id')->all();
        $defectTenantIds = Defect::query()->distinct()->pluck('tenant_id')->all();
        $phaseTenantIds = PhaseModel::query()
            ->with('project:id,tenant_id')
            ->get(['id', 'project_id'])
            ->map(fn (PhaseModel $phase) => (int) ($phase->project?->tenant_id ?? 0))
            ->filter(fn (int $tenantId) => $tenantId > 0)
            ->all();

        return array_values(array_unique(array_filter(array_merge($taskTenantIds, $defectTenantIds, $phaseTenantIds))));
    }

    private function sendOverdueTaskNotifications(int $tenantId, string $today): int
    {
        $tasks = Task::query()
            ->with(['project:id,name,tenant_id', 'phase:id,name,project_id', 'assignee:id,name', 'creator:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['todo', 'in_progress'])
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', $today)
            ->get();

        return $tasks->sum(function (Task $task) use ($tenantId): int {
            $recipient = $task->assignee ?: $task->creator;
            if (!$recipient instanceof User || $this->alreadySent($recipient, 'task_overdue', (int) $task->id)) {
                return 0;
            }

            $task->assignee?->notify($this->buildTaskNotification($task, 'task_overdue'));

            if (!$task->assignee && $task->creator) {
                $task->creator->notify($this->buildTaskNotification($task, 'task_overdue'));
            }

            return 1;
        });
    }

    private function sendDelayedPhaseNotifications(int $tenantId, string $today): int
    {
        $phases = PhaseModel::query()
            ->with(['project:id,name,tenant_id,created_by', 'project.creator:id,name'])
            ->whereHas('project', fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->get();

        return $phases->sum(function (PhaseModel $phase): int {
            $recipient = $phase->project?->creator;
            if (!$recipient instanceof User || $this->alreadySent($recipient, 'phase_overdue', (int) $phase->id)) {
                return 0;
            }

            $recipient->notify($this->buildPhaseNotification($phase));

            return 1;
        });
    }

    private function sendCriticalDefectNotifications(int $tenantId, string $today): int
    {
        $defects = Defect::query()
            ->with(['project:id,name,tenant_id', 'phase:id,name,project_id', 'assignee:id,name', 'reporter:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->where('priority', 'high')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->get();

        return $defects->sum(function (Defect $defect): int {
            $recipient = $defect->assignee ?: $defect->reporter;
            if (!$recipient instanceof User || $this->alreadySent($recipient, 'defect_overdue', (int) $defect->id)) {
                return 0;
            }

            $recipient->notify($this->buildDefectNotification($defect));

            return 1;
        });
    }

    private function alreadySent(User $recipient, string $event, int $entityId): bool
    {
        return $recipient->notifications()
            ->where('type', OperationalReminderNotification::class)
            ->get()
            ->contains(function (DatabaseNotification $notification) use ($event, $entityId): bool {
                return ($notification->data['event'] ?? null) === $event
                    && (int) ($notification->data['entity_id'] ?? 0) === $entityId;
            });
    }

    private function buildTaskNotification(Task $task, string $event): OperationalReminderNotification
    {
        $projectName = $task->project?->name;
        $message = sprintf(
            'Taskul "%s" a depasit termenul si necesita atentie.',
            $task->title,
        );

        return new OperationalReminderNotification(
            event: $event,
            title: 'Task restant',
            message: $message,
            entityType: 'task',
            entityId: (int) $task->id,
            projectId: $task->project_id,
            projectName: $projectName,
            url: route('tasks.edit', $task->id),
            severity: 'high',
        );
    }

    private function buildPhaseNotification(PhaseModel $phase): OperationalReminderNotification
    {
        $projectName = $phase->project?->name;
        $message = sprintf(
            'Etapa "%s" a depasit termenul si trebuie reevaluata.',
            $phase->name,
        );

        return new OperationalReminderNotification(
            event: 'phase_overdue',
            title: 'Etapa intarziata',
            message: $message,
            entityType: 'phase',
            entityId: (int) $phase->id,
            projectId: $phase->project_id,
            projectName: $projectName,
            url: route('projects.show', $phase->project_id) . '#phase-' . $phase->id,
            severity: 'high',
        );
    }

    private function buildDefectNotification(Defect $defect): OperationalReminderNotification
    {
        $projectName = $defect->project?->name;
        $message = sprintf(
            'Defectul critic "%s" este inca deschis si a depasit termenul de remediere.',
            $defect->title,
        );

        return new OperationalReminderNotification(
            event: 'defect_overdue',
            title: 'Defect critic deschis',
            message: $message,
            entityType: 'defect',
            entityId: (int) $defect->id,
            projectId: $defect->project_id,
            projectName: $projectName,
            url: route('defects.edit', $defect->id),
            severity: 'high',
        );
    }
}