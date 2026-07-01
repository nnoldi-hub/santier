<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentRequest;
use App\Models\Equipment;
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
            ],
            'types' => Equipment::$typeLabels,
            'availabilityStatuses' => Equipment::$availabilityLabels,
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
