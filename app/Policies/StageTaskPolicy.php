<?php

namespace App\Policies;

use App\Models\StageTask;
use App\Models\User;

class StageTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StageTask $stageTask): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, StageTask $stageTask): bool
    {
        return true;
    }

    public function delete(User $user, StageTask $stageTask): bool
    {
        return true;
    }
}
