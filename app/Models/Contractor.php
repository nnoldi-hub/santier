<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contractor extends Model
{
    use SoftDeletes;

    public const TYPE_INTERNAL_TEAM = 'internal_team';
    public const TYPE_SUBCONTRACTOR = 'subcontractor';
    public const TYPE_PFA = 'pfa';
    public const TYPE_EQUIPMENT_SUPPLIER = 'equipment_supplier';
    public const TYPE_MATERIALS_SUPPLIER = 'materials_supplier';

    public static array $typeLabels = [
        self::TYPE_INTERNAL_TEAM => 'Echipa interna',
        self::TYPE_SUBCONTRACTOR => 'Subcontractor',
        self::TYPE_PFA => 'PFA',
        self::TYPE_EQUIPMENT_SUPPLIER => 'Furnizor utilaje',
        self::TYPE_MATERIALS_SUPPLIER => 'Furnizor materiale',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'contact_name',
        'phone',
        'email',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class);
    }
}
