<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMaterialPlan extends Model
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
        'material_id',
        'planned_quantity',
        'supplier_name',
        'lead_time_days',
        'planned_order_date',
        'planned_delivery_date',
        'risk_level',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:2',
        'planned_order_date' => 'date',
        'planned_delivery_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
