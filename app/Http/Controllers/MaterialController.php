<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Models\Material;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $search = $request->string('q')->toString();
        $category = $request->string('category')->toString();

        $materials = Material::query()
            ->where('tenant_id', $tenantId)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn ($q) => $q->where('category', $category))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = Material::where('tenant_id', $tenantId)
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return Inertia::render('Materials/Index', [
            'materials' => $materials,
            'filters' => [
                'q' => $search,
                'category' => $category,
            ],
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Materials/Create', [
            'defaults' => [
                'stock_quantity' => null,
                'min_stock_quantity' => null,
            ],
        ]);
    }

    public function store(StoreMaterialRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());

        Material::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return redirect()->route('materials.index')->with('success', 'Material adaugat cu succes!');
    }

    public function edit(Material $material): Response
    {
        return Inertia::render('Materials/Edit', [
            'material' => $material,
        ]);
    }

    public function update(StoreMaterialRequest $request, Material $material): RedirectResponse
    {
        $material->update($request->validated());

        return redirect()->route('materials.index')->with('success', 'Material actualizat!');
    }

    public function destroy(Material $material): RedirectResponse
    {
        $material->delete();

        return redirect()->route('materials.index')->with('success', 'Material sters!');
    }
}
