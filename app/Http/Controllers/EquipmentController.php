<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Models\Equipment;
use App\Models\StageEquipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Equipment::class, 'equipment');
    }

    public function index(Request $request): Response
    {
        $calendarWindow = $request->string('calendar_window')->toString();
        if (!in_array($calendarWindow, ['today', '7d', '30d'], true)) {
            $calendarWindow = 'today';
        }

        $windowStart = now()->startOfDay();
        $windowEnd = match ($calendarWindow) {
            '7d' => now()->copy()->addDays(6)->endOfDay(),
            '30d' => now()->copy()->addDays(29)->endOfDay(),
            default => now()->copy()->endOfDay(),
        };

        $windowStartDate = $windowStart->toDateString();
        $windowEndDate = $windowEnd->toDateString();

        $search = $request->string('q')->toString();
        $type = $request->string('type')->toString();
        $status = $request->string('availability_status')->toString();

        $equipment = Equipment::query()
            ->where('tenant_id', 1)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('supplier_name', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->when($status !== '', fn ($q) => $q->where('availability_status', $status))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Equipment/Index', [
            'equipment' => $equipment,
            'filters' => [
                'q' => $search,
                'type' => $type,
                'availability_status' => $status,
                'calendar_window' => $calendarWindow,
            ],
            'types' => Equipment::$typeLabels,
            'availabilityStatuses' => Equipment::$availabilityLabels,
            'todayCalendar' => StageEquipment::query()
                ->with(['equipment:id,name', 'phase:id,name,project_id', 'phase.project:id,name'])
                ->where(function ($query) use ($windowStartDate, $windowEndDate) {
                    $query
                        ->whereBetween('usage_start', [$windowStartDate, $windowEndDate])
                        ->orWhereBetween('usage_end', [$windowStartDate, $windowEndDate])
                        ->orWhere(function ($inner) use ($windowStartDate, $windowEndDate) {
                            $inner
                                ->where(function ($startQuery) use ($windowEndDate) {
                                    $startQuery->whereNull('usage_start')->orWhereDate('usage_start', '<=', $windowEndDate);
                                })
                                ->where(function ($endQuery) use ($windowStartDate) {
                                    $endQuery->whereNull('usage_end')->orWhereDate('usage_end', '>=', $windowStartDate);
                                });
                        });
                })
                ->orderBy('usage_start')
                ->take(6)
                ->get(['id', 'equipment_id', 'stage_id', 'quantity', 'usage_start', 'usage_end'])
                ->map(fn (StageEquipment $reservation) => [
                    'id' => $reservation->id,
                    'equipment_name' => $reservation->equipment?->name,
                    'project_name' => $reservation->phase?->project?->name,
                    'stage_name' => $reservation->phase?->name,
                    'quantity' => (int) $reservation->quantity,
                    'window' => trim((optional($reservation->usage_start)->format('d.m') ?? '-') . ' - ' . (optional($reservation->usage_end)->format('d.m') ?? '-')),
                ])
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Equipment/Create', [
            'types' => Equipment::$typeLabels,
            'availabilityStatuses' => Equipment::$availabilityLabels,
        ]);
    }

    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        Equipment::create([
            ...$request->validated(),
            'tenant_id' => 1,
        ]);

        return redirect()->route('equipment.index')->with('success', 'Utilaj adaugat cu succes!');
    }

    public function edit(Equipment $equipment): Response
    {
        return Inertia::render('Equipment/Edit', [
            'equipment' => $equipment,
            'types' => Equipment::$typeLabels,
            'availabilityStatuses' => Equipment::$availabilityLabels,
        ]);
    }

    public function update(StoreEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $equipment->update($request->validated());

        return redirect()->route('equipment.index')->with('success', 'Utilaj actualizat!');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $equipment->delete();

        return redirect()->route('equipment.index')->with('success', 'Utilaj sters!');
    }
}
