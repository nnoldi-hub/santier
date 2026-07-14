<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteEquipmentPlan extends Model
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
        'equipment_id',
        'quantity',
        'usage_start',
        'usage_end',
        'risk_level',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'usage_start' => 'date',
        'usage_end' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
