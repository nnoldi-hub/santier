<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExternalPortalScope
{
    public static function isClientPortal(?User $user): bool
    {
        return $user !== null && $user->hasRole('client_portal');
    }

    public static function isSubcontractorPortal(?User $user): bool
    {
        return $user !== null && $user->hasRole('subcontractor_portal');
    }

    public static function isExternal(?User $user): bool
    {
        return self::isClientPortal($user) || self::isSubcontractorPortal($user);
    }

    public static function applyProjectScope(Builder $query, ?User $user): Builder
    {
        if ($user === null || !self::isExternal($user)) {
            return $query;
        }

        $email = strtolower((string) $user->email);

        return $query->where(function (Builder $scoped) use ($email, $user): void {
            if (self::isClientPortal($user)) {
                $scoped->orWhereHas('client', fn (Builder $clientQuery) => $clientQuery->whereRaw('LOWER(email) = ?', [$email]));
            }

            if (self::isSubcontractorPortal($user)) {
                $scoped->orWhereHas('phases.contractor', fn (Builder $contractorQuery) => $contractorQuery->whereRaw('LOWER(email) = ?', [$email]));
            }
        });
    }

    public static function canAccessProject(User $user, Project|int $project): bool
    {
        $projectId = $project instanceof Project ? (int) $project->id : (int) $project;

        $query = Project::query()
            ->whereKey($projectId)
            ->where('tenant_id', TenantContext::id($user));

        self::applyProjectScope($query, $user);

        return $query->exists();
    }
}
