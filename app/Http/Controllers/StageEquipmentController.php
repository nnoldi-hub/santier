<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStageEquipmentRequest;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\StageEquipment;
use Illuminate\Http\RedirectResponse;

class StageEquipmentController extends Controller
{
    public function store(StoreStageEquipmentRequest $request, Project $project, ProjectPhase $phase): RedirectResponse
    {
        if ((int) $phase->project_id !== (int) $project->id) {
            abort(404);
        }

        $data = $request->validated();

        $hasConflict = false;
        if (!empty($data['usage_start']) && !empty($data['usage_end'])) {
            $hasConflict = StageEquipment::query()
                ->where('equipment_id', $data['equipment_id'])
                ->where(function ($q) use ($data) {
                    $q->whereBetween('usage_start', [$data['usage_start'], $data['usage_end']])
                        ->orWhereBetween('usage_end', [$data['usage_start'], $data['usage_end']])
                        ->orWhere(function ($inner) use ($data) {
                            $inner->where('usage_start', '<=', $data['usage_start'])
                                ->where('usage_end', '>=', $data['usage_end']);
                        });
                })
                ->exists();
        }

        if ($hasConflict && config('equipment.strict_conflict_block', false)) {
            return back()->withErrors([
                'equipment_id' => 'Utilajul este deja rezervat pe intervalul selectat.',
            ])->with('error', 'Rezervarea a fost blocata din cauza conflictului de interval.');
        }

        $phase->equipmentReservations()->create($data);

        $message = 'Utilaj rezervat pe etapa!';
        if ($hasConflict) {
            $message .= ' Atentie: exista o suprapunere de interval pentru acest utilaj.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Project $project, ProjectPhase $phase, StageEquipment $reservation): RedirectResponse
    {
        if ((int) $phase->project_id !== (int) $project->id || (int) $reservation->stage_id !== (int) $phase->id) {
            abort(404);
        }

        $reservation->delete();

        return back()->with('success', 'Rezervarea utilajului a fost eliminata.');
    }
}
