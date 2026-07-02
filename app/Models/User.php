<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\EmailCampaignLog;
use App\Models\Tenant;
use App\Models\TenantUser;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'current_tenant_id', 'is_superadmin', 'onboarding_step', 'onboarding_data', 'onboarding_completed_at', 'billing_plan', 'billing_trial_ends_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected string $guard_name = 'web';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_data' => 'array',
            'onboarding_completed_at' => 'datetime',
            'billing_trial_ends_at' => 'datetime',
            'is_superadmin' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function currentTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'current_tenant_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot(['department', 'status', 'invited_by', 'joined_at'])
            ->withTimestamps();
    }

    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function reportedDefects(): HasMany
    {
        return $this->hasMany(Defect::class, 'reported_by');
    }

    public function assignedDefects(): HasMany
    {
        return $this->hasMany(Defect::class, 'assigned_to');
    }

    public function emailCampaignLogs(): HasMany
    {
        return $this->hasMany(EmailCampaignLog::class);
    }

    public function projectRoleAssignments(): HasMany
    {
        return $this->hasMany(ProjectUserRole::class);
    }

    public function projectRoleProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user_roles')
            ->withPivot(['project_role_id', 'tenant_id'])
            ->withTimestamps();
    }

    public function hasAnyProjectRoleAssignments(?int $tenantId = null): bool
    {
        $query = $this->projectRoleAssignments();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->exists();
    }

    public function hasProjectRole(Project|int $project, array|string $roleKeys = []): bool
    {
        $projectId = $project instanceof Project ? (int) $project->id : (int) $project;
        $keys = is_array($roleKeys) ? $roleKeys : [$roleKeys];

        $query = $this->projectRoleAssignments()
            ->where('project_id', $projectId)
            ->where('tenant_id', (int) ($this->current_tenant_id ?: $this->tenant_id));

        if (!empty($keys)) {
            $query->whereHas('projectRole', function ($roleQuery) use ($keys): void {
                $roleQuery->whereIn('key', $keys);
            });
        }

        return $query->exists();
    }
}
