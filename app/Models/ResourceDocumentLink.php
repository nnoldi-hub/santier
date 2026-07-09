<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceDocumentLink extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'resource_order_id',
        'document_id',
        'document_role',
        'document_number',
        'supplier_name',
        'carrier_name',
        'equipment_name',
        'declared_quantity',
        'delivered_quantity',
        'difference_quantity',
        'notes',
    ];

    protected $casts = [
        'declared_quantity' => 'decimal:2',
        'delivered_quantity' => 'decimal:2',
        'difference_quantity' => 'decimal:2',
    ];

    public function resourceOrder(): BelongsTo
    {
        return $this->belongsTo(ResourceOrder::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
