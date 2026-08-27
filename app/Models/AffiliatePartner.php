<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatePartner extends Model
{
    protected $fillable = [
        'name',
        'code',
        'email',
        'commission_rate',
        'active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
