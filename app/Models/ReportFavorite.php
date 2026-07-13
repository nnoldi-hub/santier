<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportFavorite extends Model
{
    public const FORMATS = ['csv', 'xlsx', 'pdf'];

    protected $fillable = [
        'tenant_id',
        'user_id',
        'label',
        'export_type',
        'format',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
