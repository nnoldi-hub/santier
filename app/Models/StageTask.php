<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StageTask extends Model
{
    use SoftDeletes;

    public static array $statusLabels = [
        'todo' => 'De facut',
        'in_progress' => 'In progres',
        'done' => 'Finalizat',
        'blocked' => 'Blocat',
    ];

    public static array $assigneeTypes = [
        'user' => 'Utilizator intern',
        'team' => 'Echipa',
        'contractor' => 'Contractor',
    ];

    protected $fillable = [
        'stage_id',
        'title',
        'description',
        'assignee_type',
        'assignee_id',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline' => 'datetime',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'stage_id');
    }

    public function userAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function teamAssignee(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assignee_id');
    }

    public function contractorAssignee(): BelongsTo
    {
        return $this->belongsTo(Contractor::class, 'assignee_id');
    }
}
