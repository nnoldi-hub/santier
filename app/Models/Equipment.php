<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use SoftDeletes;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_UNAVAILABLE = 'unavailable';

    public static array $typeLabels = [
        'excavator' => 'Excavator',
        'bulldozer' => 'Buldozer',
        'crane' => 'Macara',
        'concrete_mixer' => 'Betoniera',
        'generator' => 'Generator',
        'scaffold' => 'Schela',
        'custom' => 'Alt utilaj',
    ];

    public static array $availabilityLabels = [
        self::STATUS_AVAILABLE => 'Disponibil',
        self::STATUS_RESERVED => 'Rezervat',
        self::STATUS_MAINTENANCE => 'In mentenanta',
        self::STATUS_UNAVAILABLE => 'Indisponibil',
    ];

    protected $table = 'equipment';

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'supplier_name',
        'cost_per_hour',
        'availability_status',
        'active',
        'notes',
    ];

    protected $casts = [
        'cost_per_hour' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(StageEquipment::class, 'equipment_id')->latest();
    }
}
