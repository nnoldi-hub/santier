<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'supplier',
        'notes',
        'active',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'active' => 'boolean',
    ];
}
