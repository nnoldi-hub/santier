<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteStaffPlan extends Model
{
    public static array $riskLabels = [
        'low' => 'Scazut',
        'medium' => 'Mediu',
        'high' => 'Ridicat',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'team_id',
        'contractor_id',
        'specialty',
        'planned_headcount',
        'planned_start',
        'planned_end',
        'risk_level',
        'notes',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_end' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
