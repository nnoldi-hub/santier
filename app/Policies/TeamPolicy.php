<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Support\TenantContext;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $this->sameTenant($user, (int) $team->tenant_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $this->sameTenant($user, (int) $team->tenant_id);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->sameTenant($user, (int) $team->tenant_id);
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
