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
        return $this->legacyAllow($user) || $user->can('projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->sameTenant($user, (int) $project->tenant_id)
            && $this->canAccessProject($user, $project)
            && $this->hasProjectAccessByDynamicRole($user, $project, [ProjectRole::OWNER, ProjectRole::CONTRIBUTOR, ProjectRole::VIEWER])
            && ($this->legacyAllow($user) || $user->can('projects.view'));
    }

    public function create(User $user): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->legacyAllow($user) || $user->can('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->sameTenant($user, (int) $project->tenant_id)
            && $this->hasProjectAccessByDynamicRole($user, $project, [ProjectRole::OWNER, ProjectRole::CONTRIBUTOR])
            && ($this->legacyAllow($user) || $user->can('projects.edit'));
    }

    public function delete(User $user, Project $project): bool
    {
        if (ExternalPortalScope::isExternal($user)) {
            return false;
        }

        return $this->sameTenant($user, (int) $project->tenant_id)
            && $this->hasProjectAccessByDynamicRole($user, $project, [ProjectRole::OWNER])
            && ($this->legacyAllow($user) || $user->can('projects.delete'));
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
            return $this->legacyAllow($user) || $user->can('projects.edit');
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
