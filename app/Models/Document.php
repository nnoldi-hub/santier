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
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'date',
        'file_size' => 'integer',
    ];

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
