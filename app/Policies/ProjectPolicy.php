<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectRole;
use App\Models\User;
use App\Support\ExternalPortalScope;
use App\Support\TenantContext;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->sameTenant($user, (int) $project->tenant_id)
            && $this->canAccessProject($user, $project)
            && $this->hasProjectAccessByDynamicRole($user, $project, [ProjectRole::OWNER, ProjectRole::CONTRIBUTOR, ProjectRole::VIEWER])
            && $user->can('projects.view');
    }

    public function create(User $user): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->sameTenant($user, (int) $project->tenant_id)
            && $this->hasProjectAccessByDynamicRole($user, $project, [ProjectRole::OWNER, ProjectRole::CONTRIBUTOR])
            && $user->can('projects.edit');
    }

    public function delete(User $user, Project $project): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->sameTenant($user, (int) $project->tenant_id)
            && $this->hasProjectAccessByDynamicRole($user, $project, [ProjectRole::OWNER])
            && $user->can('projects.delete');
    }

    public function manageRoles(User $user, Project $project): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        if (! $this->sameTenant($user, (int) $project->tenant_id)) {
            return false;
        }

        if (TenantContext::isSuperadmin($user) || $user->hasRole('tenant_admin')) {
            return true;
        }

        if (! $user->hasAnyProjectRoleAssignments(TenantContext::id($user))) {
            return $user->can('projects.edit');
        }

        return $user->hasProjectRole($project, [ProjectRole::OWNER]);
    }

    /**
     * @param  array<int, string>  $allowedRoleKeys
     */
    private function hasProjectAccessByDynamicRole(User $user, Project $project, array $allowedRoleKeys): bool
    {
        if (TenantContext::isSuperadmin($user) || ExternalPortalScope::isExternal($user)) {
            return true;
        }

        if (!$user->hasAnyProjectRoleAssignments(TenantContext::id($user))) {
            return true;
        }

        return $user->hasProjectRole($project, $allowedRoleKeys);
    }

    private function canAccessProject(User $user, Project $project): bool
    {
        if (!ExternalPortalScope::isExternal($user)) {
            return true;
        }

        return ExternalPortalScope::canAccessProject($user, $project);
    }

    private function sameTenant(User $user, int $resourceTenantId): bool
    {
        if (TenantContext::isSuperadmin($user)) {
            return true;
        }

        return TenantContext::id($user) === $resourceTenantId;
    }
}
