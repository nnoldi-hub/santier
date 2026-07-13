<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\StageEquipment;
use App\Support\EquipmentCostEstimator;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentTraceabilityController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        $filters = [
            'q' => $request->string('q')->toString(),
            'type' => $request->string('type')->toString(),
            'availability_status' => $request->string('availability_status')->toString(),
        ];

        $equipmentRows = Equipment::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('reservations')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('supplier_name', 'like', '%' . $filters['q'] . '%');
                });
            })
            ->when($filters['type'] !== '', fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['availability_status'] !== '', fn ($query) => $query->where('availability_status', $filters['availability_status']))
            ->with(['reservations.phase.project:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Equipment $equipment) => $this->summarizeEquipment($equipment));

        $total = $equipmentRows->count();
        $page = max(1, (int) $request->integer('page', 1));
        $paginatedRows = $equipmentRows->forPage($page, self::PER_PAGE)->values();

        return Inertia::render('EquipmentTraceability/Index', [
            'equipment' => [
                'data' => $paginatedRows,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / self::PER_PAGE)),
                'total' => $total,
            ],
            'filters' => $filters,
            'types' => Equipment::$typeLabels,
            'availabilityStatuses' => Equipment::$availabilityLabels,
            'summary' => [
                'equipment_tracked' => $total,
                'unavailable_count' => $equipmentRows->where('availability_status', '!=', Equipment::STATUS_AVAILABLE)->count(),
                'total_estimated_cost' => round((float) $equipmentRows->sum('total_estimated_cost'), 2),
                'active_today_count' => (int) $equipmentRows->sum('active_reservations_count'),
            ],
        ]);
    }

    private function summarizeEquipment(Equipment $equipment): array
    {
        $today = now()->startOfDay();

        $reservationRows = $equipment->reservations->map(function (StageEquipment $reservation) use ($today) {
            $isActive = (! $reservation->usage_start || $reservation->usage_start->lte($today))
                && (! $reservation->usage_end || $reservation->usage_end->gte($today));

            return [
                'id' => $reservation->id,
                'project_name' => $reservation->phase?->project?->name,
                'phase_name' => $reservation->phase?->name,
                'quantity' => (int) $reservation->quantity,
                'usage_start' => optional($reservation->usage_start)->format('Y-m-d'),
                'usage_end' => optional($reservation->usage_end)->format('Y-m-d'),
                'days' => EquipmentCostEstimator::reservedDays($reservation),
                'estimated_cost' => EquipmentCostEstimator::estimate($reservation),
                'is_active' => $isActive,
            ];
        });

        return [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'type' => $equipment->type,
            'type_label' => Equipment::$typeLabels[$equipment->type] ?? $equipment->type,
            'availability_status' => $equipment->availability_status,
            'availability_label' => Equipment::$availabilityLabels[$equipment->availability_status] ?? $equipment->availability_status,
            'cost_per_hour' => (float) $equipment->cost_per_hour,
            'reservations_count' => $reservationRows->count(),
            'total_reserved_days' => (int) $reservationRows->sum('days'),
            'total_estimated_cost' => round((float) $reservationRows->sum('estimated_cost'), 2),
            'active_reservations_count' => $reservationRows->where('is_active', true)->count(),
            'reservations' => $reservationRows->values(),
        ];
    }
}
