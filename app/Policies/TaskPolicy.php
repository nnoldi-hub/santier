<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Support\TenantContext;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('tasks.view');
    }

    public function view(User $user, Task $task): bool
    {
        return $this->sameTenant($user, (int) $task->tenant_id)
            && ($this->legacyAllow($user) || $user->can('tasks.view'));
    }

    public function create(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('tasks.create');
    }

    public function update(User $user, Task $task): bool
    {
        return $this->sameTenant($user, (int) $task->tenant_id)
            && ($this->legacyAllow($user) || $user->can('tasks.edit'));
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->sameTenant($user, (int) $task->tenant_id)
            && ($this->legacyAllow($user) || $user->can('tasks.delete'));
    }

    private function legacyAllow(User $user): bool
    {
        return $user->roles()->count() === 0 && $user->permissions()->count() === 0;
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
