<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Material;
use App\Models\Recipe;
use App\Models\TaskTemplate;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        $recipes = Recipe::where('tenant_id', $tenantId)
            ->with(['subject', 'items', 'laborItems', 'equipmentItems', 'wbsStages'])
            ->orderBy('name')
            ->get()
            ->map(fn (Recipe $recipe) => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'unit' => $recipe->unit,
                'subject_type' => $recipe->subject_type,
                'subject_label' => $recipe->subject_type === 'material' ? 'Material compus' : 'Operatie de lucru',
                'subject_name' => $recipe->subject?->name ?? $recipe->subject?->title ?? '-',
                'items_count' => $recipe->items->count(),
                'labor_items_count' => $recipe->laborItems->count(),
                'equipment_items_count' => $recipe->equipmentItems->count(),
                'wbs_stages_count' => $recipe->wbsStages->count(),
            ]);

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes,
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        return Inertia::render('Recipes/Create', [
            'taskTemplates' => TaskTemplate::where('tenant_id', $tenantId)->orderBy('title')->get(['id', 'title']),
            'materials' => Material::where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'unit']),
            'equipment' => Equipment::where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'cost_per_hour']),
            'presetSubjectType' => $request->string('subject_type')->toString() ?: null,
            'presetSubjectId' => $request->integer('subject_id') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $validated = $this->validateRecipe($request, $tenantId);

        DB::transaction(function () use ($validated, $tenantId) {
            $recipe = Recipe::create([
                'tenant_id' => $tenantId,
                'subject_type' => $validated['subject_type'],
                'subject_id' => $validated['subject_id'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'notes' => $validated['notes'] ?? null,
                'drying_hours' => $validated['drying_hours'] ?? null,
                'curing_hours' => $validated['curing_hours'] ?? null,
                'default_checklist' => $this->normalizeDefaultTasks($validated['default_checklist'] ?? []),
            ]);

            foreach ($validated['items'] as $item) {
                $recipe->items()->create($item);
            }

            foreach ($validated['labor_items'] ?? [] as $item) {
                $recipe->laborItems()->create($item);
            }

            foreach ($validated['equipment_items'] ?? [] as $item) {
                $recipe->equipmentItems()->create($item);
            }

            foreach ($validated['wbs_stages'] ?? [] as $index => $stage) {
                $recipe->wbsStages()->create([
                    'name' => trim($stage['name']),
                    'order' => $index,
                    'default_tasks' => $this->normalizeDefaultTasks($stage['default_tasks'] ?? []),
                ]);
            }
        });

        return redirect()->route('recipes.index')->with('success', 'Reteta a fost creata!');
    }

    public function edit(Request $request, Recipe $recipe): Response
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $recipe->tenant_id === $tenantId, 404);

        $recipe->load(['items', 'laborItems', 'equipmentItems', 'wbsStages']);

        return Inertia::render('Recipes/Edit', [
            'recipe' => [
                'id' => $recipe->id,
                'subject_type' => $recipe->subject_type,
                'subject_id' => $recipe->subject_id,
                'name' => $recipe->name,
                'unit' => $recipe->unit,
                'notes' => $recipe->notes,
                'drying_hours' => $recipe->drying_hours !== null ? (float) $recipe->drying_hours : null,
                'curing_hours' => $recipe->curing_hours !== null ? (float) $recipe->curing_hours : null,
                'items' => $recipe->items->map(fn ($item) => [
                    'material_id' => $item->material_id,
                    'quantity_per_unit' => (float) $item->quantity_per_unit,
                ]),
                'labor_items' => $recipe->laborItems->map(fn ($item) => [
                    'role' => $item->role,
                    'hours_per_unit' => (float) $item->hours_per_unit,
                    'hourly_rate' => (float) $item->hourly_rate,
                ]),
                'equipment_items' => $recipe->equipmentItems->map(fn ($item) => [
                    'equipment_id' => $item->equipment_id,
                    'hours_per_unit' => (float) $item->hours_per_unit,
                ]),
                'wbs_stages' => $recipe->wbsStages->map(fn ($stage) => [
                    'name' => $stage->name,
                    'default_tasks' => $stage->default_tasks ?? [],
                ]),
                'default_checklist' => $recipe->default_checklist ?? [],
            ],
            'taskTemplates' => TaskTemplate::where('tenant_id', $tenantId)->orderBy('title')->get(['id', 'title']),
            'materials' => Material::where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'unit']),
            'equipment' => Equipment::where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'cost_per_hour']),
        ]);
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $recipe->tenant_id === $tenantId, 404);

        $validated = $this->validateRecipe($request, $tenantId);

        DB::transaction(function () use ($recipe, $validated) {
            $recipe->update([
                'subject_type' => $validated['subject_type'],
                'subject_id' => $validated['subject_id'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'notes' => $validated['notes'] ?? null,
                'drying_hours' => $validated['drying_hours'] ?? null,
                'curing_hours' => $validated['curing_hours'] ?? null,
                'default_checklist' => $this->normalizeDefaultTasks($validated['default_checklist'] ?? []),
            ]);

            $recipe->items()->delete();
            $recipe->laborItems()->delete();
            $recipe->equipmentItems()->delete();
            $recipe->wbsStages()->delete();

            foreach ($validated['items'] as $item) {
                $recipe->items()->create($item);
            }

            foreach ($validated['labor_items'] ?? [] as $item) {
                $recipe->laborItems()->create($item);
            }

            foreach ($validated['equipment_items'] ?? [] as $item) {
                $recipe->equipmentItems()->create($item);
            }

            foreach ($validated['wbs_stages'] ?? [] as $index => $stage) {
                $recipe->wbsStages()->create([
                    'name' => trim($stage['name']),
                    'order' => $index,
                    'default_tasks' => $this->normalizeDefaultTasks($stage['default_tasks'] ?? []),
                ]);
            }
        });

        return redirect()->route('recipes.index')->with('success', 'Reteta a fost actualizata!');
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $recipe->tenant_id === $tenantId, 404);

        $recipe->delete();

        return redirect()->route('recipes.index')->with('success', 'Reteta a fost stearsa!');
    }

    public function quickCreate(Request $request)
    {
        $tenantId = TenantContext::id($request->user());

        // Not under /api/* - bootstrap/app.php only renders JSON error responses
        // for that prefix, so validate manually to guarantee a JSON response
        // instead of the redirect $request->validate() would otherwise trigger.
        $validator = Validator::make($request->all(), [
            'subject_type' => ['required', 'in:task_template,material'],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists($request->input('subject_type') === 'material' ? 'materials' : 'task_templates', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required', 'integer', Rule::exists('materials', 'id')->where('tenant_id', $tenantId)],
            'items.*.quantity_per_unit' => ['required', 'numeric', 'min:0.0001', 'max:999999999'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Date invalide.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $recipe = DB::transaction(function () use ($validated, $tenantId) {
            $recipe = Recipe::create([
                'tenant_id' => $tenantId,
                'subject_type' => $validated['subject_type'],
                'subject_id' => $validated['subject_id'],
                'name' => $validated['name'],
                'unit' => $validated['unit'],
            ]);

            foreach ($validated['items'] as $item) {
                $recipe->items()->create($item);
            }

            return $recipe;
        });

        $recipe->load('items.material:id,name,unit');

        return response()->json([
            'id' => $recipe->id,
            'unit' => $recipe->unit,
            'items' => $recipe->items->map(fn ($item) => [
                'material_id' => $item->material_id,
                'material_name' => $item->material?->name,
                'quantity_per_unit' => (float) $item->quantity_per_unit,
                'unit' => $item->material?->unit,
            ]),
        ]);
    }

    private function validateRecipe(Request $request, int $tenantId): array
    {
        return $request->validate([
            'subject_type' => ['required', 'in:task_template,material'],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists($request->input('subject_type') === 'material' ? 'materials' : 'task_templates', 'id')
                    ->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'drying_hours' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'curing_hours' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required', 'integer', Rule::exists('materials', 'id')->where('tenant_id', $tenantId)],
            'items.*.quantity_per_unit' => ['required', 'numeric', 'min:0.0001', 'max:999999999'],
            'labor_items' => ['nullable', 'array'],
            'labor_items.*.role' => ['required', 'string', 'max:100'],
            'labor_items.*.hours_per_unit' => ['required', 'numeric', 'min:0.0001', 'max:999999999'],
            'labor_items.*.hourly_rate' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'equipment_items' => ['nullable', 'array'],
            'equipment_items.*.equipment_id' => ['required', 'integer', Rule::exists('equipment', 'id')->where('tenant_id', $tenantId)],
            'equipment_items.*.hours_per_unit' => ['required', 'numeric', 'min:0.0001', 'max:999999999'],
            'wbs_stages' => ['nullable', 'array', 'max:10'],
            'wbs_stages.*.name' => ['required_with:wbs_stages', 'string', 'max:255'],
            'wbs_stages.*.default_tasks' => ['nullable', 'array', 'max:10'],
            'wbs_stages.*.default_tasks.*' => ['nullable', 'string', 'max:255'],
            'default_checklist' => ['nullable', 'array', 'max:20'],
            'default_checklist.*' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function normalizeDefaultTasks(array $defaultTasks): array
    {
        return collect($defaultTasks)
            ->map(fn ($task) => trim((string) $task))
            ->filter()
            ->values()
            ->all();
    }
}
