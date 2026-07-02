<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUserRole extends Model
{
    protected $fillable = [
        'tenant_id',
        'project_id',
        'user_id',
        'project_role_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projectRole(): BelongsTo
    {
        return $this->belongsTo(ProjectRole::class);
    }
}
