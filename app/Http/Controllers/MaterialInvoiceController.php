<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaterialInvoiceRequest;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\Project;
use App\Support\DemoScope;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialInvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MaterialInvoice::class, 'material_invoice');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = TenantContext::id($user);
        $filters = [
            'q' => $request->string('q')->toString(),
            'payment_status' => $request->string('payment_status')->toString(),
            'project_id' => $request->integer('project_id') > 0 ? $request->integer('project_id') : null,
        ];

        $invoices = MaterialInvoice::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->with(['project:id,name', 'phase:id,name', 'material:id,name,unit'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('invoice_no', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('supplier_name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('notes', 'like', '%' . $filters['q'] . '%');
                });
            })
            ->when($filters['payment_status'] !== '', fn ($query) => $query->where('payment_status', $filters['payment_status']))
            ->when($filters['project_id'], fn ($query, $value) => $query->where('project_id', $value))
            ->orderByRaw("CASE WHEN payment_status = 'unpaid' THEN 1 WHEN payment_status = 'partial' THEN 2 WHEN payment_status = 'paid' THEN 3 ELSE 4 END")
            ->orderByDesc('issue_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $summary = MaterialInvoice::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->when($filters['project_id'], fn ($query, $value) => $query->where('project_id', $value))
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw("SUM(CASE WHEN payment_status IN ('unpaid','partial') THEN amount_total ELSE 0 END) as unpaid_exposure")
            ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN amount_total ELSE 0 END) as paid_total")
            ->first();

        return Inertia::render('MaterialInvoices/Index', [
            'invoices' => $invoices,
            'filters' => $filters,
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
            'paymentStatuses' => MaterialInvoice::$paymentStatusLabels,
            'summary' => [
                'total_count' => (int) ($summary->total_count ?? 0),
                'unpaid_exposure' => (float) ($summary->unpaid_exposure ?? 0),
                'paid_total' => (float) ($summary->paid_total ?? 0),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $tenantId = TenantContext::id($user);

        $projects = DemoScope::applyProjectScope(Project::query(), $user)
            ->with(['phases:id,project_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        $projectId = $request->integer('project_id');

        return Inertia::render('MaterialInvoices/Create', [
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'selectedProjectId' => $projectId > 0 ? $projectId : null,
            'phasesByProject' => $projects->mapWithKeys(fn ($project) => [
                $project->id => $project->phases->map(fn ($phase) => [
                    'id' => $phase->id,
                    'name' => $phase->name,
                ])->values(),
            ]),
            'materials' => Material::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'unit']),
            'paymentStatuses' => MaterialInvoice::$paymentStatusLabels,
        ]);
    }

    public function store(StoreMaterialInvoiceRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());

        MaterialInvoice::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return redirect()->route('material-invoices.index')->with('success', 'Factura materiale a fost creata.');
    }

    public function edit(MaterialInvoice $material_invoice): Response
    {
        $user = request()->user();
        $tenantId = TenantContext::id($user);

        $projects = DemoScope::applyProjectScope(Project::query(), $user)
            ->with(['phases:id,project_id,name'])
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MaterialInvoices/Edit', [
            'invoice' => $material_invoice,
            'projects' => $projects->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
            ]),
            'phasesByProject' => $projects->mapWithKeys(fn ($project) => [
                $project->id => $project->phases->map(fn ($phase) => [
                    'id' => $phase->id,
                    'name' => $phase->name,
                ])->values(),
            ]),
            'materials' => Material::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'unit']),
            'paymentStatuses' => MaterialInvoice::$paymentStatusLabels,
        ]);
    }

    public function update(StoreMaterialInvoiceRequest $request, MaterialInvoice $material_invoice): RedirectResponse
    {
        $material_invoice->update($request->validated());

        return redirect()->route('material-invoices.index')->with('success', 'Factura materiale a fost actualizata.');
    }

    public function destroy(MaterialInvoice $material_invoice): RedirectResponse
    {
        $material_invoice->delete();

        return redirect()->route('material-invoices.index')->with('success', 'Factura materiale a fost stearsa.');
    }
}
