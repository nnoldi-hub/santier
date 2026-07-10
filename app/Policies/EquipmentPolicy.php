<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;
use App\Support\TenantContext;

class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('equipment.view');
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $this->sameTenant($user, (int) $equipment->tenant_id)
            && ($this->legacyAllow($user) || $user->can('equipment.view'));
    }

    public function create(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('equipment.create');
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $this->sameTenant($user, (int) $equipment->tenant_id)
            && ($this->legacyAllow($user) || $user->can('equipment.edit'));
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $this->sameTenant($user, (int) $equipment->tenant_id)
            && ($this->legacyAllow($user) || $user->can('equipment.delete'));
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
