<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'client_id', 'created_by', 'name', 'description',
        'address', 'status', 'start_date', 'end_date', 'total_budget', 'notes',
        'plan_approved_at', 'plan_approved_by',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'end_date'         => 'date',
        'total_budget'     => 'decimal:2',
        'plan_approved_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function planApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'plan_approved_by');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->latest();
    }

    public function defects(): HasMany
    {
        return $this->hasMany(Defect::class)->latest();
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->latest();
    }

    public function resourceOrders(): HasMany
    {
        return $this->hasMany(ResourceOrder::class)->latest();
    }

    public function projectRoleAssignments(): HasMany
    {
        return $this->hasMany(ProjectUserRole::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user_roles')
            ->withPivot(['project_role_id', 'tenant_id'])
            ->withTimestamps();
    }

    public function projectRoles(): BelongsToMany
    {
        return $this->belongsToMany(ProjectRole::class, 'project_user_roles')
            ->withPivot(['user_id', 'tenant_id'])
            ->withTimestamps();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'    => 'green',
            'paused'    => 'yellow',
            'completed' => 'blue',
            'cancelled' => 'red',
            default     => 'gray',
        };
    }
}
