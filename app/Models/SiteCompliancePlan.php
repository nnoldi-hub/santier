<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteCompliancePlan extends Model
{
    public static array $itemTypeLabels = [
        'contract' => 'Contract',
        'aviz' => 'Aviz',
        'autorizatie' => 'Autorizatie',
    ];

    public static array $statusLabels = [
        'valid' => 'Valid',
        'expiring_soon' => 'Expira curand',
        'expired' => 'Expirat',
        'missing' => 'Lipsa',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'contractor_id',
        'item_type',
        'title',
        'status',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
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
