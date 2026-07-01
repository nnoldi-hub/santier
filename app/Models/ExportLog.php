<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'export_type',
        'format',
        'filters',
        'file_name',
        'file_size',
        'status',
        'delivery_channel',
        'delivery_target',
        'notes',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
