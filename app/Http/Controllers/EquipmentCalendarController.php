<?php

namespace App\Http\Controllers;

use App\Models\StageEquipment;
use App\Models\Equipment;
use App\Support\DemoScope;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentCalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = TenantContext::id($user);
        $startDate = $request->string('start_date')->toString() ?: now()->toDateString();
        $endDate = $request->string('end_date')->toString() ?: now()->addDays(30)->toDateString();
        $equipmentId = $request->integer('equipment_id') ?: null;

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
            ->orderByRaw('COALESCE(usage_start, usage_end, created_at) ASC')
            ->get();

        return Inertia::render('EquipmentCalendar/Index', [
            'reservations' => $reservations,
            'equipment' => Equipment::where('tenant_id', $tenantId)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('reservations.phase.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name', 'cost_per_hour', 'availability_status']),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'equipment_id' => $equipmentId,
            ],
            'summary' => [
                'total_reservations' => $reservations->count(),
                'equipment_involved' => $reservations->pluck('equipment_id')->unique()->count(),
                'units_reserved' => (int) $reservations->sum('quantity'),
                'estimated_cost' => (float) $reservations->sum(function ($reservation) {
                    return (float) ($reservation->equipment->cost_per_hour ?? 0) * (int) $reservation->quantity;
                }),
            ],
        ]);
    }
}
