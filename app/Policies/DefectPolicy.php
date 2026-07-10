<?php

namespace App\Policies;

use App\Models\Defect;
use App\Models\User;
use App\Support\TenantContext;

class DefectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Defect $defect): bool
    {
        return $this->sameTenant($user, (int) $defect->tenant_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Defect $defect): bool
    {
        return $this->sameTenant($user, (int) $defect->tenant_id);
    }

    public function delete(User $user, Defect $defect): bool
    {
        return $this->sameTenant($user, (int) $defect->tenant_id);
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
