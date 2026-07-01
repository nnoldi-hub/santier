<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Contractor;
use App\Support\DemoScope;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StageProgressController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = [
            'q' => $request->string('q')->toString(),
            'project_id' => $request->integer('project_id') > 0 ? $request->integer('project_id') : null,
            'status' => $request->string('status')->toString(),
            'contractor_id' => $request->integer('contractor_id') > 0 ? $request->integer('contractor_id') : null,
        ];

        $phases = ProjectPhase::query()
            ->with(['project:id,name', 'contractor:id,name', 'parent:id,name'])
            ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('name', 'like', '%' . $filters['q'] . '%')
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', '%' . $filters['q'] . '%'));
                });
            })
            ->when($filters['project_id'], fn ($query, $value) => $query->where('project_id', $value))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['contractor_id'], fn ($query, $value) => $query->where('contractor_id', $value))
            ->orderByDesc('progress_pct')
            ->orderBy('project_id')
            ->orderBy('order')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $allPhases = ProjectPhase::query()
            ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->when($filters['project_id'], fn ($query, $value) => $query->where('project_id', $value))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['contractor_id'], fn ($query, $value) => $query->where('contractor_id', $value));

        $summary = [
            'phases_count' => (clone $allPhases)->count(),
            'average_progress' => (float) round((clone $allPhases)->avg('progress_pct') ?? 0, 1),
            'completed_count' => (clone $allPhases)->where('progress_pct', '>=', 100)->count(),
            'in_progress_count' => (clone $allPhases)->whereBetween('progress_pct', [1, 99])->count(),
            'not_started_count' => (clone $allPhases)->where('progress_pct', 0)->count(),
        ];

        return Inertia::render('StageProgress/Index', [
            'phases' => $phases,
            'filters' => $filters,
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('phases.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'statusLabels' => ProjectPhase::$typeLabels,
            'summary' => $summary,
        ]);
    }
}
