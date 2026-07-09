<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilotInvite extends Model
{
    protected $fillable = [
        'tenant_id',
        'owner_id',
        'company_name',
        'segment',
        'contact_name',
        'contact_email',
        'contact_phone',
        'status',
        'commercial_stage',
        'invited_at',
        'demo_scheduled_at',
        'follow_up_at',
        'last_contacted_at',
        'next_step',
        'notes',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'demo_scheduled_at' => 'datetime',
        'follow_up_at' => 'datetime',
        'last_contacted_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
