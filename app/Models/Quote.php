<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'project_id',
        'version',
        'title',
        'status',
        'valid_until',
        'discount_pct',
        'tva_pct',
        'notes',
        'meta',
        'total_net',
        'total_tva',
        'total_gross',
        'sent_at',
        'accepted_at',
        'internal_approved_at',
        'internal_approved_by',
        'created_by',
    ];

    protected $casts = [
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'internal_approved_at' => 'datetime',
        'discount_pct' => 'decimal:2',
        'tva_pct' => 'decimal:2',
        'meta' => 'array',
        'total_net' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_gross' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function internalApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'internal_approved_by');
    }
}
