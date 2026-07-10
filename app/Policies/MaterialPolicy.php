<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;
use App\Support\TenantContext;

class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Material $material): bool
    {
        return $this->sameTenant($user, (int) $material->tenant_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Material $material): bool
    {
        return $this->sameTenant($user, (int) $material->tenant_id);
    }

    public function delete(User $user, Material $material): bool
    {
        return $this->sameTenant($user, (int) $material->tenant_id);
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
