<?php

namespace App\Support;

use App\Models\QualityCheck;
use App\Models\StageTask;

class QualityCheckAutoStatus
{
    public static function applyForPhase(?int $phaseId): void
    {
        if (! $phaseId || $phaseId <= 0) {
            return;
        }

        $totalTasks = StageTask::query()->where('stage_id', $phaseId)->count();

        if ($totalTasks === 0) {
            return;
        }

        $openTasks = StageTask::query()
            ->where('stage_id', $phaseId)
            ->whereIn('status', ['todo', 'in_progress', 'blocked'])
            ->count();

        if ($openTasks > 0) {
            return;
        }

        $checks = QualityCheck::query()
            ->where('phase_id', $phaseId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        foreach ($checks as $check) {
            $notes = trim((string) ($check->notes ?? ''));
            $autoNote = 'Status automat: verificarea a fost finalizata deoarece toate taskurile etapei sunt inchise.';

            $check->update([
                'status' => 'passed',
                'completed_at' => $check->completed_at ?? now(),
                'notes' => $notes === '' ? $autoNote : ($notes . "\n" . $autoNote),
            ]);
        }
    }
}
