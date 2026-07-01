<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StageReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stage_id',
        'contractor_id',
        'report_date',
        'progress_pct',
        'activities',
        'issues',
        'materials_used',
        'equipment_used',
        'images',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'progress_pct' => 'integer',
        'materials_used' => 'array',
        'equipment_used' => 'array',
        'images' => 'array',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'stage_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
