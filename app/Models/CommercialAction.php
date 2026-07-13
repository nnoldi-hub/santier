<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialAction extends Model
{
    public static array $typeLabels = [
        'apel' => 'Apel',
        'email' => 'Email',
        'demo' => 'Demo',
        'oferta' => 'Oferta',
        'follow_up' => 'Follow-up',
        'negociere' => 'Negociere',
    ];

    protected $fillable = [
        'tenant_id',
        'pilot_invite_id',
        'actor_id',
        'action_type',
        'notes',
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
