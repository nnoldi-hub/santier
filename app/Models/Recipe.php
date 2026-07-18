<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Recipe extends Model
{
    protected $fillable = [
        'tenant_id',
        'subject_type',
        'subject_id',
        'name',
        'unit',
        'notes',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }
}
