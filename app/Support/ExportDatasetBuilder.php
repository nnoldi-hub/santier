<?php

namespace App\Support;

use App\Models\Defect;
use App\Models\Document;
use App\Models\Material;
use App\Models\ResourceDelivery;
use App\Models\ResourceDocumentLink;
use App\Models\ResourceOrder;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\StageEquipment;
use App\Models\StageReport;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use App\Support\DemoScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class ExportDatasetBuilder
{
    private const PRIORITY_ALIASES = [
        'high' => ['high', 'urgent', 'critic', 'critice', 'critical'],
        'medium' => ['medium', 'mediu', 'medie', 'moderate'],
        'low' => ['low', 'scazut', 'minor'],
    ];

    private const CONTROL_TOKENS = [
        'proiect',
        'proiecte',
        'etapa',
        'etape',
        'contractor',
        'contractori',
        'defect',
        'defecte',
        'task',
        'taskuri',
        'utilaj',
        'utilaje',
        'material',
        'materiale',
        'resursa',
        'resurse',
        'aviz',
        'avize',
    ];

    public static function build(string $exportType, array $filters): array
    {
        return match ($exportType) {
            'projects' => self::projects($filters),
            'quotes' => self::quotes($filters),
            'materials' => self::materials($filters),
            'resource-comparison' => self::resourceComparison($filters),
            'material-timeline' => self::materialTimeline($filters),
            'equipment-consumption' => self::equipmentConsumption($filters),
            'costs' => self::costs($filters),
            'teams' => self::teams($filters),
            'tasks' => self::tasks($filters),
            'defects' => self::defects($filters),
            'wbs' => self::wbs($filters),
            'equipment' => self::equipment($filters),
            'documents' => self::documents($filters),
            'stage-reports' => self::stageReports($filters),
            'stage-tasks' => self::stageTasks($filters),
            'stage-progress' => self::stageProgress($filters),
            default => ['rows' => collect(), 'meta' => []],
        };
    }

    private static function stageProgress(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = ProjectPhase::query()
            ->with(['project:id,name,tenant_id', 'contractor:id,name', 'parent:id,name'])
            ->whereHas('project', fn ($q) => $q->where('tenant_id', $tenantId));

        self::applyProjectFilter($query, $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['contractor_id'])) {
            $query->where('contractor_id', $filters['contractor_id']);
        }

        $searchText = self::searchTerm($filters);
        if ($searchText !== null) {
            $text = $searchText;
            $query->where(function ($inner) use ($text) {
                $inner->where('name', 'like', "%{$text}%")
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$text}%"))
                    ->orWhereHas('contractor', fn ($contractorQuery) => $contractorQuery->where('name', 'like', "%{$text}%"));
            });
        }

        return [
            'rows' => $query
                ->orderByDesc('progress_pct')
                ->orderBy('project_id')
                ->orderBy('order')
                ->latest('id')
                ->get()
                ->map(function (ProjectPhase $phase) {
                    return [
                        'phase_id' => $phase->id,
                        'project' => $phase->project?->name,
                        'phase' => $phase->name,
                        'parent' => $phase->parent?->name,
                        'contractor' => $phase->contractor?->name,
                        'status' => $phase->status,
                        'progress_pct' => $phase->progress_pct,
                        'start_date' => optional($phase->start_date)->format('Y-m-d'),
                        'end_date' => optional($phase->end_date)->format('Y-m-d'),
                        'order' => $phase->order,
                    ];
                }),
            'meta' => ['title' => 'Progres etape'],
        ];
    }

    private static function stageReports(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = StageReport::query()
            ->with(['stage:id,project_id,name', 'stage.project:id,name,tenant_id', 'contractor:id,name', 'creator:id,name'])
            ->whereHas('stage.project', fn ($q) => $q->where('tenant_id', $tenantId));

        self::applyDateRange($query, 'report_date', $filters);

        if (!empty($filters['project_id'])) {
            $query->whereHas('stage', fn ($q) => $q->where('project_id', $filters['project_id']));
        }

        $searchText = self::searchTerm($filters);
        if ($searchText !== null) {
            $text = $searchText;
            $query->where(function ($inner) use ($text) {
                $inner->where('activities', 'like', "%{$text}%")
                    ->orWhere('issues', 'like', "%{$text}%");
            });
        }

        return [
            'rows' => $query->orderByDesc('report_date')->latest('id')->get(),
            'meta' => ['title' => 'Rapoarte etapa'],
        ];
    }

    private static function stageTasks(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = StageTask::query()
            ->with(['stage:id,project_id,name', 'stage.project:id,name,tenant_id', 'userAssignee:id,name', 'teamAssignee:id,name', 'contractorAssignee:id,name'])
            ->whereHas('stage.project', fn ($q) => $q->where('tenant_id', $tenantId));

        self::applyDateRange($query, 'deadline', $filters);

        if (!empty($filters['project_id'])) {
            $query->whereHas('stage', fn ($q) => $q->where('project_id', $filters['project_id']));
        }

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        self::applyInferredPriorityFilter($query, $filters, 'priority');

        $searchText = self::searchTerm($filters);
        if ($searchText !== null) {
            $text = $searchText;
            $query->where(function ($inner) use ($text) {
                $inner->where('title', 'like', "%{$text}%")
                    ->orWhere('description', 'like', "%{$text}%");
            });
        }

        return [
            'rows' => $query
                ->orderByRaw("CASE WHEN status = 'todo' THEN 1 WHEN status = 'in_progress' THEN 2 WHEN status = 'blocked' THEN 3 WHEN status = 'done' THEN 4 ELSE 5 END")
                ->orderBy('deadline')
                ->latest('id')
                ->get(),
            'meta' => ['title' => 'Taskuri etapa'],
        ];
    }

    private static function documents(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = Document::query()
            ->where('tenant_id', $tenantId)
            ->with(['project:id,name', 'stage:id,name', 'contractor:id,name']);

        self::applyDateRange($query, 'issued_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'notes', 'file_name'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('payment_status', $filters['status']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Documente financiare'],
        ];
    }

    private static function equipment(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = StageEquipment::query()
            ->with([
                'equipment:id,name,type,supplier_name,cost_per_hour,availability_status',
                'phase:id,project_id,name,status',
                'phase.project:id,name,tenant_id',
            ])
            ->whereHas('phase.project', fn ($q) => $q->where('tenant_id', $tenantId));

        self::applyDateRange($query, 'usage_start', $filters);
        self::applyProjectFilter($query, $filters);

        $searchText = self::searchTerm($filters);
        if ($searchText !== null) {
            $text = $searchText;
            $query->where(function ($inner) use ($text) {
                $inner->whereHas('equipment', function ($equipmentQuery) use ($text) {
                    $equipmentQuery->where('name', 'like', "%{$text}%")
                        ->orWhere('supplier_name', 'like', "%{$text}%");
                })->orWhereHas('phase', function ($phaseQuery) use ($text) {
                    $phaseQuery->where('name', 'like', "%{$text}%");
                });
            });
        }

        $rows = $query
            ->latest('id')
            ->get()
            ->map(function (StageEquipment $reservation) {
                $days = 1;
                if ($reservation->usage_start && $reservation->usage_end) {
                    $days = Carbon::parse($reservation->usage_start)
                        ->diffInDays(Carbon::parse($reservation->usage_end)) + 1;
                }

                $dailyHours = 8;
                $hourlyCost = (float) ($reservation->equipment?->cost_per_hour ?? 0);
                $estimatedCost = $hourlyCost * max(1, (int) $reservation->quantity) * max(1, $days) * $dailyHours;

                return [
                    'reservation_id' => $reservation->id,
                    'project' => $reservation->phase?->project?->name,
                    'phase' => $reservation->phase?->name,
                    'phase_status' => $reservation->phase?->status,
                    'equipment' => $reservation->equipment?->name,
                    'equipment_type' => $reservation->equipment?->type,
                    'supplier' => $reservation->equipment?->supplier_name,
                    'availability_status' => $reservation->equipment?->availability_status,
                    'quantity' => $reservation->quantity,
                    'usage_start' => optional($reservation->usage_start)->format('Y-m-d'),
                    'usage_end' => optional($reservation->usage_end)->format('Y-m-d'),
                    'days' => $days,
                    'hourly_cost' => $hourlyCost,
                    'estimated_cost' => round($estimatedCost, 2),
                ];
            });

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Utilaje rezervate pe etape'],
        ];
    }

    private static function wbs(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = ProjectPhase::query()
            ->with([
                'project:id,name',
                'contractor:id,name',
                'parent:id,name,parent_id',
            ])
            ->whereHas('project', fn ($q) => $q->where('tenant_id', $tenantId));

        self::applyDateRange($query, 'created_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['name', 'type', 'notes'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        return [
            'rows' => $query
                ->orderBy('project_id')
                ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->map(function (ProjectPhase $phase) {
                    $level = self::resolvePhaseLevel($phase);

                    return [
                        'id' => $phase->id,
                        'project' => $phase->project?->name,
                        'name' => $phase->name,
                        'level' => $level,
                        'wbs_path' => str_repeat('>', max(0, $level - 1)) . ($level > 1 ? ' ' : '') . $phase->name,
                        'parent' => $phase->parent?->name,
                        'status' => $phase->status,
                        'progress_pct' => $phase->progress_pct,
                        'contractor' => $phase->contractor?->name,
                        'start_date' => optional($phase->start_date)->format('Y-m-d'),
                        'end_date' => optional($phase->end_date)->format('Y-m-d'),
                    ];
                }),
            'meta' => ['title' => 'WBS Etape'],
        ];
    }

    private static function resolvePhaseLevel(ProjectPhase $phase): int
    {
        $level = 1;
        $parentId = $phase->parent_id;
        $safety = 0;

        while ($parentId && $safety < 20) {
            $level++;
            $parentId = ProjectPhase::query()->where('id', $parentId)->value('parent_id');
            $safety++;
        }

        return $level;
    }

    private static function projects(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = Project::query()->with('client:id,name')->where('tenant_id', $tenantId);

        self::applyDateRange($query, 'created_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['name', 'address', 'description'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Proiecte'],
        ];
    }

    private static function quotes(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = Quote::query()->with('project:id,name')->where('tenant_id', $tenantId);

        self::applyDateRange($query, 'created_at', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'notes'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Oferte si Devize'],
        ];
    }

    private static function materials(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = Material::query()->where('tenant_id', $tenantId);

        self::applyDateRange($query, 'created_at', $filters);
        self::applyTextFilter($query, ['name', 'code', 'supplier', 'category'], $filters);

        if (!$filters['include_inactive']) {
            $query->where('active', true);
        }

        return [
            'rows' => $query->orderBy('name')->get(),
            'meta' => ['title' => 'Materiale'],
        ];
    }

    private static function resourceComparison(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = ResourceOrder::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'project:id,name',
                'phase:id,name',
                'material:id,name,code,unit',
                'responsibleUser:id,name',
                'deliveries' => fn ($relation) => $relation->select([
                    'id',
                    'resource_order_id',
                    'declared_quantity',
                    'received_quantity',
                    'equipment_reported_quantity',
                    'consumed_quantity',
                    'returned_quantity',
                    'delivered_at',
                ]),
                'documentLinks' => fn ($relation) => $relation->select([
                    'id',
                    'resource_order_id',
                    'document_id',
                    'document_role',
                    'document_number',
                    'declared_quantity',
                    'delivered_quantity',
                    'difference_quantity',
                ]),
            ]);

        self::applyDateRange($query, 'delivery_date', $filters);
        self::applyProjectFilter($query, $filters);

        $searchText = self::searchTerm($filters);
        if ($searchText !== null) {
            $query->where(function ($inner) use ($searchText) {
                $inner->where('supplier_name', 'like', "%{$searchText}%")
                    ->orWhere('carrier_name', 'like', "%{$searchText}%")
                    ->orWhere('equipment_name', 'like', "%{$searchText}%")
                    ->orWhere('notes', 'like', "%{$searchText}%")
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$searchText}%"))
                    ->orWhereHas('phase', fn ($phaseQuery) => $phaseQuery->where('name', 'like', "%{$searchText}%"))
                    ->orWhereHas('material', fn ($materialQuery) => $materialQuery->where('name', 'like', "%{$searchText}%")->orWhere('code', 'like', "%{$searchText}%"));
            });
        }

        $rows = $query
            ->orderByDesc('delivery_date')
            ->latest('id')
            ->get()
            ->map(function (ResourceOrder $order) {
                $deliveries = $order->deliveries;
                $links = $order->documentLinks;

                $orderedQuantity = (float) $order->ordered_quantity;
                $receivedQuantity = (float) $deliveries->sum('received_quantity');
                $declaredQuantity = (float) $deliveries->sum('declared_quantity');
                $consumedQuantity = (float) $deliveries->sum('consumed_quantity');
                $returnedQuantity = (float) $deliveries->sum('returned_quantity');
                $documentDeliveredQuantity = (float) $links->sum('delivered_quantity');
                $documentDifferenceQuantity = (float) $links->sum('difference_quantity');

                return [
                    'order_id' => $order->id,
                    'project' => $order->project?->name,
                    'phase' => $order->phase?->name,
                    'resource_type' => $order->resource_type,
                    'resource_label' => ResourceOrder::$resourceTypeLabels[$order->resource_type] ?? $order->resource_type,
                    'material' => $order->material?->name ?? $order->equipment_name,
                    'material_code' => $order->material?->code,
                    'supplier' => $order->supplier_name,
                    'carrier' => $order->carrier_name,
                    'ordered_quantity' => $orderedQuantity,
                    'ordered_unit' => $order->ordered_unit,
                    'declared_quantity' => $declaredQuantity,
                    'received_quantity' => $receivedQuantity,
                    'consumed_quantity' => $consumedQuantity,
                    'returned_quantity' => $returnedQuantity,
                    'received_delta' => round($orderedQuantity - $receivedQuantity, 2),
                    'document_links_count' => $links->count(),
                    'delivery_notes_count' => $links->where('document_role', 'delivery_note')->count(),
                    'document_delivered_quantity' => $documentDeliveredQuantity,
                    'document_difference_quantity' => $documentDifferenceQuantity,
                    'status' => $order->status,
                    'delivery_date' => optional($order->delivery_date)->format('Y-m-d'),
                    'responsible' => $order->responsibleUser?->name,
                    'latest_delivery_at' => optional($deliveries->first()?->delivered_at)->format('Y-m-d H:i'),
                    'notes' => $order->notes,
                ];
            });

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Materiale & Avize comparative'],
        ];
    }

    private static function materialTimeline(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = ResourceOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('resource_type', 'material')
            ->with([
                'project:id,name',
                'phase:id,name',
                'material:id,name,code,unit',
                'responsibleUser:id,name',
                'deliveries',
                'documentLinks.document' => fn ($relation) => $relation->select(['id', 'issued_at']),
            ]);

        self::applyDateRange($query, 'delivery_date', $filters);
        self::applyProjectFilter($query, $filters);

        $searchText = self::searchTerm($filters);
        if ($searchText !== null) {
            $query->where(function ($inner) use ($searchText) {
                $inner->where('supplier_name', 'like', "%{$searchText}%")
                    ->orWhere('carrier_name', 'like', "%{$searchText}%")
                    ->orWhere('notes', 'like', "%{$searchText}%")
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', "%{$searchText}%"))
                    ->orWhereHas('phase', fn ($phaseQuery) => $phaseQuery->where('name', 'like', "%{$searchText}%"))
                    ->orWhereHas('material', fn ($materialQuery) => $materialQuery->where('name', 'like', "%{$searchText}%")->orWhere('code', 'like', "%{$searchText}%"));
            });
        }

        $orders = $query->latest('id')->get();
        $events = collect();

        foreach ($orders as $order) {
            $project = $order->project?->name;
            $phase = $order->phase?->name;
            $material = $order->material?->name;
            $orderStatus = ResourceOrder::$statusLabels[$order->status] ?? $order->status;

            $events->push([
                'project' => $project,
                'phase' => $phase,
                'material' => $material,
                'event_date' => optional($order->delivery_date)->format('Y-m-d'),
                'event_type' => 'comanda',
                'actor' => $order->supplier_name,
                'quantity' => (float) $order->ordered_quantity,
                'unit' => $order->ordered_unit,
                'document_number' => null,
                'order_status' => $orderStatus,
                'notes' => $order->notes,
            ]);

            foreach ($order->deliveries as $delivery) {
                $events->push([
                    'project' => $project,
                    'phase' => $phase,
                    'material' => $material,
                    'event_date' => optional($delivery->delivered_at)->format('Y-m-d H:i'),
                    'event_type' => 'livrare',
                    'actor' => $order->carrier_name ?: $order->supplier_name,
                    'quantity' => (float) $delivery->received_quantity,
                    'unit' => $order->ordered_unit,
                    'document_number' => null,
                    'order_status' => $orderStatus,
                    'notes' => $delivery->notes,
                ]);
            }

            foreach ($order->documentLinks as $link) {
                $events->push([
                    'project' => $project,
                    'phase' => $phase,
                    'material' => $material,
                    'event_date' => optional($link->document?->issued_at)->format('Y-m-d'),
                    'event_type' => 'document',
                    'actor' => $link->supplier_name ?: $link->carrier_name,
                    'quantity' => (float) $link->delivered_quantity,
                    'unit' => $order->ordered_unit,
                    'document_number' => $link->document_number,
                    'order_status' => $orderStatus,
                    'notes' => $link->notes,
                ]);
            }
        }

        return [
            'rows' => $events->sortBy('event_date')->values(),
            'meta' => ['title' => 'Trasabilitate materiale - cronologie'],
        ];
    }

    private static function equipmentConsumption(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = StageEquipment::query()
            ->with([
                'equipment:id,name,type,cost_per_hour,availability_status',
                'phase:id,project_id,name,status',
                'phase.project:id,name,tenant_id',
            ])
            ->whereHas('phase.project', fn ($q) => $q->where('tenant_id', $tenantId));

        self::applyDateRange($query, 'usage_start', $filters);

        $searchText = self::searchTerm($filters);
        if ($searchText !== null) {
            $text = $searchText;
            $query->where(function ($inner) use ($text) {
                $inner->whereHas('equipment', function ($equipmentQuery) use ($text) {
                    $equipmentQuery->where('name', 'like', "%{$text}%");
                })->orWhereHas('phase', function ($phaseQuery) use ($text) {
                    $phaseQuery->where('name', 'like', "%{$text}%");
                });
            });
        }

        $reservations = $query->latest('id')->get();
        $phaseIds = $reservations->pluck('phase.id')->filter()->unique()->values();

        $consumptionByPhase = ResourceOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('resource_type', 'material')
            ->whereIn('phase_id', $phaseIds)
            ->with(['deliveries:id,resource_order_id,consumed_quantity'])
            ->get()
            ->groupBy('phase_id')
            ->map(fn ($orders) => [
                'consumed_quantity' => (float) $orders->flatMap->deliveries->sum('consumed_quantity'),
                'orders_count' => $orders->count(),
            ]);

        $rows = $reservations->map(function (StageEquipment $reservation) use ($consumptionByPhase) {
            $phaseId = $reservation->phase?->id;
            $consumption = $phaseId ? $consumptionByPhase->get($phaseId) : null;
            $consumption ??= ['consumed_quantity' => 0.0, 'orders_count' => 0];

            return [
                'project' => $reservation->phase?->project?->name,
                'phase' => $reservation->phase?->name,
                'equipment' => $reservation->equipment?->name,
                'availability_status' => $reservation->equipment?->availability_status,
                'days' => EquipmentCostEstimator::reservedDays($reservation),
                'estimated_cost' => EquipmentCostEstimator::estimate($reservation),
                'phase_material_consumed_quantity' => round($consumption['consumed_quantity'], 2),
                'phase_material_orders_count' => $consumption['orders_count'],
                'reservation_id' => $reservation->id,
                'equipment_type' => $reservation->equipment?->type,
                'quantity' => $reservation->quantity,
                'usage_start' => optional($reservation->usage_start)->format('Y-m-d'),
                'usage_end' => optional($reservation->usage_end)->format('Y-m-d'),
                'hourly_cost' => (float) ($reservation->equipment?->cost_per_hour ?? 0),
            ];
        });

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Utilaje & consum materiale pe etapa'],
        ];
    }

    private static function costs(array $filters): array
    {
        $user = auth()->user();

        $projectsQuery = Project::query();
        DemoScope::applyProjectScope($projectsQuery, $user);

        $projectsQuery->with(['quotes' => function ($query) use ($filters) {
                self::applyDateRange($query, 'created_at', $filters);
                if (!empty($filters['status'])) {
                    $query->whereIn('status', $filters['status']);
                }
            }]);

        self::applyProjectFilter($projectsQuery, $filters);
        self::applyTextFilter($projectsQuery, ['name'], $filters);

        $rows = $projectsQuery->get()->map(function (Project $project) {
            $quotes = $project->quotes;
            $gross = (float) $quotes->sum('total_gross');
            $budget = $project->total_budget !== null ? (float) $project->total_budget : null;

            return [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'budget' => $budget,
                'quotes_count' => $quotes->count(),
                'total_net' => (float) $quotes->sum('total_net'),
                'total_tva' => (float) $quotes->sum('total_tva'),
                'total_gross' => $gross,
                'accepted_total_gross' => (float) $quotes->where('status', 'accepted')->sum('total_gross'),
                'diff_vs_budget' => $budget !== null ? round($gross - $budget, 2) : null,
            ];
        });

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Costuri proiecte'],
        ];
    }

    private static function teams(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = Team::query()->where('tenant_id', $tenantId)
            ->with([
                'leader:id,name',
                'members.user:id,name',
                'assignments.phase.project:id,name',
            ]);

        self::applyTextFilter($query, ['name', 'specialty'], $filters);

        if (!$filters['include_inactive']) {
            $query->where('active', true);
        }

        if (!empty($filters['team_id'])) {
            $query->where('id', $filters['team_id']);
        }

        $rows = collect();

        foreach ($query->orderBy('name')->get() as $team) {
            $projects = $team->assignments
                ->map(fn ($assignment) => $assignment->phase?->project?->name)
                ->filter()
                ->unique()
                ->values()
                ->implode(' | ');

            if ($team->members->isEmpty()) {
                $rows->push([
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'leader' => $team->leader?->name,
                    'member' => null,
                    'member_role' => null,
                    'specialty' => $team->specialty,
                    'active' => $team->active,
                    'assignments_count' => $team->assignments->count(),
                    'projects' => $projects,
                ]);
                continue;
            }

            foreach ($team->members as $member) {
                $rows->push([
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'leader' => $team->leader?->name,
                    'member' => $member->user?->name,
                    'member_role' => $member->role,
                    'specialty' => $team->specialty,
                    'active' => $team->active,
                    'assignments_count' => $team->assignments->count(),
                    'projects' => $projects,
                ]);
            }
        }

        return [
            'rows' => $rows,
            'meta' => ['title' => 'Echipe si responsabilitati'],
        ];
    }

    private static function tasks(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = Task::query()->where('tenant_id', $tenantId)
            ->with(['project:id,name', 'phase:id,name', 'assignee:id,name']);

        self::applyDateRange($query, 'deadline', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'description'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->whereIn('priority', $filters['priority']);
        }

        self::applyInferredPriorityFilter($query, $filters, 'priority');

        if (!empty($filters['assignee_ids'])) {
            $query->whereIn('assigned_to', $filters['assignee_ids']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Taskuri'],
        ];
    }

    private static function defects(array $filters): array
    {
        $tenantId = TenantContext::id();

        $query = Defect::query()->where('tenant_id', $tenantId)
            ->with(['project:id,name', 'phase:id,name', 'assignee:id,name']);

        self::applyDateRange($query, 'due_date', $filters);
        self::applyProjectFilter($query, $filters);
        self::applyTextFilter($query, ['title', 'description', 'location'], $filters);

        if (!empty($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->whereIn('priority', $filters['priority']);
        }

        self::applyInferredPriorityFilter($query, $filters, 'priority');

        if (!empty($filters['assignee_ids'])) {
            $query->whereIn('assigned_to', $filters['assignee_ids']);
        }

        return [
            'rows' => $query->latest('id')->get(),
            'meta' => ['title' => 'Defecte'],
        ];
    }

    private static function applyDateRange(Builder|Relation $query, string $column, array $filters): void
    {
        if (!empty($filters['from'])) {
            $query->whereDate($column, '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate($column, '<=', $filters['to']);
        }
    }

    private static function applyProjectFilter(Builder|Relation $query, array $filters): void
    {
        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }
    }

    private static function applyTextFilter(Builder|Relation $query, array $columns, array $filters): void
    {
        $text = self::searchTerm($filters);
        if ($text === null) {
            return;
        }

        $query->where(function ($inner) use ($columns, $text) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $inner->where($column, 'like', "%{$text}%");
                } else {
                    $inner->orWhere($column, 'like', "%{$text}%");
                }
            }
        });
    }

    private static function searchTerm(array $filters): ?string
    {
        $candidate = trim((string) ($filters['global_search'] ?? $filters['q'] ?? ''));
        if ($candidate === '') {
            return null;
        }

        $normalized = mb_strtolower($candidate);

        $dropTokens = [
            ...self::CONTROL_TOKENS,
            ...array_merge(...array_values(self::PRIORITY_ALIASES)),
        ];

        $filtered = collect(preg_split('/\s+/u', $normalized) ?: [])
            ->filter(fn ($token) => $token !== '' && !in_array($token, $dropTokens, true))
            ->implode(' ');

        return trim($filtered) !== '' ? trim($filtered) : $candidate;
    }

    private static function inferredPriority(array $filters): ?string
    {
        if (!empty($filters['priority'])) {
            return null;
        }

        $search = mb_strtolower(trim((string) ($filters['global_search'] ?? $filters['q'] ?? '')));
        if ($search === '') {
            return null;
        }

        foreach (self::PRIORITY_ALIASES as $priority => $aliases) {
            foreach ($aliases as $alias) {
                if (preg_match('/(^|\s)' . preg_quote($alias, '/') . '(\s|$)/u', $search) === 1) {
                    return $priority;
                }
            }
        }

        return null;
    }

    private static function applyInferredPriorityFilter(Builder|Relation $query, array $filters, string $column): void
    {
        $priority = self::inferredPriority($filters);

        if ($priority === null) {
            return;
        }

        $query->where($column, $priority);
    }
}
