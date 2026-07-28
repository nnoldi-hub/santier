<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilotInviteMessage extends Model
{
    protected $fillable = [
        'tenant_id',
        'pilot_invite_id',
        'actor_id',
        'direction',
        'from_email',
        'from_name',
        'subject',
        'body',
        'message_id',
        'in_reply_to_message_id',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function pilotInvite(): BelongsTo
    {
        return $this->belongsTo(PilotInvite::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
