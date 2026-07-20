<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteStaffTimeEntry extends Model
{
    protected $fillable = [
        'tenant_id',
        'project_id',
        'staff_plan_id',
        'entry_date',
        'hours_worked',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'hours_worked' => 'decimal:2',
    ];

    public function staffPlan(): BelongsTo
    {
        return $this->belongsTo(SiteStaffPlan::class, 'staff_plan_id');
    }
}
