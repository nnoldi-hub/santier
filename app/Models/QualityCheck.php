<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityCheck extends Model
{
    use SoftDeletes;

    public static array $statusLabels = [
        'pending' => 'In asteptare',
        'in_progress' => 'In verificare',
        'passed' => 'Conform',
        'failed' => 'Neconform',
    ];

    public static array $typeLabels = [
        'execution' => 'Executie',
        'materials' => 'Materiale',
        'safety' => 'Siguranta',
        'handover' => 'Predare',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'assigned_to',
        'title',
        'description',
        'check_type',
        'status',
        'planned_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'planned_at' => 'datetime',
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
}
