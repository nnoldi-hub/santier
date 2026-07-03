<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\PhaseTeamAssignment;
use App\Models\StageEquipment;
use App\Models\Team;
use App\Support\DemoScope;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = TenantContext::id($user);

        $startDate = $request->string('start_date')->toString() ?: now()->toDateString();
        $endDate = $request->string('end_date')->toString() ?: now()->addDays(30)->toDateString();
        $teamId = $request->integer('team_id') ?: null;
        $equipmentId = $request->integer('equipment_id') ?: null;

        $assignments = PhaseTeamAssignment::query()
            ->with([
                'team:id,name,active',
                'phase:id,project_id,name,start_date,end_date',
                'phase.project:id,name',
            ])
            ->whereHas('team', fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereHas('phase.project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->when($teamId, fn ($query) => $query->where('team_id', $teamId))
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->where(function ($windowQuery) use ($startDate, $endDate) {
                    $windowQuery->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($overlapQuery) use ($startDate, $endDate) {
                            $overlapQuery->where(function ($startQuery) use ($endDate) {
                                $startQuery->whereNull('start_date')->orWhere('start_date', '<=', $endDate);
                            })->where(function ($endQuery) use ($startDate) {
                                $endQuery->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                            });
                        });
                });
            })
            ->get();

        $reservations = StageEquipment::query()
            ->with([
                'equipment:id,name,cost_per_hour,availability_status',
                'phase:id,project_id,name,start_date,end_date',
                'phase.project:id,name',
            ])
            ->whereHas('phase.project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->when($equipmentId, fn ($query) => $query->where('equipment_id', $equipmentId))
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->where(function ($windowQuery) use ($startDate, $endDate) {
                    $windowQuery->whereBetween('usage_start', [$startDate, $endDate])
                        ->orWhereBetween('usage_end', [$startDate, $endDate])
                        ->orWhere(function ($overlapQuery) use ($startDate, $endDate) {
                            $overlapQuery->where(function ($startQuery) use ($endDate) {
                                $startQuery->whereNull('usage_start')->orWhere('usage_start', '<=', $endDate);
                            })->where(function ($endQuery) use ($startDate) {
                                $endQuery->whereNull('usage_end')->orWhere('usage_end', '>=', $startDate);
                            });
                        });
                });
            })
            ->get();

        $events = collect()
            ->merge($assignments->map(function (PhaseTeamAssignment $assignment) {
                return [
                    'id' => 'team-' . $assignment->id,
                    'type' => 'team',
                    'resource' => $assignment->team?->name ?? 'Echipa',
                    'resource_status' => $assignment->team?->active ? 'active' : 'inactive',
                    'project_name' => $assignment->phase?->project?->name,
                    'phase_name' => $assignment->phase?->name,
                    'start_date' => optional($assignment->start_date)->toDateString(),
                    'end_date' => optional($assignment->end_date)->toDateString(),
                    'meta' => [
                        'workers_needed' => (int) $assignment->workers_needed,
                        'workers_assigned' => (int) $assignment->workers_assigned,
                        'notes' => $assignment->notes,
                    ],
                ];
            }))
            ->merge($reservations->map(function (StageEquipment $reservation) {
                return [
                    'id' => 'equipment-' . $reservation->id,
                    'type' => 'equipment',
                    'resource' => $reservation->equipment?->name ?? 'Utilaj',
                    'resource_status' => $reservation->equipment?->availability_status,
                    'project_name' => $reservation->phase?->project?->name,
                    'phase_name' => $reservation->phase?->name,
                    'start_date' => optional($reservation->usage_start)->toDateString(),
                    'end_date' => optional($reservation->usage_end)->toDateString(),
                    'meta' => [
                        'quantity' => (int) $reservation->quantity,
                        'cost_per_hour' => (float) ($reservation->equipment?->cost_per_hour ?? 0),
                        'notes' => $reservation->notes,
                    ],
                ];
            }))
            ->sortBy(function (array $event) {
                return $event['start_date'] ?? $event['end_date'] ?? now()->toDateString();
            })
            ->values();

        return Inertia::render('ResourceCalendar/Index', [
            'events' => $events,
            'teams' => Team::where('tenant_id', $tenantId)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('assignments.phase.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
            'equipment' => Equipment::where('tenant_id', $tenantId)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('reservations.phase.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name', 'availability_status']),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'team_id' => $teamId,
                'equipment_id' => $equipmentId,
            ],
            'summary' => [
                'total_events' => $events->count(),
                'team_events' => $events->where('type', 'team')->count(),
                'equipment_events' => $events->where('type', 'equipment')->count(),
            ],
        ]);
    }
}
