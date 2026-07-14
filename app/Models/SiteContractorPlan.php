<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteContractorPlan extends Model
{
    public static array $contractStatusLabels = [
        'draft' => 'Draft',
        'signed' => 'Semnat',
        'missing' => 'Lipsa',
    ];

    public static array $availabilityLabels = [
        'ok' => 'Disponibil',
        'risk' => 'Risc',
        'conflict' => 'Conflict',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'contractor_id',
        'contract_status',
        'availability_status',
        'planned_start',
        'planned_end',
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

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }
}
