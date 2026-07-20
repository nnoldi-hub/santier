<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialInvoice extends Model
{
    use SoftDeletes;

    public static array $paymentStatusLabels = [
        'unpaid' => 'Neplatita',
        'partial' => 'Partial platita',
        'paid' => 'Platita',
        'cancelled' => 'Anulata',
    ];

    protected $fillable = [
        'tenant_id',
        'project_id',
        'phase_id',
        'material_id',
        'supplier_id',
        'supplier_name',
        'invoice_no',
        'issue_date',
        'due_date',
        'amount_net',
        'amount_vat',
        'amount_total',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount_net' => 'decimal:2',
        'amount_vat' => 'decimal:2',
        'amount_total' => 'decimal:2',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
