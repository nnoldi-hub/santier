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

        $projects = Project::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($projectId <= 0 && $projects->count() > 0) {
            $projectId = (int) $projects->first()->id;
        }

        $phases = collect();
        $project = null;

        if ($projectId > 0) {
            $project = Project::where('tenant_id', $tenantId)->find($projectId, ['id', 'name', 'status', 'start_date', 'end_date']);

            if ($project) {
                $phases = ProjectPhase::where('project_id', $project->id)
                    ->orderBy('order')
                    ->get(['id', 'name', 'status', 'progress_pct', 'start_date', 'end_date', 'duration_days']);
            }
        }

        return Inertia::render('Gantt/Index', [
            'projects' => $projects,
            'selectedProjectId' => $projectId > 0 ? $projectId : null,
            'selectedProject' => $project,
            'phases' => $phases,
        ]);
    }
}
