<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectPhase extends Model
{
    protected $fillable = [
        'project_id', 'name', 'type', 'order', 'start_date', 'end_date',
        'duration_days', 'buffer_days', 'status', 'progress_pct', 'contractor_id', 'parent_id', 'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'buffer_days' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'phase_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PhaseTeamAssignment::class, 'phase_id')->latest();
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectPhase::class, 'parent_id')->orderBy('order');
    }

    public function equipmentReservations(): HasMany
    {
        return $this->hasMany(StageEquipment::class, 'stage_id')->latest();
    }

    public function defects(): HasMany
    {
        return $this->hasMany(Defect::class, 'phase_id')->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'stage_id')->latest();
    }

    public function resourceOrders(): HasMany
    {
        return $this->hasMany(ResourceOrder::class, 'phase_id')->latest();
    }

    public function stageReports(): HasMany
    {
        return $this->hasMany(StageReport::class, 'stage_id')->latest('report_date');
    }

    public function stageTasks(): HasMany
    {
        return $this->hasMany(StageTask::class, 'stage_id')->latest();
    }

    public static array $typeLabels = [
        'demolare'         => 'Demolare',
        'structura'        => 'Structura',
        'instalatii_brute' => 'Instalatii brute',
        'tencuieli'        => 'Tencuieli',
        'sape'             => 'Sape',
        'glet'             => 'Glet',
        'finisaje_umede'   => 'Finisaje umede',
        'montaj_tamplarie' => 'Montaj tamplarie',
        'zugraveli'        => 'Zugraveli',
        'pardoseli'        => 'Pardoseli',
        'finisaje_fine'    => 'Finisaje fine',
        'curatenie'        => 'Curatenie',
        'custom'           => 'Alta etapa',
    ];
}
