<?php

namespace App\Policies;

use App\Models\MaterialInvoice;
use App\Models\User;
use App\Support\TenantContext;

class MaterialInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('finance.view');
    }

    public function view(User $user, MaterialInvoice $materialInvoice): bool
    {
        return $this->sameTenant($user, (int) $materialInvoice->tenant_id)
            && $user->can('finance.view');
    }

    public function create(User $user): bool
    {
        return $user->can('finance.create');
    }

    public function update(User $user, MaterialInvoice $materialInvoice): bool
    {
        return $this->sameTenant($user, (int) $materialInvoice->tenant_id)
            && $user->can('finance.edit');
    }

    public function delete(User $user, MaterialInvoice $materialInvoice): bool
    {
        return $this->sameTenant($user, (int) $materialInvoice->tenant_id)
            && $user->can('finance.delete');
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
