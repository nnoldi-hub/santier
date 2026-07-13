<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\ResourceDocumentLink;
use App\Models\ResourceOrder;
use App\Support\ResourceOrderReconciliation;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaterialTraceabilityController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        $filters = [
            'q' => $request->string('q')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $materials = Material::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('resourceOrders')
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('name', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('code', 'like', '%' . $filters['q'] . '%');
                });
            })
            ->with(['resourceOrders.project:id,name', 'resourceOrders.deliveries', 'resourceOrders.documentLinks'])
            ->orderBy('name')
            ->get()
            ->map(fn (Material $material) => $this->summarizeMaterial($material, $tenantId));

        if ($filters['status'] !== '') {
            $materials = $materials->where('status', $filters['status']);
        }

        $total = $materials->count();
        $page = max(1, (int) $request->integer('page', 1));
        $paginatedRows = $materials->forPage($page, self::PER_PAGE)->values();

        return Inertia::render('MaterialTraceability/Index', [
            'materials' => [
                'data' => $paginatedRows,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / self::PER_PAGE)),
                'total' => $total,
            ],
            'filters' => $filters,
            'statusOptions' => [
                'ok' => 'Conform',
                'warning' => 'Cu diferente',
                'blocked' => 'Blocat la plata',
            ],
            'summary' => [
                'materials_tracked' => $total,
                'with_discrepancies' => $materials->whereIn('status', ['warning', 'blocked'])->count(),
                'total_ordered_value' => round((float) $materials->sum('total_ordered_value'), 2),
                'unpaid_invoices_total' => round((float) $materials->sum(fn (array $row) => $row['invoices']['unpaid_total']), 2),
            ],
        ]);
    }

    private function summarizeMaterial(Material $material, int $tenantId): array
    {
        $orders = $material->resourceOrders;

        $orderRows = $orders->map(function (ResourceOrder $order) {
            $reconciliation = ResourceOrderReconciliation::summarize(
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

            return [
                'id' => $order->id,
                'status' => $order->status,
                'status_label' => ResourceOrder::$statusLabels[$order->status] ?? $order->status,
                'project_name' => $order->project?->name,
                'ordered_quantity' => (float) $order->ordered_quantity,
                'delivered_quantity' => $reconciliation['delivered'],
                'consumed_quantity' => $reconciliation['consumed'],
                'ordered_value' => round((float) $order->ordered_quantity * (float) $order->unit_price, 2),
                'tone' => $this->toneForOrder($order, $reconciliation),
                'show_url' => route('resource-orders.show', $order->id),
            ];
        });

        $worstTone = 'ok';
        if ($orderRows->contains(fn (array $row) => $row['tone'] === 'blocked')) {
            $worstTone = 'blocked';
        } elseif ($orderRows->contains(fn (array $row) => $row['tone'] === 'warning')) {
            $worstTone = 'warning';
        }

        $invoices = MaterialInvoice::query()
            ->where('tenant_id', $tenantId)
            ->where('material_id', $material->id)
            ->get();

        return [
            'id' => $material->id,
            'name' => $material->name,
            'code' => $material->code,
            'unit' => $material->unit,
            'status' => $worstTone,
            'orders_count' => $orderRows->count(),
            'total_ordered' => round((float) $orderRows->sum('ordered_quantity'), 2),
            'total_delivered' => round((float) $orderRows->sum(fn (array $row) => $row['delivered_quantity'] ?? 0), 2),
            'total_consumed' => round((float) $orderRows->sum(fn (array $row) => $row['consumed_quantity'] ?? 0), 2),
            'total_ordered_value' => round((float) $orderRows->sum('ordered_value'), 2),
            'orders' => $orderRows->values(),
            'invoices' => [
                'count' => $invoices->count(),
                'total' => round((float) $invoices->sum('amount_total'), 2),
                'unpaid_total' => round((float) $invoices->whereIn('payment_status', ['unpaid', 'partial'])->sum('amount_total'), 2),
            ],
        ];
    }

    private function toneForOrder(ResourceOrder $order, array $reconciliation): string
    {
        if ($order->status === 'blocked_payment' || ($reconciliation['is_blocked'] ?? false) === true) {
            return 'blocked';
        }

        if ((float) ($reconciliation['max_delta'] ?? 0) > 0.01) {
            return 'warning';
        }

        return 'ok';
    }
}
