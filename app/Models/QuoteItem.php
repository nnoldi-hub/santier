<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'quote_id',
        'item_type',
        'reference_id',
        'name',
        'stage_name',
        'unit',
        'quantity',
        'cost_unit_price',
        'sell_unit_price',
        'line_cost_total',
        'line_sell_total',
        'sort_order',
        'stage_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'cost_unit_price' => 'decimal:2',
        'sell_unit_price' => 'decimal:2',
        'line_cost_total' => 'decimal:2',
        'line_sell_total' => 'decimal:2',
        'sort_order' => 'integer',
        'stage_order' => 'integer',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
