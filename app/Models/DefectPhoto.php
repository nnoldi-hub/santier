<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefectPhoto extends Model
{
    protected $fillable = [
        'tenant_id',
        'defect_id',
        'path',
        'name',
    ];

    public function defect(): BelongsTo
    {
        return $this->belongsTo(Defect::class);
    }
}
