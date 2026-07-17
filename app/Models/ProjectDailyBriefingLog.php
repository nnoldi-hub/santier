<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyBriefingLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'project_id',
        'briefing_date',
        'sent_at',
        'risk_level',
        'blockers_count',
        'recipients_count',
        'channels',
        'snapshot',
    ];

    protected $casts = [
        'briefing_date' => 'date',
        'sent_at' => 'datetime',
        'channels' => 'array',
        'snapshot' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
