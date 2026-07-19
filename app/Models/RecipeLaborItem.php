<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeLaborItem extends Model
{
    protected $fillable = [
        'recipe_id',
        'role',
        'hours_per_unit',
        'hourly_rate',
    ];

    protected $casts = [
        'hours_per_unit' => 'decimal:4',
        'hourly_rate' => 'decimal:2',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
