<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TaskTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'title',
    ];

    public function recipe(): MorphOne
    {
        return $this->morphOne(Recipe::class, 'subject');
    }

    public static function forEstimatePicker(int $tenantId): Collection
    {
        return static::where('tenant_id', $tenantId)
            ->with(['recipe.items.material:id,name,unit,unit_price'])
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (TaskTemplate $template) => [
                'id' => $template->id,
                'title' => $template->title,
                'recipe' => $template->recipe ? [
                    'id' => $template->recipe->id,
                    'unit' => $template->recipe->unit,
                    'items' => $template->recipe->items->map(fn (RecipeItem $item) => [
                        'material_id' => $item->material_id,
                        'material_name' => $item->material?->name,
                        'quantity_per_unit' => (float) $item->quantity_per_unit,
                        'unit' => $item->material?->unit,
                        'unit_price' => (float) ($item->material?->unit_price ?? 0),
                    ]),
                ] : null,
            ]);
    }
}
