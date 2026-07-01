<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContractorRequest;
use App\Models\Contractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractorController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('q')->toString();
        $type = $request->string('type')->toString();

        $contractors = Contractor::query()
            ->where('tenant_id', 1)
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
            ],
            'types' => Contractor::$typeLabels,
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
        Contractor::create([
            ...$request->validated(),
            'tenant_id' => 1,
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
