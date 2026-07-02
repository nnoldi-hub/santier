<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class DemoScope
{
    public static function isDemoUser(?User $user): bool
    {
        return $user !== null
            && $user->email === config('demo.email', 'demo@santier.local');
    }

    public static function applyProjectScope(Builder|QueryBuilder $query, ?User $user): Builder|QueryBuilder
    {
        $tenantId = TenantContext::id($user);

        $query->where('tenant_id', $tenantId);

        if (ExternalPortalScope::isExternal($user)) {
            if ($query instanceof Builder) {
                ExternalPortalScope::applyProjectScope($query, $user);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user !== null && !TenantContext::isSuperadmin($user) && $user->hasAnyProjectRoleAssignments($tenantId)) {
            if ($query instanceof Builder) {
                $query->whereHas('projectRoleAssignments', function (Builder $assignmentQuery) use ($tenantId, $user): void {
                    $assignmentQuery
                        ->where('tenant_id', $tenantId)
                        ->where('user_id', $user->id);
                });
            } else {
                $query->whereExists(function ($subQuery) use ($tenantId, $user): void {
                    $subQuery
                        ->selectRaw('1')
                        ->from('project_user_roles')
                        ->whereColumn('project_user_roles.project_id', 'projects.id')
                        ->where('project_user_roles.tenant_id', $tenantId)
                        ->where('project_user_roles.user_id', $user->id);
                });
            }
        }

        if (self::isDemoUser($user)) {
            $query->where('created_by', $user->id);
        }

        return $query;
    }
}