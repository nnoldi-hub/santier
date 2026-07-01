<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportSubscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'created_by',
        'name',
        'export_type',
        'format',
        'frequency',
        'schedule_time',
        'schedule_weekday',
        'filters',
        'recipients',
        'active',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'recipients' => 'array',
        'active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
