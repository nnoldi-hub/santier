<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'company_name',
        'company_cui',
        'company_address',
        'contact_name',
        'contact_email',
        'contact_phone',
        'plan',
        'interval',
        'discount_pct',
        'status',
        'sent_at',
        'ip_address',
    ];

    protected $casts = [
        'discount_pct' => 'integer',
        'sent_at' => 'datetime',
    ];
}
