<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectPhase;
use App\Http\Requests\StorePhaseRequest;
use Illuminate\Http\RedirectResponse;

class ProjectPhaseController extends Controller
{
    public function store(StorePhaseRequest $request, Project $project): RedirectResponse
    {
        $maxOrder = $project->phases()->max('order') ?? 0;

        $project->phases()->create([
            ...$request->validated(),
            'order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Etapa adaugata!');
    }

    public function update(StorePhaseRequest $request, Project $project, ProjectPhase $phase): RedirectResponse
    {
        $phase->update($request->validated());
        return back()->with('success', 'Etapa actualizata!');
    }

    public function destroy(Project $project, ProjectPhase $phase): RedirectResponse
    {
        $phase->delete();
        return back()->with('success', 'Etapa stearsa!');
    }

    public function updateProgress(Project $project, ProjectPhase $phase): RedirectResponse
    {
        $phase->update([
            'progress_pct' => request()->validate(['progress_pct' => ['required', 'integer', 'min:0', 'max:100']])['progress_pct'],
        ]);
        return back();
    }
}