<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityCheckPhoto extends Model
{
    protected $fillable = [
        'tenant_id',
        'quality_check_id',
        'path',
        'name',
    ];

    public function qualityCheck(): BelongsTo
    {
        return $this->belongsTo(QualityCheck::class);
    }
}
