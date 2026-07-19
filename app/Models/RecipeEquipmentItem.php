<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeEquipmentItem extends Model
{
    protected $fillable = [
        'recipe_id',
        'equipment_id',
        'hours_per_unit',
    ];

    protected $casts = [
        'hours_per_unit' => 'decimal:4',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
