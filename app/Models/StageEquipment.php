<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageEquipment extends Model
{
    protected $table = 'stage_equipment';

    protected $fillable = [
        'stage_id',
        'equipment_id',
        'quantity',
        'usage_start',
        'usage_end',
        'notes',
    ];

    protected $casts = [
        'usage_start' => 'date',
        'usage_end' => 'date',
        'quantity' => 'integer',
    ];

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'stage_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'equipment_id');
    }
}
