<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    public static array $typeLabels = [
        'contract' => 'Contract',
        'invoice' => 'Factura',
        'estimate' => 'Deviz',
        'offer' => 'Oferta',
        'delivery_note' => 'Aviz de livrare',
        'carrier_note' => 'Aviz transportator',
        'pump_note' => 'Aviz pompa / utilaj',
        'resource_invoice' => 'Factura resursa',
        'site_photo' => 'Poza santier',
        'receipt_confirmation' => 'Confirmare receptie',
        'quantity_confirmation' => 'Confirmare cantitate',
        'quality_confirmation' => 'Confirmare calitate',
        'proc_verbal_receptie' => 'Proces verbal de receptie',
        'proc_verbal_constatare' => 'Proces verbal de constatare',
        'proc_verbal_lucrari_ascunse' => 'Proces verbal de lucrari ascunse',
    ];

    public static array $paymentStatusLabels = [
        'unpaid' => 'Neplatit',
        'partial' => 'Plata partiala',
        'paid' => 'Platit',
        'cancelled' => 'Anulat',
    ];

    protected $fillable = [
        'tenant_id',
        'contractor_id',
        'project_id',
        'stage_id',
        'type',
        'amount',
        'issued_at',
        'payment_status',
        'title',
        'invoice_number',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'notes',
        'type_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'date',
        'file_size' => 'integer',
        'type_data' => 'array',
    ];

    public function getTypeLabelAttribute(): string
    {
        return static::$typeLabels[$this->type] ?? (string) $this->type;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return static::$paymentStatusLabels[$this->payment_status] ?? (string) $this->payment_status;
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'stage_id');
    }
}
