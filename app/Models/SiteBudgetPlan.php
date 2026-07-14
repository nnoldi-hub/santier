<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteBudgetPlan extends Model
{
    public static array $categoryLabels = [
        'labor' => 'Manopera',
        'subcontractors' => 'Subcontractori',
        'logistics' => 'Logistica',
        'compliance' => 'Conformitate',
        'contingency' => 'Rezerva',
        'other' => 'Altele',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'category',
        'description',
        'estimated_cost',
        'notes',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }
}
