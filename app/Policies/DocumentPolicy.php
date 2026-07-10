<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\TenantContext;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $this->sameTenant($user, (int) $document->tenant_id)
            && $user->can('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->sameTenant($user, (int) $document->tenant_id)
            && $user->can('documents.edit');
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->sameTenant($user, (int) $document->tenant_id)
            && $user->can('documents.delete');
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
