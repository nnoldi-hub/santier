<?php

namespace App\Policies;

use App\Models\StageReport;
use App\Models\User;
use App\Support\TenantContext;

class StageReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('reports.view');
    }

    public function view(User $user, StageReport $stageReport): bool
    {
        return $this->sameTenant($user, $stageReport)
            && ($this->legacyAllow($user) || $user->can('reports.view'));
    }

    public function create(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('reports.create');
    }

    public function update(User $user, StageReport $stageReport): bool
    {
        return $this->sameTenant($user, $stageReport)
            && ($this->legacyAllow($user) || $user->can('reports.edit'));
    }

    public function delete(User $user, StageReport $stageReport): bool
    {
        return $this->sameTenant($user, $stageReport)
            && ($this->legacyAllow($user) || $user->can('reports.delete'));
    }

    private function legacyAllow(User $user): bool
    {
        return $user->roles()->count() === 0 && $user->permissions()->count() === 0;
    }

    private function sameTenant(User $user, StageReport $stageReport): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        $resourceTenantId = (int) ($stageReport->stage?->project?->tenant_id ?? 0);

        return $resourceTenantId > 0 && TenantContext::id($user) === $resourceTenantId;
    }
}
