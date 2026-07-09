<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceOrderRequest;
use App\Models\AccessAuditLog;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\ResourceConfirmation;
use App\Models\ResourceDocumentLink;
use App\Models\ResourceOrder;
use App\Models\Task;
use App\Models\User;
use App\Notifications\OperationalReminderNotification;
use App\Support\AccessAudit;
use App\Support\DemoScope;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ResourceOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = TenantContext::id($user);
        $filters = [
            'q' => $request->string('q')->toString(),
            'resource_type' => $request->string('resource_type')->toString(),
            'status' => $request->string('status')->toString(),
            'project_id' => $request->integer('project_id') > 0 ? $request->integer('project_id') : null,
        ];

        $orders = ResourceOrder::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->with(['project:id,name', 'phase:id,name', 'material:id,name,unit', 'equipment:id,name', 'responsibleUser:id,name'])
            ->withCount('documentLinks')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner
                        ->where('supplier_name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('carrier_name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('equipment_name', 'like', '%' . $filters['q'] . '%')
                        ->orWhereHas('material', fn ($materialQuery) => $materialQuery->where('name', 'like', '%' . $filters['q'] . '%'))
                        ->orWhereHas('equipment', fn ($equipmentQuery) => $equipmentQuery->where('name', 'like', '%' . $filters['q'] . '%'));
                });
            })
            ->when($filters['resource_type'] !== '', fn ($query) => $query->where('resource_type', $filters['resource_type']))
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['project_id'], fn ($query, $value) => $query->where('project_id', $value))
            ->latest('id')
            ->paginate(20)
            ->through(fn (ResourceOrder $order) => [
                'id' => $order->id,
                'resource_type' => $order->resource_type,
                'resource_type_label' => ResourceOrder::$resourceTypeLabels[$order->resource_type] ?? $order->resource_type,
                'status' => $order->status,
                'status_label' => ResourceOrder::$statusLabels[$order->status] ?? $order->status,
                'project' => $order->project,
                'phase' => $order->phase,
                'material' => $order->material,
                'equipment' => $order->equipment,
                'responsible_user' => $order->responsibleUser,
                'supplier_name' => $order->supplier_name,
                'carrier_name' => $order->carrier_name,
                'equipment_name' => $order->equipment_name,
                'ordered_quantity' => (float) $order->ordered_quantity,
                'ordered_unit' => $order->ordered_unit,
                'unit_price' => (float) $order->unit_price,
                'delivery_date' => optional($order->delivery_date)?->format('Y-m-d'),
                'notes' => $order->notes,
                'document_links_count' => $order->document_links_count,
                'show_url' => route('resource-orders.show', $order),
            ])
            ->withQueryString();

        return Inertia::render('ResourceOrders/Index', [
            'orders' => $orders,
            'filters' => $filters,
            'resourceTypes' => ResourceOrder::$resourceTypeLabels,
            'statuses' => ResourceOrder::$statusLabels,
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $tenantId = TenantContext::id($user);

        return Inertia::render('ResourceOrders/Create', [
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
            'phasesByProject' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
                ->orderBy('name')
                ->get(['id', 'project_id', 'name'])
                ->groupBy('project_id')
                ->map(fn ($rows) => $rows->values())
                ->all(),
            'materials' => Material::query()->where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'unit', 'supplier']),
            'equipment' => Equipment::query()->where('tenant_id', $tenantId)->where('active', true)->orderBy('name')->get(['id', 'name', 'supplier_name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'resourceTypes' => ResourceOrder::$resourceTypeLabels,
            'statuses' => ResourceOrder::$statusLabels,
            'resourceDocumentTypes' => ResourceOrder::$documentTypeLabels,
        ]);
    }

    public function show(Request $request, ResourceOrder $resource_order): Response
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $resource_order->tenant_id === $tenantId, 404);

        $resource_order->load([
            'project:id,name',
            'phase:id,name',
            'material:id,name,unit',
            'equipment:id,name',
            'responsibleUser:id,name',
            'deliveries',
            'documentLinks.document',
            'confirmations.confirmer:id,name',
        ]);

        $confirmationByRole = $resource_order->confirmations->keyBy('confirmation_role');

        $confirmations = collect(ResourceConfirmation::$roleLabels)
            ->map(function (string $label, string $role) use ($confirmationByRole) {
                $row = $confirmationByRole->get($role);

                return [
                    'role' => $role,
                    'role_label' => $label,
                    'status' => $row?->status ?? 'pending',
                    'status_label' => ResourceConfirmation::$statusLabels[$row?->status ?? 'pending'] ?? 'In asteptare',
                    'confirmed_at' => optional($row?->confirmed_at)->format('Y-m-d H:i'),
                    'confirmed_by' => $row?->confirmer?->name,
                    'notes' => $row?->notes,
                ];
            })
            ->values();

        $linkedDocuments = $resource_order->documentLinks
            ->map(function (ResourceDocumentLink $link) {
                $document = $link->document;

                return [
                    'id' => $link->id,
                    'role' => $link->document_role,
                    'role_label' => ResourceOrder::$documentTypeLabels[$link->document_role] ?? $link->document_role,
                    'title' => $document?->title,
                    'document_number' => $link->document_number,
                    'declared_quantity' => (float) $link->declared_quantity,
                    'delivered_quantity' => (float) $link->delivered_quantity,
                    'difference_quantity' => (float) $link->difference_quantity,
                    'created_at' => optional($link->created_at)->format('Y-m-d H:i'),
                    'download_url' => $document ? route('documents.download', $document) : null,
                    'pdf_url' => $document ? route('documents.pdf', $document) : null,
                    'notes' => $link->notes,
                ];
            })
            ->values();

        $deliveries = $resource_order->deliveries
            ->map(fn ($delivery) => [
                'declared_quantity' => (float) ($delivery->declared_quantity ?? 0),
                'received_quantity' => (float) ($delivery->received_quantity ?? 0),
                'equipment_reported_quantity' => (float) ($delivery->equipment_reported_quantity ?? 0),
                'consumed_quantity' => (float) ($delivery->consumed_quantity ?? 0),
                'returned_quantity' => (float) ($delivery->returned_quantity ?? 0),
            ])
            ->values();

        $reconciliation = $this->buildReconciliationSummary($resource_order, $linkedDocuments, $deliveries);

        $timeline = collect([
            [
                'timestamp' => optional($resource_order->created_at)->toDateTimeString(),
                'label' => 'Comanda inregistrata',
                'details' => sprintf('Status initial: %s', ResourceOrder::$statusLabels[$resource_order->status] ?? $resource_order->status),
            ],
            ...$linkedDocuments->map(fn (array $item) => [
                'timestamp' => $item['created_at'],
                'label' => 'Document atasat',
                'details' => trim(sprintf('%s%s', $item['role_label'], $item['document_number'] ? ' #' . $item['document_number'] : '')),
            ])->all(),
            ...$confirmations->filter(fn (array $item) => $item['status'] !== 'pending')->map(fn (array $item) => [
                'timestamp' => $item['confirmed_at'],
                'label' => 'Confirmare ' . mb_strtolower($item['role_label']),
                'details' => sprintf('%s%s', $item['status_label'], $item['confirmed_by'] ? ' - ' . $item['confirmed_by'] : ''),
            ])->all(),
        ])
            ->filter(fn (array $item) => ! empty($item['timestamp']))
            ->sortBy('timestamp')
            ->values();

        $discrepancySummary = [
            'ordered_quantity' => (float) $resource_order->ordered_quantity,
            'max_document_difference' => (float) $linkedDocuments->max('difference_quantity'),
            'has_positive_difference' => (bool) $linkedDocuments->contains(fn (array $item) => (float) $item['difference_quantity'] > 0.01),
            'blocked_payment' => $resource_order->status === 'blocked_payment',
            'quantity_tolerance' => (float) config('resources.quantity_tolerance', 0.20),
        ];

        $auditTrail = AccessAuditLog::query()
            ->where('tenant_id', $tenantId)
            ->where('resource_type', 'resource_order')
            ->where('resource_id', $resource_order->id)
            ->with('actor:id,name,email')
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (AccessAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'action_label' => $this->auditActionLabel($log->action),
                'created_at' => optional($log->created_at)->format('Y-m-d H:i:s'),
                'actor_name' => $log->actor?->name,
                'actor_email' => $log->actor?->email,
                'metadata' => $log->metadata ?? [],
            ])
            ->values();

        return Inertia::render('ResourceOrders/Show', [
            'order' => [
                'id' => $resource_order->id,
                'resource_type' => $resource_order->resource_type,
                'resource_type_label' => ResourceOrder::$resourceTypeLabels[$resource_order->resource_type] ?? $resource_order->resource_type,
                'status' => $resource_order->status,
                'status_label' => ResourceOrder::$statusLabels[$resource_order->status] ?? $resource_order->status,
                'project' => $resource_order->project,
                'phase' => $resource_order->phase,
                'material' => $resource_order->material,
                'equipment' => $resource_order->equipment,
                'responsible_user' => $resource_order->responsibleUser,
                'supplier_name' => $resource_order->supplier_name,
                'carrier_name' => $resource_order->carrier_name,
                'equipment_name' => $resource_order->equipment_name,
                'ordered_quantity' => (float) $resource_order->ordered_quantity,
                'ordered_unit' => $resource_order->ordered_unit,
                'unit_price' => (float) $resource_order->unit_price,
                'delivery_date' => optional($resource_order->delivery_date)?->format('Y-m-d'),
                'notes' => $resource_order->notes,
            ],
            'confirmations' => $confirmations,
            'linkedDocuments' => $linkedDocuments,
            'deliveries' => $deliveries,
            'timeline' => $timeline,
            'discrepancySummary' => $discrepancySummary,
            'reconciliation' => $reconciliation,
            'resourceDocumentTypes' => ResourceOrder::$documentTypeLabels,
            'auditTrail' => $auditTrail,
        ]);
    }

    public function store(StoreResourceOrderRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        $payload = $request->validated();
        $documents = $payload['documents'] ?? [];
        $actorId = (int) $request->user()->id;

        unset($payload['documents']);

        if (($payload['resource_type'] ?? null) === 'material') {
            $payload['equipment_id'] = null;
        }

        if (($payload['resource_type'] ?? null) === 'equipment') {
            $payload['material_id'] = null;
        }

        DB::transaction(function () use ($payload, $tenantId, $documents, $request, $actorId): void {
            $order = ResourceOrder::create([
                ...$payload,
                'tenant_id' => $tenantId,
            ]);

            $this->persistLinkedDocuments($order, $documents, $request, $tenantId);
            $this->reconcileOrderState($order, $actorId);

            $this->logResourceAudit('resource_order.created', $request, $order, [
                'status' => $order->status,
                'resource_type' => $order->resource_type,
                'ordered_quantity' => (float) $order->ordered_quantity,
                'ordered_unit' => $order->ordered_unit,
            ]);
        });

        return redirect()->route('resource-orders.index')->with('success', 'Documentul de resursa a fost inregistrat.');
    }

    public function updateConfirmation(Request $request, ResourceOrder $resource_order): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $resource_order->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'confirmation_role' => ['required', 'in:' . implode(',', array_keys(ResourceConfirmation::$roleLabels))],
            'status' => ['required', 'in:confirmed,rejected'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $previous = ResourceConfirmation::query()
            ->where('resource_order_id', $resource_order->id)
            ->where('confirmation_role', $validated['confirmation_role'])
            ->first();

        ResourceConfirmation::query()->updateOrCreate(
            [
                'resource_order_id' => $resource_order->id,
                'confirmation_role' => $validated['confirmation_role'],
            ],
            [
                'tenant_id' => $tenantId,
                'status' => $validated['status'],
                'confirmed_by' => $request->user()->id,
                'confirmed_at' => now(),
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            ]
        );

        $resource_order->load([
            'documentLinks',
            'deliveries',
            'confirmations',
        ]);

        $reconciliation = $this->buildReconciliationSummary(
            $resource_order,
            $resource_order->documentLinks->map(fn (ResourceDocumentLink $link) => [
                'role' => $link->document_role,
                'declared_quantity' => (float) $link->declared_quantity,
                'delivered_quantity' => (float) $link->delivered_quantity,
                'difference_quantity' => (float) $link->difference_quantity,
            ]),
            $resource_order->deliveries->map(fn ($delivery) => [
                'declared_quantity' => (float) ($delivery->declared_quantity ?? 0),
                'received_quantity' => (float) ($delivery->received_quantity ?? 0),
                'equipment_reported_quantity' => (float) ($delivery->equipment_reported_quantity ?? 0),
                'consumed_quantity' => (float) ($delivery->consumed_quantity ?? 0),
                'returned_quantity' => (float) ($delivery->returned_quantity ?? 0),
            ])
        );

        $this->applyLifecycleStatus($resource_order, $reconciliation, $resource_order->confirmations);

        $this->logResourceAudit('resource_order.confirmation_updated', $request, $resource_order, [
            'confirmation_role' => $validated['confirmation_role'],
            'status_before' => $previous?->status ?? 'pending',
            'status_after' => $validated['status'],
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
        ]);

        return redirect()->route('resource-orders.show', $resource_order)->with('success', 'Confirmarea a fost actualizata.');
    }

    public function destroy(Request $request, ResourceOrder $resource_order): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $resource_order->tenant_id === $tenantId, 404);

        $this->logResourceAudit('resource_order.deleted', $request, $resource_order, [
            'status' => $resource_order->status,
            'ordered_quantity' => (float) $resource_order->ordered_quantity,
            'ordered_unit' => $resource_order->ordered_unit,
            'document_links_count' => (int) $resource_order->documentLinks()->count(),
        ]);

        $resource_order->delete();

        return redirect()->route('resource-orders.index')->with('success', 'Inregistrarea a fost stearsa.');
    }

    public function storeDocument(Request $request, ResourceOrder $resource_order): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $resource_order->tenant_id === $tenantId, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:' . implode(',', array_keys(ResourceOrder::$documentTypeLabels))],
            'document_number' => ['nullable', 'string', 'max:120'],
            'attachment' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
            'declared_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'delivered_quantity' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($resource_order, $validated, $tenantId, $request): void {
            $file = $request->file('attachment');

            if ($file === null) {
                return;
            }

            $declaredQuantity = (float) ($validated['declared_quantity'] ?? 0);
            $deliveredQuantity = (float) ($validated['delivered_quantity'] ?? 0);

            $document = Document::create([
                'tenant_id' => $tenantId,
                'project_id' => $resource_order->project_id,
                'stage_id' => $resource_order->phase_id,
                'contractor_id' => null,
                'type' => $validated['type'],
                'amount' => $validated['type'] === 'resource_invoice'
                    ? (float) (($resource_order->unit_price ?? 0) * ($resource_order->ordered_quantity ?? 0))
                    : 0,
                'issued_at' => $resource_order->delivery_date ?? now()->toDateString(),
                'payment_status' => $validated['type'] === 'resource_invoice' ? 'unpaid' : 'paid',
                'title' => $validated['title'],
                'invoice_number' => $validated['document_number'] ?? null,
                'file_path' => $file->store('documents', 'local'),
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $link = ResourceDocumentLink::create([
                'tenant_id' => $tenantId,
                'resource_order_id' => $resource_order->id,
                'document_id' => $document->id,
                'document_role' => $validated['type'],
                'document_number' => $validated['document_number'] ?? null,
                'supplier_name' => $resource_order->supplier_name,
                'carrier_name' => $resource_order->carrier_name,
                'equipment_name' => $resource_order->equipment_name,
                'declared_quantity' => $declaredQuantity,
                'delivered_quantity' => $deliveredQuantity,
                'difference_quantity' => $declaredQuantity - $deliveredQuantity,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->reconcileOrderState($resource_order, (int) ($request->user()?->id ?? 0));

            $this->logResourceAudit('resource_order.document_attached', $request, $resource_order, [
                'resource_document_link_id' => (int) $link->id,
                'document_id' => (int) $document->id,
                'document_type' => $validated['type'],
                'document_number' => $validated['document_number'] ?? null,
                'declared_quantity' => $declaredQuantity,
                'delivered_quantity' => $deliveredQuantity,
                'difference_quantity' => $declaredQuantity - $deliveredQuantity,
            ]);
        });

        return redirect()->route('resource-orders.show', $resource_order)->with('success', 'Documentul a fost atasat.');
    }

    public function destroyDocument(Request $request, ResourceOrder $resource_order, ResourceDocumentLink $resource_document_link): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());
        abort_unless((int) $resource_order->tenant_id === $tenantId, 404);
        abort_unless((int) $resource_document_link->tenant_id === $tenantId, 404);
        abort_unless((int) $resource_document_link->resource_order_id === (int) $resource_order->id, 404);

        DB::transaction(function () use ($resource_order, $resource_document_link, $request): void {
            $document = $resource_document_link->document;
            $snapshot = [
                'resource_document_link_id' => (int) $resource_document_link->id,
                'document_id' => (int) ($resource_document_link->document_id ?? 0),
                'document_type' => (string) ($resource_document_link->document_role ?? ''),
                'document_number' => $resource_document_link->document_number,
                'declared_quantity' => (float) ($resource_document_link->declared_quantity ?? 0),
                'delivered_quantity' => (float) ($resource_document_link->delivered_quantity ?? 0),
                'difference_quantity' => (float) ($resource_document_link->difference_quantity ?? 0),
            ];

            $resource_document_link->delete();

            if ($document instanceof Document) {
                $hasOtherLinks = ResourceDocumentLink::query()
                    ->where('document_id', $document->id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (! $hasOtherLinks) {
                    if ($document->file_path) {
                        Storage::disk('local')->delete($document->file_path);
                    }

                    $document->delete();
                }
            }

            $this->reconcileOrderState($resource_order, (int) ($request->user()?->id ?? 0));

            $this->logResourceAudit('resource_order.document_deleted', $request, $resource_order, $snapshot);
        });

        return redirect()->route('resource-orders.show', $resource_order)->with('success', 'Documentul a fost sters.');
    }

    private function reconcileOrderState(ResourceOrder $order, int $actorId): void
    {
        $order->load([
            'documentLinks',
            'deliveries',
            'confirmations',
            'responsibleUser',
            'project',
        ]);

        $reconciliation = $this->buildReconciliationSummary(
            $order,
            $order->documentLinks->map(fn (ResourceDocumentLink $link) => [
                'role' => $link->document_role,
                'declared_quantity' => (float) $link->declared_quantity,
                'delivered_quantity' => (float) $link->delivered_quantity,
                'difference_quantity' => (float) $link->difference_quantity,
            ]),
            $order->deliveries->map(fn ($delivery) => [
                'declared_quantity' => (float) ($delivery->declared_quantity ?? 0),
                'received_quantity' => (float) ($delivery->received_quantity ?? 0),
                'equipment_reported_quantity' => (float) ($delivery->equipment_reported_quantity ?? 0),
                'consumed_quantity' => (float) ($delivery->consumed_quantity ?? 0),
                'returned_quantity' => (float) ($delivery->returned_quantity ?? 0),
            ])
        );

        $this->applyLifecycleStatus($order, $reconciliation, $order->confirmations);
        $this->createDiscrepancyFollowUp($order, (int) $order->tenant_id, $actorId, $reconciliation);
    }

    private function persistLinkedDocuments(ResourceOrder $order, array $documents, Request $request, int $tenantId): void
    {
        foreach ($documents as $index => $documentInput) {
            $file = $request->file("documents.$index.attachment");

            if ($file === null) {
                continue;
            }

            $declaredQuantity = (float) ($documentInput['declared_quantity'] ?? 0);
            $deliveredQuantity = (float) ($documentInput['delivered_quantity'] ?? 0);

            $document = Document::create([
                'tenant_id' => $tenantId,
                'project_id' => $order->project_id,
                'stage_id' => $order->phase_id,
                'contractor_id' => null,
                'type' => $documentInput['type'],
                'amount' => $documentInput['type'] === 'resource_invoice'
                    ? (float) (($order->unit_price ?? 0) * ($order->ordered_quantity ?? 0))
                    : 0,
                'issued_at' => $order->delivery_date ?? now()->toDateString(),
                'payment_status' => $documentInput['type'] === 'resource_invoice' ? 'unpaid' : 'paid',
                'title' => $documentInput['title'],
                'invoice_number' => $documentInput['document_number'] ?? null,
                'file_path' => $file->store('documents', 'local'),
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'notes' => $documentInput['notes'] ?? null,
            ]);

            ResourceDocumentLink::create([
                'tenant_id' => $tenantId,
                'resource_order_id' => $order->id,
                'document_id' => $document->id,
                'document_role' => $documentInput['type'],
                'document_number' => $documentInput['document_number'] ?? null,
                'supplier_name' => $order->supplier_name,
                'carrier_name' => $order->carrier_name,
                'equipment_name' => $order->equipment_name,
                'declared_quantity' => $declaredQuantity,
                'delivered_quantity' => $deliveredQuantity,
                'difference_quantity' => $declaredQuantity - $deliveredQuantity,
                'notes' => $documentInput['notes'] ?? null,
            ]);
        }
    }

    private function createDiscrepancyFollowUp(ResourceOrder $order, int $tenantId, int $actorId, array $reconciliation): void
    {
        $maxDifference = (float) ($reconciliation['max_delta'] ?? 0);

        if (($reconciliation['is_blocked'] ?? false) !== true || $maxDifference <= 0.01) {
            $this->cancelDiscrepancyFollowUp($order);
            return;
        }

        $existingTask = $this->findActiveDiscrepancyTask($order);
        $taskMarker = sprintf('[resource_order:%d]', (int) $order->id);

        if ($existingTask instanceof Task) {
            $existingTask->update([
                'title' => sprintf('Verifica diferenta de cantitate (%.2f %s)', $maxDifference, (string) $order->ordered_unit),
                'description' => "Sistemul a detectat o diferenta pozitiva intre cantitatea declarata si cantitatea livrata. Verifica avizele atasate si aprobarea financiara.\n{$taskMarker}",
                'priority' => 'high',
                'deadline' => now()->addDay(),
                'assigned_to' => $order->responsible_user_id,
            ]);

            return;
        }

        $task = Task::create([
            'tenant_id' => $tenantId,
            'project_id' => $order->project_id,
            'phase_id' => $order->phase_id,
            'assigned_to' => $order->responsible_user_id,
            'created_by' => $actorId,
            'title' => sprintf('Verifica diferenta de cantitate (%.2f %s)', $maxDifference, (string) $order->ordered_unit),
            'description' => "Sistemul a detectat o diferenta pozitiva intre cantitatea declarata si cantitatea livrata. Verifica avizele atasate si aprobarea financiara.\n{$taskMarker}",
            'status' => 'todo',
            'priority' => 'high',
            'deadline' => now()->addDay(),
            'checklist' => [
                ['text' => 'Verifica avizele de livrare si transport', 'done' => false],
                ['text' => 'Confirma cantitatea reala in santier', 'done' => false],
                ['text' => 'Evalueaza statusul blocat la plata', 'done' => false],
            ],
        ]);

        $recipient = $order->responsibleUser;

        if (! $recipient instanceof User) {
            return;
        }

        $recipient->notify(new OperationalReminderNotification(
            event: 'resource_discrepancy',
            title: 'Diferenta la livrare resursa',
            message: sprintf('Comanda #%d are o diferenta de %.2f %s si necesita verificare.', $order->id, $maxDifference, (string) $order->ordered_unit),
            entityType: 'resource_order',
            entityId: (int) $order->id,
            projectId: $order->project_id,
            projectName: $order->project?->name,
            url: route('resource-orders.show', $order),
            severity: 'high',
        ));
    }

    private function findActiveDiscrepancyTask(ResourceOrder $order): ?Task
    {
        $query = Task::query()
            ->where('tenant_id', (int) $order->tenant_id)
            ->where('project_id', $order->project_id)
            ->where('phase_id', $order->phase_id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->where('description', 'like', '%[resource_order:' . (int) $order->id . ']%')
            ->latest('id');

        if ($order->responsible_user_id) {
            $query->where('assigned_to', $order->responsible_user_id);
        } else {
            $query->whereNull('assigned_to');
        }

        return $query->first();
    }

    private function cancelDiscrepancyFollowUp(ResourceOrder $order): void
    {
        $task = $this->findActiveDiscrepancyTask($order);

        if (! $task instanceof Task) {
            return;
        }

        $task->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    private function auditActionLabel(string $action): string
    {
        return match ($action) {
            'resource_order.created' => 'Comanda creata',
            'resource_order.deleted' => 'Comanda stearsa',
            'resource_order.document_attached' => 'Document atasat',
            'resource_order.document_deleted' => 'Document sters',
            'resource_order.confirmation_updated' => 'Confirmare actualizata',
            default => $action,
        };
    }

    private function logResourceAudit(string $action, Request $request, ResourceOrder $order, array $metadata = []): void
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            return;
        }

        AccessAudit::log(
            action: $action,
            actor: $actor,
            request: $request,
            resourceType: 'resource_order',
            resourceId: (int) $order->id,
            metadata: [
                ...$metadata,
                'resource_order_id' => (int) $order->id,
                'status' => (string) $order->status,
            ]
        );
    }

    private function applyLifecycleStatus(ResourceOrder $order, array $reconciliation, Collection $confirmations): void
    {
        $status = $this->resolveLifecycleStatus($reconciliation, $confirmations);

        if ($order->status !== $status) {
            $order->update(['status' => $status]);
        }
    }

    private function resolveLifecycleStatus(array $reconciliation, Collection $confirmations): string
    {
        if (($reconciliation['is_blocked'] ?? false) === true) {
            return 'blocked_payment';
        }

        if ($confirmations->contains(fn (ResourceConfirmation $item) => $item->status === 'rejected')) {
            return 'rejected';
        }

        $roleStatuses = $confirmations->keyBy('confirmation_role');

        $technicalRoles = ['site_manager', 'execution_manager', 'quality_manager'];
        $technicalComplete = collect($technicalRoles)
            ->every(fn (string $role) => ($roleStatuses->get($role)?->status ?? null) === 'confirmed');

        $anyTechnicalConfirmed = collect($technicalRoles)
            ->contains(fn (string $role) => ($roleStatuses->get($role)?->status ?? null) === 'confirmed');

        $financialStatus = $roleStatuses->get('financial_manager')?->status;

        if ($technicalComplete && $financialStatus === 'confirmed') {
            return 'approved';
        }

        if ($technicalComplete) {
            return 'financial_review';
        }

        if ($anyTechnicalConfirmed) {
            return 'verified';
        }

        return 'ordered';
    }

    /**
     * @param Collection<int, array<string, mixed>> $linkedDocuments
     * @param Collection<int, array<string, mixed>> $deliveries
     * @return array<string, mixed>
     */
    private function buildReconciliationSummary(ResourceOrder $order, Collection $linkedDocuments, Collection $deliveries): array
    {
        $tolerance = (float) config('resources.quantity_tolerance', 0.20);
        $ordered = (float) $order->ordered_quantity;

        $deliveredValues = $linkedDocuments
            ->whereIn('role', ['delivery_note', 'carrier_note'])
            ->pluck('delivered_quantity')
            ->filter(fn ($value) => $value !== null);

        $pumpFromDocs = $linkedDocuments
            ->where('role', 'pump_note')
            ->pluck('delivered_quantity')
            ->filter(fn ($value) => $value !== null);

        $pumpFromDeliveries = $deliveries
            ->pluck('equipment_reported_quantity')
            ->filter(fn ($value) => $value !== null);

        $consumedValues = $deliveries
            ->pluck('consumed_quantity')
            ->filter(fn ($value) => $value !== null);

        $hasDelivered = $deliveredValues->isNotEmpty();
        $hasPump = $pumpFromDocs->isNotEmpty() || $pumpFromDeliveries->isNotEmpty();
        $hasConsumed = $consumedValues->isNotEmpty();
        $hasLinkedDocumentDifference = $linkedDocuments->isNotEmpty();

        $delivered = $hasDelivered ? (float) $deliveredValues->max() : null;
        $pump = $hasPump
            ? (float) max((float) ($pumpFromDocs->max() ?? 0), (float) ($pumpFromDeliveries->max() ?? 0))
            : null;
        $consumed = $hasConsumed ? (float) $consumedValues->max() : null;

        $checks = [
            [
                'key' => 'declared_vs_delivered_documents',
                'label' => 'Declarat vs livrat (documente)',
                'left' => null,
                'right' => null,
                'is_applicable' => $hasLinkedDocumentDifference,
                'delta' => $hasLinkedDocumentDifference
                    ? (float) $linkedDocuments->map(fn (array $item) => abs((float) ($item['difference_quantity'] ?? 0)))->max()
                    : 0,
            ],
            [
                'key' => 'ordered_vs_delivered',
                'label' => 'Comandat vs livrat',
                'left' => $ordered,
                'right' => $delivered,
                'is_applicable' => $hasDelivered,
                'delta' => $hasDelivered && $delivered !== null ? abs($ordered - $delivered) : 0,
            ],
            [
                'key' => 'delivered_vs_pump',
                'label' => 'Livrat vs pompa',
                'left' => $delivered,
                'right' => $pump,
                'is_applicable' => $hasDelivered && $hasPump,
                'delta' => $hasDelivered && $hasPump && $delivered !== null && $pump !== null ? abs($delivered - $pump) : 0,
            ],
            [
                'key' => 'pump_vs_consumed',
                'label' => 'Pompa vs consum',
                'left' => $pump,
                'right' => $consumed,
                'is_applicable' => $hasPump && $hasConsumed,
                'delta' => $hasPump && $hasConsumed && $pump !== null && $consumed !== null ? abs($pump - $consumed) : 0,
            ],
        ];

        $checks = collect($checks)
            ->map(function (array $item) use ($tolerance) {
                $item['is_blocking'] = ($item['is_applicable'] ?? false) === true && (float) $item['delta'] > $tolerance;

                return $item;
            })
            ->values()
            ->all();

        return [
            'tolerance' => $tolerance,
            'checks' => $checks,
            'is_blocked' => collect($checks)->contains(fn (array $item) => ($item['is_blocking'] ?? false) === true),
            'max_delta' => (float) collect($checks)->max('delta'),
        ];
    }
}