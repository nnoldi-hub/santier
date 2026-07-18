<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'category',
        'unit',
        'unit_price',
        'stock_quantity',
        'min_stock_quantity',
        'supplier',
        'notes',
        'active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'stock_quantity' => 'decimal:2',
        'min_stock_quantity' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_material')
            ->withPivot(['quantity', 'unit_override', 'unit_price'])
            ->withTimestamps();
    }

    public function resourceOrders(): HasMany
    {
        return $this->hasMany(ResourceOrder::class)->latest();
    }

    public function recipe(): MorphOne
    {
        return $this->morphOne(Recipe::class, 'subject');
    }
}
