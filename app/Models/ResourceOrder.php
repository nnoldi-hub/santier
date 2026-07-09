<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceOrder extends Model
{
    use SoftDeletes;

    public static array $resourceTypeLabels = [
        'material' => 'Material',
        'equipment' => 'Utilaj',
    ];

    public static array $statusLabels = [
        'draft' => 'Draft',
        'ordered' => 'Comandata',
        'delivered' => 'Livrata',
        'verified' => 'Verificata',
        'financial_review' => 'In validare financiara',
        'blocked_payment' => 'Blocat la plata',
        'approved' => 'Aprobata',
        'rejected' => 'Respinsa',
    ];

    public static array $documentTypeLabels = [
        'delivery_note' => 'Aviz de livrare',
        'carrier_note' => 'Aviz transportator',
        'pump_note' => 'Aviz pompa / utilaj',
        'resource_invoice' => 'Factura resursa',
        'site_photo' => 'Poza santier',
        'receipt_confirmation' => 'Confirmare receptie',
        'quantity_confirmation' => 'Confirmare cantitate',
        'quality_confirmation' => 'Confirmare calitate',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'resource_type',
        'material_id',
        'equipment_id',
        'supplier_name',
        'carrier_name',
        'equipment_name',
        'ordered_quantity',
        'ordered_unit',
        'unit_price',
        'delivery_date',
        'responsible_user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'ordered_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'delivery_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ResourceDelivery::class)->latest('delivered_at');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(ResourceConfirmation::class)->latest();
    }

    public function documentLinks(): HasMany
    {
        return $this->hasMany(ResourceDocumentLink::class)->latest();
    }
}
