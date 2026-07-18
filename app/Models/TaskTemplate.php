<?php

namespace App\Models;

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
}
