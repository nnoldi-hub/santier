<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhaseTeamAssignmentRequest;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Http\RedirectResponse;

class PhaseTeamAssignmentController extends Controller
{
    public function store(StorePhaseTeamAssignmentRequest $request, Project $project, ProjectPhase $phase): RedirectResponse
    {
        if ((int) $phase->project_id !== (int) $project->id) {
            abort(404);
        }

        $data = $request->validated();

        $existsQuery = $phase->assignments()->where('team_id', $data['team_id']);

        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $existsQuery->where(function ($q) use ($data) {
                $q->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                    ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']]);
            });
        }

        $exists = $existsQuery->exists();

        if ($exists) {
            return back()->withErrors([
                'team_id' => 'Aceasta echipa pare deja alocata pe acelasi interval pentru aceasta etapa.',
            ]);
        }

        $phase->assignments()->create([
            ...$data,
            'workers_assigned' => $data['workers_assigned'] ?? 0,
        ]);

        return back()->with('success', 'Echipa alocata pe etapa!');
    }

    public function destroy(Project $project, ProjectPhase $phase, PhaseTeamAssignment $assignment): RedirectResponse
    {
        if ((int) $phase->project_id !== (int) $project->id || (int) $assignment->phase_id !== (int) $phase->id) {
            abort(404);
        }

        $assignment->delete();

        return back()->with('success', 'Alocare eliminata!');
    }
}
