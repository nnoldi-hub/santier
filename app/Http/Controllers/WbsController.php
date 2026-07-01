<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Support\DemoScope;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WbsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $search = $request->string('q')->toString();
        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id');
        $contractorId = $request->integer('contractor_id');

        $phases = ProjectPhase::query()
            ->with([
                'project:id,name,tenant_id',
                'contractor:id,name,type',
                'parent:id,name',
            ])
            ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($projectQ) => $projectQ->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('contractor', fn ($contractorQ) => $contractorQ->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->when($contractorId > 0, fn ($q) => $q->where('contractor_id', $contractorId))
            ->orderBy('project_id')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 1 WHEN 'pending' THEN 2 WHEN 'blocked' THEN 3 WHEN 'completed' THEN 4 ELSE 5 END")
            ->orderBy('start_date')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $phaseOptionsByProject = ProjectPhase::query()
            ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
            ->orderBy('name')
            ->get(['id', 'project_id', 'name'])
            ->groupBy('project_id')
            ->map(fn ($group) => $group->map(fn ($phase) => [
                'id' => $phase->id,
                'name' => $phase->name,
            ])->values());

        return Inertia::render('Wbs/Index', [
            'phases' => $phases,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'project_id' => $projectId > 0 ? (string) $projectId : '',
                'contractor_id' => $contractorId > 0 ? (string) $contractorId : '',
            ],
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)
                ->orderBy('name')
                ->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)
                ->where('active', true)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('phases.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'typeLabels' => ProjectPhase::$typeLabels,
            'phaseOptionsByProject' => $phaseOptionsByProject,
        ]);
    }

    public function updatePhase(Request $request, ProjectPhase $phase): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'blocked'])],
            'progress_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'contractor_id' => ['nullable', 'integer', 'exists:contractors,id'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('project_phases', 'id')->where(fn ($q) => $q->where('project_id', $phase->project_id)),
                Rule::notIn([$phase->id]),
            ],
        ]);

        if ($this->createsHierarchyCycle($phase, $validated['parent_id'] ?? null)) {
            return back()->with('error', 'Relatia parinte selectata creeaza un ciclu WBS si nu este permisa.');
        }

        $phase->update($validated);

        return back()->with('success', 'Etapa actualizata din WBS.');
    }

    private function createsHierarchyCycle(ProjectPhase $phase, ?int $newParentId): bool
    {
        if (!$newParentId) {
            return false;
        }

        $currentParentId = $newParentId;

        while ($currentParentId) {
            if ($currentParentId === $phase->id) {
                return true;
            }

            $currentParentId = ProjectPhase::query()
                ->where('id', $currentParentId)
                ->value('parent_id');
        }

        return false;
    }
}
