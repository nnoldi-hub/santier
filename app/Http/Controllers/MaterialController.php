<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialRequest;
use App\Models\Material;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Material::class, 'material');
    }

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

    public function quickCreate(Request $request)
    {
        $this->authorize('create', Material::class);

        // Not under /api/* - bootstrap/app.php only renders JSON error responses
        // for that prefix, so validate manually to guarantee a JSON response
        // instead of the redirect $request->validate() would otherwise trigger.
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Date invalide.', 'errors' => $validator->errors()], 422);
        }

        $material = Material::create([
            ...$validator->validated(),
            'tenant_id' => TenantContext::id($request->user()),
            'active' => true,
        ]);

        return response()->json(['id' => $material->id, 'name' => $material->name, 'unit' => $material->unit]);
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
