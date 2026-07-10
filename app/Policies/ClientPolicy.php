<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Support\TenantContext;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return $this->sameTenant($user, (int) $client->tenant_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Client $client): bool
    {
        return $this->sameTenant($user, (int) $client->tenant_id);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->sameTenant($user, (int) $client->tenant_id);
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
