<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public static array $receptionTypeLabels = [
        'partial' => 'Receptie partiala',
        'final' => 'Receptie finala',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'assigned_to',
        'title',
        'description',
        'checklist',
        'check_type',
        'reception_type',
        'status',
        'planned_at',
        'completed_at',
        'notes',
        'signature_path',
        'signed_by_name',
        'signed_at',
    ];

    protected $casts = [
        'checklist' => 'array',
        'planned_at' => 'datetime',
        'completed_at' => 'datetime',
        'signed_at' => 'datetime',
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

    public function photos(): HasMany
    {
        return $this->hasMany(QualityCheckPhoto::class);
    }
}
