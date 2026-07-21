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
        'drying_hours',
        'curing_hours',
        'default_checklist',
    ];

    protected $casts = [
        'drying_hours' => 'decimal:2',
        'curing_hours' => 'decimal:2',
        'default_checklist' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function laborItems(): HasMany
    {
        return $this->hasMany(RecipeLaborItem::class);
    }

    public function equipmentItems(): HasMany
    {
        return $this->hasMany(RecipeEquipmentItem::class);
    }

    public function wbsStages(): HasMany
    {
        return $this->hasMany(RecipeWbsStage::class)->orderBy('order');
    }
}
