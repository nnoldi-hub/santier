<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;
use App\Support\TenantContext;

class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('equipment.view');
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $this->sameTenant($user, (int) $equipment->tenant_id)
            && $user->can('equipment.view');
    }

    public function create(User $user): bool
    {
        return $user->can('equipment.create');
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $this->sameTenant($user, (int) $equipment->tenant_id)
            && $user->can('equipment.edit');
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $this->sameTenant($user, (int) $equipment->tenant_id)
            && $user->can('equipment.delete');
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
