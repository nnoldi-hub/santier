<?php

namespace App\Support;

use App\Models\AccessAuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AccessAudit
{
    /**
     * Persist an IAM-related audit event.
     */
    public static function log(
        string $action,
        ?User $actor,
        ?Request $request = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $metadata = []
    ): void {
        AccessAuditLog::query()->create([
            'tenant_id' => $actor ? TenantContext::id($actor) : null,
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $metadata,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
