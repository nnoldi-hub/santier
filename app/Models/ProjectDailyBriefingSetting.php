<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDailyBriefingSetting extends Model
{
    public static array $detailLevelLabels = [
        'complet' => 'Complet',
        'esential' => 'Esential',
        'doar_blocaje' => 'Doar blocaje',
    ];

    public static array $defaultChannels = [
        'email' => true,
        'in_app' => true,
        'whatsapp' => false,
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'enabled',
        'send_time',
        'recipient_user_ids',
        'detail_level',
        'channels',
        'last_sent_date',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'send_time' => 'datetime:H:i',
        'recipient_user_ids' => 'array',
        'channels' => 'array',
        'last_sent_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
