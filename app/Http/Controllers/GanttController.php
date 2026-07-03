<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GanttController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $projectId = $request->integer('project_id');
        $scope = $request->string('scope')->toString() === 'all' ? 'all' : 'single';

        $projects = Project::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($projectId <= 0 && $projects->count() > 0) {
            $projectId = (int) $projects->first()->id;
        }

        $phases = collect();
        $project = null;

        if ($scope === 'all') {
            $phases = ProjectPhase::query()
                ->with('project:id,name')
                ->whereHas('project', fn ($query) => $query->where('tenant_id', $tenantId))
                ->orderBy('project_id')
                ->orderBy('order')
                ->get(['id', 'project_id', 'name', 'status', 'progress_pct', 'start_date', 'end_date', 'duration_days'])
                ->map(fn (ProjectPhase $phase) => [
                    'id' => $phase->id,
                    'project_id' => $phase->project_id,
                    'project_name' => $phase->project?->name,
                    'name' => $phase->name,
                    'status' => $phase->status,
                    'progress_pct' => $phase->progress_pct,
                    'start_date' => $phase->start_date,
                    'end_date' => $phase->end_date,
                    'duration_days' => $phase->duration_days,
                ]);
        } elseif ($projectId > 0) {
            $project = Project::where('tenant_id', $tenantId)->find($projectId, ['id', 'name', 'status', 'start_date', 'end_date']);

            if ($project) {
                $phases = ProjectPhase::where('project_id', $project->id)
                    ->orderBy('order')
                    ->get(['id', 'project_id', 'name', 'status', 'progress_pct', 'start_date', 'end_date', 'duration_days'])
                    ->map(fn (ProjectPhase $phase) => [
                        'id' => $phase->id,
                        'project_id' => $phase->project_id,
                        'project_name' => $project->name,
                        'name' => $phase->name,
                        'status' => $phase->status,
                        'progress_pct' => $phase->progress_pct,
                        'start_date' => $phase->start_date,
                        'end_date' => $phase->end_date,
                        'duration_days' => $phase->duration_days,
                    ]);
            }
        }

        return Inertia::render('Gantt/Index', [
            'projects' => $projects,
            'selectedProjectId' => $projectId > 0 ? $projectId : null,
            'selectedProject' => $project,
            'phases' => $phases,
            'scope' => $scope,
        ]);
    }
}
