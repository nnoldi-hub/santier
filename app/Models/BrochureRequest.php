<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrochureRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'company',
        'sent_at',
        'ip_address',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
