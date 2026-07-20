<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Support\TenantContext;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return $this->sameTenant($user, (int) $supplier->tenant_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $this->sameTenant($user, (int) $supplier->tenant_id);
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $this->sameTenant($user, (int) $supplier->tenant_id);
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
