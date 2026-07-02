<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractorRequest;
use App\Models\Contractor;
use App\Models\ProjectPhase;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractorController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

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

        $contractors = Contractor::query()
            ->where('tenant_id', $tenantId)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Contractors/Index', [
            'contractors' => $contractors,
            'filters' => [
                'q' => $search,
                'type' => $type,
                'calendar_window' => $calendarWindow,
            ],
            'types' => Contractor::$typeLabels,
            'todayCalendar' => ProjectPhase::query()
                ->with(['project:id,name', 'contractor:id,name,type'])
                ->whereHas('project', fn ($query) => $query->where('tenant_id', $tenantId))
                ->whereHas('contractor', fn ($query) => $query->whereIn('type', [Contractor::TYPE_SUBCONTRACTOR, Contractor::TYPE_PFA]))
                ->where(function ($query) use ($windowStartDate, $windowEndDate) {
                    $query
                        ->whereBetween('start_date', [$windowStartDate, $windowEndDate])
                        ->orWhereBetween('end_date', [$windowStartDate, $windowEndDate])
                        ->orWhere(function ($inner) use ($windowStartDate, $windowEndDate) {
                            $inner
                                ->where(function ($startQuery) use ($windowEndDate) {
                                    $startQuery->whereNull('start_date')->orWhereDate('start_date', '<=', $windowEndDate);
                                })
                                ->where(function ($endQuery) use ($windowStartDate) {
                                    $endQuery->whereNull('end_date')->orWhereDate('end_date', '>=', $windowStartDate);
                                });
                        });
                })
                ->orderBy('start_date')
                ->take(6)
                ->get(['id', 'project_id', 'contractor_id', 'name', 'status', 'start_date', 'end_date'])
                ->map(fn (ProjectPhase $phase) => [
                    'id' => $phase->id,
                    'contractor_name' => $phase->contractor?->name,
                    'project_name' => $phase->project?->name,
                    'stage_name' => $phase->name,
                    'status' => $phase->status,
                    'window' => trim((optional($phase->start_date)->format('d.m') ?? '-') . ' - ' . (optional($phase->end_date)->format('d.m') ?? '-')),
                ])
                ->values(),
        ]);
    }

    public function subcontractors(): Response
    {
        return $this->index(request()->merge(['type' => Contractor::TYPE_SUBCONTRACTOR]));
    }

    public function create(): Response
    {
        return Inertia::render('Contractors/Create', [
            'types' => Contractor::$typeLabels,
        ]);
    }

    public function store(StoreContractorRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());

        Contractor::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return redirect()->route('contractors.index')->with('success', 'Contractor adaugat cu succes!');
    }

    public function edit(Contractor $contractor): Response
    {
        return Inertia::render('Contractors/Edit', [
            'contractor' => $contractor,
            'types' => Contractor::$typeLabels,
        ]);
    }

    public function update(StoreContractorRequest $request, Contractor $contractor): RedirectResponse
    {
        $contractor->update($request->validated());

        return redirect()->route('contractors.index')->with('success', 'Contractor actualizat!');
    }

    public function destroy(Contractor $contractor): RedirectResponse
    {
        $contractor->delete();

        return redirect()->route('contractors.index')->with('success', 'Contractor sters!');
    }
}
