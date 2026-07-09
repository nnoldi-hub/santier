<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceDelivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'resource_order_id',
        'declared_quantity',
        'received_quantity',
        'equipment_reported_quantity',
        'consumed_quantity',
        'returned_quantity',
        'delivered_at',
        'notes',
    ];

    protected $casts = [
        'declared_quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'equipment_reported_quantity' => 'decimal:2',
        'consumed_quantity' => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    public function resourceOrder(): BelongsTo
    {
        return $this->belongsTo(ResourceOrder::class);
    }
}
