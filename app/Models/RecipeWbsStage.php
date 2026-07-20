<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeWbsStage extends Model
{
    protected $fillable = [
        'recipe_id',
        'name',
        'order',
        'default_tasks',
    ];

    protected $casts = [
        'default_tasks' => 'array',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
