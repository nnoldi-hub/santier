<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\TenantContext;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $this->sameTenant($user, (int) $document->tenant_id)
            && ($this->legacyAllow($user) || $user->can('documents.view'));
    }

    public function create(User $user): bool
    {
        return $this->legacyAllow($user) || $user->can('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->sameTenant($user, (int) $document->tenant_id)
            && ($this->legacyAllow($user) || $user->can('documents.edit'));
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->sameTenant($user, (int) $document->tenant_id)
            && ($this->legacyAllow($user) || $user->can('documents.delete'));
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
