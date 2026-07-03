<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'deadline',
        'checklist',
        'completed_at',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'checklist' => 'array',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'task_material')
            ->withPivot(['quantity', 'unit_override', 'unit_price'])
            ->withTimestamps();
    }
}
