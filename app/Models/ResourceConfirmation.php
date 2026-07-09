<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceConfirmation extends Model
{
    use SoftDeletes;

    public static array $roleLabels = [
        'site_manager' => 'Sef santier',
        'execution_manager' => 'Responsabil executie',
        'quality_manager' => 'Responsabil calitate',
        'financial_manager' => 'Responsabil financiar',
    ];

    public static array $statusLabels = [
        'pending' => 'In asteptare',
        'confirmed' => 'Confirmat',
        'rejected' => 'Respins',
    ];

    protected $fillable = [
        'tenant_id',
        'resource_order_id',
        'confirmation_role',
        'confirmed_by',
        'status',
        'confirmed_at',
        'notes',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function resourceOrder(): BelongsTo
    {
        return $this->belongsTo(ResourceOrder::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
