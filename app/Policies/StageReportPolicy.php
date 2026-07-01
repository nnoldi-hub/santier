<?php

namespace App\Policies;

use App\Models\StageReport;
use App\Models\User;

class StageReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StageReport $stageReport): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, StageReport $stageReport): bool
    {
        return true;
    }

    public function delete(User $user, StageReport $stageReport): bool
    {
        return true;
    }
}
