<?php

namespace App\Policies;

use App\Models\Contractor;
use App\Models\User;
use App\Support\TenantContext;

class ContractorPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('contractors.view');
    }

    public function view(User $user, Contractor $contractor): bool
    {
        return $this->sameTenant($user, (int) $contractor->tenant_id)
            && ($this->legacyAllow($user) || $user->can('contractors.view'));
    }

    public function create(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('contractors.create');
    }

    public function update(User $user, Contractor $contractor): bool
    {
        return $this->sameTenant($user, (int) $contractor->tenant_id)
            && ($this->legacyAllow($user) || $user->can('contractors.edit'));
    }

    public function delete(User $user, Contractor $contractor): bool
    {
        return $this->sameTenant($user, (int) $contractor->tenant_id)
            && ($this->legacyAllow($user) || $user->can('contractors.delete'));
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
