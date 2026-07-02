<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use App\Support\ExternalPortalScope;
use App\Support\TenantContext;

class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->legacyAllow($user) || $user->can('quotes.view');
    }

    public function view(User $user, Quote $quote): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->sameTenant($user, (int) $quote->tenant_id)
            && ($this->legacyAllow($user) || $user->can('quotes.view'));
    }

    public function create(User $user): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->legacyAllow($user) || $user->can('quotes.create');
    }

    public function update(User $user, Quote $quote): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->sameTenant($user, (int) $quote->tenant_id)
            && ($this->legacyAllow($user) || $user->can('quotes.edit'));
    }

    public function delete(User $user, Quote $quote): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->sameTenant($user, (int) $quote->tenant_id)
            && ($this->legacyAllow($user) || $user->can('quotes.delete'));
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
