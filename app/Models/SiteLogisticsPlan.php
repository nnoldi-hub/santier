<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteLogisticsPlan extends Model
{
    public static array $categoryLabels = [
        'access' => 'Acces',
        'storage' => 'Depozitare',
        'safety_zone' => 'Zona de siguranta',
        'restriction' => 'Restrictie',
    ];

    public static array $riskLabels = [
        'low' => 'Scazut',
        'medium' => 'Mediu',
        'high' => 'Ridicat',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'category',
        'title',
        'location_description',
        'capacity_notes',
        'risk_level',
        'notes',
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
