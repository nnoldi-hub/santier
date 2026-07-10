<?php

namespace App\Policies;

use App\Models\StageTask;
use App\Models\User;
use App\Support\TenantContext;

class StageTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tasks.view');
    }

    public function view(User $user, StageTask $stageTask): bool
    {
        return $this->sameTenant($user, $stageTask)
            && $user->can('tasks.view');
    }

    public function create(User $user): bool
    {
        return $user->can('tasks.create');
    }

    public function update(User $user, StageTask $stageTask): bool
    {
        return $this->sameTenant($user, $stageTask)
            && $user->can('tasks.edit');
    }

    public function delete(User $user, StageTask $stageTask): bool
    {
        return $this->sameTenant($user, $stageTask)
            && $user->can('tasks.delete');
    }

    private function sameTenant(User $user, StageTask $stageTask): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        $resourceTenantId = (int) ($stageTask->stage?->project?->tenant_id ?? 0);

        return $resourceTenantId > 0 && TenantContext::id($user) === $resourceTenantId;
    }
}
