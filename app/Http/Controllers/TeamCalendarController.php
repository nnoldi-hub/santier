<?php

namespace App\Http\Controllers;

use App\Models\PhaseTeamAssignment;
use App\Models\Team;
use App\Support\DemoScope;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = TenantContext::id($user);
        $startDate = $request->string('start_date')->toString() ?: now()->toDateString();
        $endDate = $request->string('end_date')->toString() ?: now()->addDays(30)->toDateString();
        $teamId = $request->integer('team_id') ?: null;

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
                            $overlapQuery->whereNull('start_date')
                                ->whereNull('end_date');
                        })
                        ->orWhere(function ($overlapQuery) use ($startDate, $endDate) {
                            $overlapQuery->where(function ($startQuery) use ($endDate) {
                                $startQuery->whereNull('start_date')->orWhere('start_date', '<=', $endDate);
                            })->where(function ($endQuery) use ($startDate) {
                                $endQuery->whereNull('end_date')->orWhere('end_date', '>=', $startDate);
                            });
                        });
                });
            })
            ->orderByRaw('COALESCE(start_date, end_date, created_at) ASC')
            ->get();

        return Inertia::render('TeamCalendar/Index', [
            'assignments' => $assignments,
            'teams' => Team::where('tenant_id', $tenantId)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('assignments.phase.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name', 'active']),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'team_id' => $teamId,
            ],
            'summary' => [
                'total_assignments' => $assignments->count(),
                'teams_involved' => $assignments->pluck('team_id')->unique()->count(),
                'workers_needed' => (int) $assignments->sum('workers_needed'),
                'workers_assigned' => (int) $assignments->sum('workers_assigned'),
            ],
        ]);
    }
}
