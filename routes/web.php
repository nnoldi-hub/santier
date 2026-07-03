<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectPhaseController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamCalendarController;
use App\Http\Controllers\PhaseTeamAssignmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\DefectController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ContractorController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentCalendarController;
use App\Http\Controllers\ResourceCalendarController;
use App\Http\Controllers\CostTrackingController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GanttController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\PilotInviteController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\WbsController;
use App\Http\Controllers\StageEquipmentController;
use App\Http\Controllers\QualityCheckController;
use App\Http\Controllers\MaterialInvoiceController;
use App\Http\Controllers\StageReportController;
use App\Http\Controllers\StageTaskController;
use App\Http\Controllers\StageProgressController;
use App\Http\Controllers\ProjectAiToolsController;
use App\Http\Controllers\AccessAuditLogController;
use App\Http\Controllers\DocumentBrandingController;
use App\Http\Controllers\TenantRoleController;
use App\Http\Controllers\TenantUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationCenterController;
use App\Notifications\OperationalReminderNotification;
use App\Models\AppSetting;
use App\Http\Middleware\EnsureOnboardingCompleted;
use App\Support\AnalyticsTracker;
use App\Support\DemoScope;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use App\Models\Defect;
use App\Models\Contractor;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\PhaseTeamAssignment;
use App\Models\Quote;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\StageReport;
use App\Models\StageEquipment;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    AnalyticsTracker::track($request, 'landing_view', [
        'path' => '/',
    ]);

    $platformSettings = AppSetting::allWithDefaults(config('platform.defaults', []));

    return Inertia::render('Welcome', [
        'appName' => $platformSettings['app_name'] ?? config('app.name'),
        'companyName' => $platformSettings['company_name'] ?? config('app.name'),
        'trialDays' => (int) ($platformSettings['trial_days'] ?? 14),
        'supportEmail' => $platformSettings['support_email'] ?? null,
        'salesEmail' => $platformSettings['sales_email'] ?? null,
        'plans' => collect(config('pricing.plans', []))->map(function (array $plan, string $key) {
            return [
                'key' => $key,
                'name' => $plan['label'] ?? $key,
                'price' => (int) ($plan['price'] ?? 0),
                'period' => $plan['billing_period'] ?? 'luna',
                'highlight' => $key === 'pro',
                'badge' => match ($key) {
                    'free' => 'Start rapid',
                    'starter' => 'Brand de baza',
                    'pro' => 'Recomandat',
                    'enterprise' => 'White-label',
                    default => 'Plan',
                },
                'cta_label' => match ($key) {
                    'free' => 'Incearca demo',
                    'starter' => 'Alege branding de baza',
                    'pro' => 'Alege brand complet',
                    'enterprise' => 'Cere oferta enterprise',
                    default => 'Alege planul',
                },
                'items' => match ($key) {
                    'free' => ['Brand standard', 'Acces demo', '1 proiect', 'Evaluare rapida'],
                    'starter' => ['Logo si date firma', 'Branding de baza', '5 proiecte', '3 utilizatori'],
                    'pro' => ['Logo + culori', 'Antet si footer', 'Template documente', '10 utilizatori'],
                    'enterprise' => ['Mai multe sabloane', 'Aprobari', 'White-label', 'Domeniu propriu'],
                    default => [],
                },
            ];
        })->values(),
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register') && (bool) ($platformSettings['public_signup_enabled'] ?? true),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::post('/demo-request', [PilotInviteController::class, 'storePublic'])
    ->middleware('throttle:6,1')
    ->name('demo-request.store');

Route::get('/dashboard', function () {
    $dashboardRequest = request();
    $today = now()->toDateString();
    $user = $dashboardRequest->user();
    $tenantId = TenantContext::id($user);

    $calendarWindow = $dashboardRequest->string('calendar_window')->toString();
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

    $availableCalendarCategories = ['stages', 'tasks', 'subcontractors', 'equipment', 'documents', 'quality_checks'];
    $calendarCategories = collect(explode(',', $dashboardRequest->string('calendar_categories')->toString()))
        ->map(fn ($item) => trim((string) $item))
        ->filter()
        ->unique()
        ->values()
        ->all();
    $calendarCategories = array_values(array_intersect($availableCalendarCategories, $calendarCategories));
    if ($calendarCategories === []) {
        $calendarCategories = $availableCalendarCategories;
    }

    $activeTeamAssignmentsToday = PhaseTeamAssignment::query()
        ->with(['team:id,name', 'phase:id,project_id,name', 'phase.project:id,name'])
        ->whereHas('phase.project', fn ($query) => DemoScope::applyProjectScope($query, $user))
        ->where(function ($query) use ($today) {
            $query
                ->whereBetween('start_date', [$today, $today])
                ->orWhereBetween('end_date', [$today, $today])
                ->orWhere(function ($inner) use ($today) {
                    $inner
                        ->where(function ($startQuery) use ($today) {
                            $startQuery->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
                        })
                        ->where(function ($endQuery) use ($today) {
                            $endQuery->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                        });
                });
        })
        ->get();

    $overloadedTeams = $activeTeamAssignmentsToday
        ->groupBy('team_id')
        ->map(function ($rows) {
            $first = $rows->first();
            $needed = (int) $rows->sum('workers_needed');
            $assigned = (int) $rows->sum('workers_assigned');
            $parallel = $rows->count();

            return [
                'team_id' => (int) ($first->team_id ?? 0),
                'name' => $first?->team?->name ?? 'Echipa',
                'workers_needed' => $needed,
                'workers_assigned' => $assigned,
                'parallel_assignments' => $parallel,
                'is_overloaded' => $assigned > $needed || $parallel > 1,
            ];
        })
        ->filter(fn ($item) => $item['is_overloaded'])
        ->sortByDesc('parallel_assignments')
        ->values();

    $subcontractorPhasesToday = ProjectPhase::query()
        ->with(['contractor:id,name,type', 'project:id,name'])
        ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
        ->whereHas('contractor', fn ($query) => $query->whereIn('type', [Contractor::TYPE_SUBCONTRACTOR, Contractor::TYPE_PFA]))
        ->where(function ($query) use ($today) {
            $query
                ->whereBetween('start_date', [$today, $today])
                ->orWhereBetween('end_date', [$today, $today])
                ->orWhere(function ($inner) use ($today) {
                    $inner
                        ->where(function ($startQuery) use ($today) {
                            $startQuery->whereNull('start_date')->orWhereDate('start_date', '<=', $today);
                        })
                        ->where(function ($endQuery) use ($today) {
                            $endQuery->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                        });
                });
        })
        ->get(['id', 'project_id', 'name', 'contractor_id', 'start_date', 'end_date']);

    $parallelSubcontractors = $subcontractorPhasesToday
        ->groupBy('contractor_id')
        ->map(function ($rows) {
            $first = $rows->first();

            return [
                'contractor_id' => (int) ($first->contractor_id ?? 0),
                'name' => $first?->contractor?->name ?? 'Subcontractor',
                'parallel_projects' => $rows->pluck('project_id')->unique()->count(),
                'parallel_phases' => $rows->count(),
            ];
        })
        ->filter(fn ($item) => $item['parallel_projects'] > 1 || $item['parallel_phases'] > 1)
        ->sortByDesc('parallel_projects')
        ->values();

    $unavailableEquipment = Equipment::query()
        ->where('tenant_id', $tenantId)
        ->where('active', true)
        ->whereIn('availability_status', ['maintenance', 'unavailable'])
        ->orderBy('name')
        ->get(['id', 'name', 'availability_status']);

    $equipmentParallelReservations = StageEquipment::query()
        ->with(['equipment:id,name', 'phase.project:id,name'])
        ->whereHas('phase.project', fn ($query) => DemoScope::applyProjectScope($query, $user))
        ->where(function ($query) use ($today) {
            $query
                ->whereBetween('usage_start', [$today, $today])
                ->orWhereBetween('usage_end', [$today, $today])
                ->orWhere(function ($inner) use ($today) {
                    $inner
                        ->where(function ($startQuery) use ($today) {
                            $startQuery->whereNull('usage_start')->orWhereDate('usage_start', '<=', $today);
                        })
                        ->where(function ($endQuery) use ($today) {
                            $endQuery->whereNull('usage_end')->orWhereDate('usage_end', '>=', $today);
                        });
                });
        })
        ->get()
        ->groupBy('equipment_id')
        ->map(function ($rows) {
            $first = $rows->first();

            return [
                'equipment_id' => (int) ($first->equipment_id ?? 0),
                'name' => $first?->equipment?->name ?? 'Utilaj',
                'parallel_reservations' => $rows->count(),
            ];
        })
        ->filter(fn ($item) => $item['parallel_reservations'] > 1)
        ->sortByDesc('parallel_reservations')
        ->values();

    $materialsStockColumnsAvailable = Schema::hasColumns('materials', ['stock_quantity', 'min_stock_quantity']);
    $lowStockMaterials = collect();

    if ($materialsStockColumnsAvailable) {
        $lowStockMaterials = Material::query()
            ->where('tenant_id', $tenantId)
            ->where('active', true)
            ->whereNotNull('stock_quantity')
            ->whereNotNull('min_stock_quantity')
            ->whereRaw('stock_quantity <= min_stock_quantity')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock_quantity', 'min_stock_quantity']);
    }

    $teamHourlyRates = TeamMember::query()
        ->selectRaw('team_id, AVG(hourly_rate) as avg_rate')
        ->whereNotNull('hourly_rate')
        ->groupBy('team_id')
        ->pluck('avg_rate', 'team_id');

    $equipmentDailyCost = (float) StageEquipment::query()
        ->whereHas('phase.project', fn ($query) => DemoScope::applyProjectScope($query, $user))
        ->where(function ($query) use ($today) {
            $query
                ->whereBetween('usage_start', [$today, $today])
                ->orWhereBetween('usage_end', [$today, $today])
                ->orWhere(function ($inner) use ($today) {
                    $inner
                        ->where(function ($startQuery) use ($today) {
                            $startQuery->whereNull('usage_start')->orWhereDate('usage_start', '<=', $today);
                        })
                        ->where(function ($endQuery) use ($today) {
                            $endQuery->whereNull('usage_end')->orWhereDate('usage_end', '>=', $today);
                        });
                });
        })
        ->with('equipment:id,cost_per_hour')
        ->get()
        ->sum(function (StageEquipment $reservation) {
            return (float) ($reservation->equipment?->cost_per_hour ?? 0)
                * max(1, (int) $reservation->quantity)
                * 8;
        });

    $teamDailyCost = (float) $activeTeamAssignmentsToday->sum(function (PhaseTeamAssignment $assignment) use ($teamHourlyRates) {
        $workersAssigned = max(1, (int) ($assignment->workers_assigned ?: $assignment->workers_needed));
        $avgRate = (float) ($teamHourlyRates[$assignment->team_id] ?? 85);

        return $workersAssigned * $avgRate * 8;
    });

    $subcontractorCostByPhase = Document::query()
        ->with(['stage:id,name,project_id', 'stage.project:id,name', 'contractor:id,name,type'])
        ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
        ->whereHas('contractor', fn ($query) => $query->whereIn('type', [Contractor::TYPE_SUBCONTRACTOR, Contractor::TYPE_PFA]))
        ->whereNotNull('stage_id')
        ->get(['id', 'stage_id', 'contractor_id', 'amount'])
        ->groupBy('stage_id')
        ->map(function ($rows) {
            $first = $rows->first();

            return [
                'stage_id' => (int) ($first->stage_id ?? 0),
                'stage_name' => $first?->stage?->name ?? 'Etapa',
                'project_name' => $first?->stage?->project?->name ?? null,
                'contractors' => $rows
                    ->map(fn (Document $document) => $document->contractor?->name)
                    ->filter()
                    ->unique()
                    ->values(),
                'total_cost' => (float) $rows->sum('amount'),
            ];
        })
        ->sortByDesc('total_cost')
        ->values();

    $resourceAlerts = collect();

    foreach ($overloadedTeams->take(3) as $teamAlert) {
        $resourceAlerts->push([
            'event' => 'team_overloaded',
            'title' => 'Echipa supraincarcata',
            'message' => sprintf('Echipa %s este supraincarcata azi.', $teamAlert['name']),
            'entity_type' => 'team',
            'entity_id' => $teamAlert['team_id'],
            'project_id' => null,
            'project_name' => null,
            'url' => route('team-calendar.index', ['start_date' => $today, 'end_date' => $today, 'team_id' => $teamAlert['team_id']]),
            'severity' => 'high',
        ]);
    }

    foreach ($equipmentParallelReservations->take(3) as $equipmentAlert) {
        $resourceAlerts->push([
            'event' => 'equipment_parallel',
            'title' => 'Utilaj rezervat in paralel',
            'message' => sprintf('%s este rezervat in paralel.', $equipmentAlert['name']),
            'entity_type' => 'equipment',
            'entity_id' => $equipmentAlert['equipment_id'],
            'project_id' => null,
            'project_name' => null,
            'url' => route('equipment-calendar.index', ['start_date' => $today, 'end_date' => $today, 'equipment_id' => $equipmentAlert['equipment_id']]),
            'severity' => 'high',
        ]);
    }

    foreach ($lowStockMaterials->take(3) as $materialAlert) {
        $resourceAlerts->push([
            'event' => 'material_low_stock',
            'title' => 'Material cu stoc scazut',
            'message' => sprintf('%s are stoc sub pragul minim.', $materialAlert->name),
            'entity_type' => 'material',
            'entity_id' => $materialAlert->id,
            'project_id' => null,
            'project_name' => null,
            'url' => route('materials.index'),
            'severity' => 'medium',
        ]);
    }

    foreach ($parallelSubcontractors->take(3) as $subcontractorAlert) {
        $resourceAlerts->push([
            'event' => 'subcontractor_parallel',
            'title' => 'Subcontractor in paralel',
            'message' => sprintf('%s ruleaza simultan pe mai multe proiecte.', $subcontractorAlert['name']),
            'entity_type' => 'contractor',
            'entity_id' => $subcontractorAlert['contractor_id'],
            'project_id' => null,
            'project_name' => null,
            'url' => route('contractors.index'),
            'severity' => 'medium',
        ]);
    }

    foreach ($resourceAlerts as $alert) {
        $alreadySent = $user->notifications()
            ->where('created_at', '>=', now()->startOfDay())
            ->where('data', 'like', '%"event":"' . $alert['event'] . '"%')
            ->where('data', 'like', '%"entity_id":' . (int) $alert['entity_id'] . '%')
            ->exists();

        if (! $alreadySent) {
            $user->notify(new OperationalReminderNotification(
                $alert['event'],
                $alert['title'],
                $alert['message'],
                $alert['entity_type'],
                (int) $alert['entity_id'],
                $alert['project_id'],
                $alert['project_name'],
                $alert['url'],
                $alert['severity'],
            ));
        }
    }

    return Inertia::render('Dashboard', [
        'stats' => [
            'activeProjects' => DemoScope::applyProjectScope(Project::query(), $user)
                ->where('status', 'active')
                ->count(),
            'teams'   => Team::where('tenant_id', $tenantId)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->where('leader_id', $user->id))
                ->where('active', true)
                ->count(),
            'quotes'  => Quote::query()
                ->whereIn('status', ['sent', 'accepted'])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->count(),
            'defects' => Defect::query()
                ->whereIn('status', ['open', 'in_progress'])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->count(),
            'overdueTasks' => Task::where('tenant_id', $tenantId)
                ->whereIn('status', ['todo', 'in_progress'])
                ->whereDate('deadline', '<', $today)
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->count(),
            'delayedPhases' => ProjectPhase::whereIn('status', ['pending', 'in_progress'])
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<', $today)
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->count(),
            'avgProgress' => (int) round(ProjectPhase::whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->avg('progress_pct') ?? 0),
            'estimatedEquipmentCost' => (float) StageEquipment::query()
                ->whereHas('phase.project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->with('equipment:id,cost_per_hour')
                ->get()
                ->sum(function (StageEquipment $reservation) {
                    $dailyHours = 8;
                    $days = 1;

                    if ($reservation->usage_start && $reservation->usage_end) {
                        $days = Carbon::parse($reservation->usage_start)
                            ->diffInDays(Carbon::parse($reservation->usage_end)) + 1;
                    }

                    return (float) ($reservation->equipment?->cost_per_hour ?? 0)
                        * max(1, (int) $reservation->quantity)
                        * max(1, $days)
                        * $dailyHours;
                }),
            'documentsUnpaidCount' => Document::query()
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->count(),
            'documentsUnpaidAmount' => (float) Document::query()
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->sum('amount'),
            'documentsOverdueInvoices' => Document::query()
                ->where('type', 'invoice')
                ->where('payment_status', 'unpaid')
                ->whereDate('issued_at', '<=', now()->subDays(30)->toDateString())
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->count(),
            'stageTasksOpen' => StageTask::query()
                ->whereIn('status', ['todo', 'in_progress', 'blocked'])
                ->whereHas('stage.project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->count(),
            'overloadedTeamsCount' => $overloadedTeams->count(),
            'parallelSubcontractorsCount' => $parallelSubcontractors->count(),
            'unavailableEquipmentCount' => $unavailableEquipment->count(),
            'lowStockMaterialsCount' => $lowStockMaterials->count(),
            'equipmentDailyCost' => $equipmentDailyCost,
            'teamDailyCost' => $teamDailyCost,
            'subcontractorDailyCost' => (float) $subcontractorCostByPhase->sum('total_cost'),
        ],
        'resourceDashboard' => [
            'overloadedTeams' => $overloadedTeams->take(5)->values(),
            'parallelSubcontractors' => $parallelSubcontractors->take(5)->values(),
            'unavailableEquipment' => $unavailableEquipment->take(5)->values(),
            'lowStockMaterials' => $lowStockMaterials->take(5)->values(),
        ],
        'realtimeCosts' => [
            'equipment_daily_cost' => $equipmentDailyCost,
            'team_daily_cost' => $teamDailyCost,
            'subcontractor_cost_by_phase' => $subcontractorCostByPhase->take(6)->values(),
        ],
        'resourceAlerts' => $resourceAlerts->values(),
        'recentProjects' => DemoScope::applyProjectScope(Project::query()->with('client'), $user)
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'status', 'client_id']),
        'todayTasks' => Task::with(['project:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereDate('deadline', $today)
            ->whereIn('status', ['todo', 'in_progress'])
            ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
            ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 WHEN priority = 'low' THEN 3 ELSE 4 END")
            ->take(6)
            ->get(['id', 'project_id', 'title', 'status', 'priority', 'deadline']),
        'todayCalendar' => (function () use ($today, $user, $calendarWindow, $calendarCategories, $windowStart, $windowEnd, $windowStartDate, $windowEndDate) {
            $stages = ProjectPhase::query()
                ->with(['project:id,name'])
                ->whereIn('status', ['pending', 'in_progress'])
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
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->orderByRaw("CASE WHEN status = 'in_progress' THEN 1 ELSE 2 END")
                ->orderBy('start_date')
                ->take(6)
                ->get(['id', 'project_id', 'name', 'status', 'start_date', 'end_date'])
                ->map(fn (ProjectPhase $phase) => [
                    'id' => $phase->id,
                    'title' => $phase->name,
                    'project_name' => $phase->project?->name,
                    'status' => $phase->status,
                    'start_date' => optional($phase->start_date)->format('Y-m-d'),
                    'end_date' => optional($phase->end_date)->format('Y-m-d'),
                    'risk' => $phase->status === 'pending'
                        && !empty($phase->start_date)
                        && Carbon::parse($phase->start_date)->lt(now()->startOfDay()),
                    'criticality' => ($phase->status === 'pending'
                        && !empty($phase->start_date)
                        && Carbon::parse($phase->start_date)->lt(now()->startOfDay())) ? 'high' : 'normal',
                    'url' => route('projects.show', ['project' => $phase->project_id]) . '#phase-' . $phase->id,
                ])
                ->values();

            $tasks = StageTask::query()
                ->with(['stage:id,name,project_id', 'stage.project:id,name'])
                ->whereBetween('deadline', [$windowStart, $windowEnd])
                ->whereIn('status', ['todo', 'in_progress', 'blocked'])
                ->whereHas('stage.project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->orderByRaw("CASE WHEN status = 'blocked' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
                ->take(6)
                ->get(['id', 'stage_id', 'title', 'status', 'deadline'])
                ->map(fn (StageTask $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project_name' => $task->stage?->project?->name,
                    'stage_name' => $task->stage?->name,
                    'status' => $task->status,
                    'deadline' => optional($task->deadline)->format('Y-m-d H:i'),
                    'risk' => $task->status === 'blocked',
                    'criticality' => $task->status === 'blocked' ? 'high' : ($task->status === 'in_progress' ? 'medium' : 'normal'),
                    'url' => route('stage-tasks.edit', ['stage_task' => $task->id]),
                ])
                ->values();

            $equipment = StageEquipment::query()
                ->with(['equipment:id,name', 'phase:id,name,project_id', 'phase.project:id,name'])
                ->where(function ($query) use ($windowStartDate, $windowEndDate) {
                    $query
                        ->whereBetween('usage_start', [$windowStartDate, $windowEndDate])
                        ->orWhereBetween('usage_end', [$windowStartDate, $windowEndDate])
                        ->orWhere(function ($inner) use ($windowStartDate, $windowEndDate) {
                            $inner
                                ->where(function ($startQuery) use ($windowEndDate) {
                                    $startQuery->whereNull('usage_start')->orWhereDate('usage_start', '<=', $windowEndDate);
                                })
                                ->where(function ($endQuery) use ($windowStartDate) {
                                    $endQuery->whereNull('usage_end')->orWhereDate('usage_end', '>=', $windowStartDate);
                                });
                        });
                })
                ->whereHas('phase.project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->orderBy('usage_start')
                ->take(6)
                ->get(['id', 'stage_id', 'equipment_id', 'quantity', 'usage_start', 'usage_end'])
                ->map(fn (StageEquipment $reservation) => [
                    'id' => $reservation->id,
                    'title' => $reservation->equipment?->name,
                    'equipment_id' => $reservation->equipment_id,
                    'project_name' => $reservation->phase?->project?->name,
                    'stage_name' => $reservation->phase?->name,
                    'time_range' => trim((optional($reservation->usage_start)->format('Y-m-d') ?? '-') . ' - ' . (optional($reservation->usage_end)->format('Y-m-d') ?? '-')),
                    'quantity' => (int) $reservation->quantity,
                    'risk' => ($reservation->equipment?->availability_status ?? 'available') !== 'available',
                    'criticality' => (($reservation->equipment?->availability_status ?? 'available') !== 'available') ? 'high' : 'normal',
                    'url' => route('equipment-calendar.index', [
                        'equipment_id' => $reservation->equipment_id,
                        'start_date' => $windowStartDate,
                        'end_date' => $windowEndDate,
                    ]),
                ])
                ->values();

            $subcontractors = ProjectPhase::query()
                ->with(['project:id,name', 'contractor:id,name,type'])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->whereHas('contractor', fn ($q) => $q->whereIn('type', ['subcontractor', 'pfa']))
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
                ->get(['id', 'project_id', 'name', 'contractor_id', 'status', 'start_date', 'end_date'])
                ->map(fn (ProjectPhase $phase) => [
                    'id' => $phase->id,
                    'title' => $phase->contractor?->name,
                    'contractor_id' => $phase->contractor_id,
                    'project_name' => $phase->project?->name,
                    'stage_name' => $phase->name,
                    'status' => $phase->status,
                    'window' => trim((optional($phase->start_date)->format('Y-m-d') ?? '-') . ' - ' . (optional($phase->end_date)->format('Y-m-d') ?? '-')),
                    'risk' => $phase->status === 'pending'
                        && !empty($phase->start_date)
                        && Carbon::parse($phase->start_date)->lt(now()->startOfDay()),
                    'criticality' => ($phase->status === 'pending'
                        && !empty($phase->start_date)
                        && Carbon::parse($phase->start_date)->lt(now()->startOfDay())) ? 'high' : 'normal',
                    'url' => $phase->contractor_id ? route('contractors.edit', ['contractor' => $phase->contractor_id]) : route('contractors.index'),
                ])
                ->values();

            $documents = Document::query()
                ->with(['project:id,name', 'stage:id,name'])
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereBetween('issued_at', [$windowStartDate, $windowEndDate])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->orderByDesc('amount')
                ->take(6)
                ->get(['id', 'project_id', 'stage_id', 'title', 'amount', 'payment_status', 'issued_at'])
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'project_name' => $document->project?->name,
                    'stage_name' => $document->stage?->name,
                    'amount' => (float) $document->amount,
                    'issued_at' => optional($document->issued_at)->format('Y-m-d'),
                    'payment_status' => $document->payment_status,
                    'risk' => in_array($document->payment_status, ['unpaid', 'partial'], true),
                    'criticality' => in_array($document->payment_status, ['unpaid', 'partial'], true) ? 'medium' : 'normal',
                    'url' => route('documents.edit', ['document' => $document->id]),
                ])
                ->values();

            $quality = QualityCheck::query()
                ->with(['project:id,name', 'phase:id,name'])
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereBetween('planned_at', [$windowStart, $windowEnd])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->orderBy('planned_at')
                ->take(6)
                ->get(['id', 'project_id', 'phase_id', 'title', 'status', 'planned_at'])
                ->map(fn (QualityCheck $check) => [
                    'id' => $check->id,
                    'title' => $check->title,
                    'project_name' => $check->project?->name,
                    'stage_name' => $check->phase?->name,
                    'status' => $check->status,
                    'planned_at' => optional($check->planned_at)->format('Y-m-d H:i'),
                    'risk' => $check->status === 'pending',
                    'criticality' => $check->status === 'pending' ? 'medium' : 'normal',
                    'url' => route('quality-checks.edit', ['quality_check' => $check->id]),
                ])
                ->values();

            if (!in_array('stages', $calendarCategories, true)) {
                $stages = collect();
            }

            if (!in_array('tasks', $calendarCategories, true)) {
                $tasks = collect();
            }

            if (!in_array('equipment', $calendarCategories, true)) {
                $equipment = collect();
            }

            if (!in_array('subcontractors', $calendarCategories, true)) {
                $subcontractors = collect();
            }

            if (!in_array('documents', $calendarCategories, true)) {
                $documents = collect();
            }

            if (!in_array('quality_checks', $calendarCategories, true)) {
                $quality = collect();
            }

            $blockedByStage = StageTask::query()
                ->whereIn('stage_id', $stages->pluck('id')->filter()->values())
                ->where('status', 'blocked')
                ->selectRaw('stage_id, COUNT(*) as blocked_count')
                ->groupBy('stage_id')
                ->pluck('blocked_count', 'stage_id');

            $equipmentCoverageByStage = StageEquipment::query()
                ->whereIn('stage_id', $stages->pluck('id')->filter()->values())
                ->whereNotNull('usage_end')
                ->selectRaw('stage_id, MAX(usage_end) as last_usage_end')
                ->groupBy('stage_id')
                ->pluck('last_usage_end', 'stage_id');

            $stageDelayPredictions = $stages
                ->map(function (array $stage) use ($blockedByStage, $equipmentCoverageByStage) {
                    $score = 6;
                    $reasons = [];
                    $factors = [];

                    $blockedCount = (int) ($blockedByStage[$stage['id']] ?? 0);
                    if ($blockedCount > 0) {
                        $factorScore = min(20, $blockedCount * 7);
                        $score += $factorScore;
                        $reasons[] = $blockedCount . ' taskuri blocate';
                        $factors[] = [
                            'label' => 'Taskuri blocate',
                            'impact' => '+' . $factorScore,
                            'detail' => $blockedCount . ' in etapa',
                        ];
                    }

                    $phaseEnd = $stage['end_date'] ?? null;
                    $equipmentEnd = $equipmentCoverageByStage[$stage['id']] ?? null;
                    if ($phaseEnd && $equipmentEnd && Carbon::parse($equipmentEnd)->lt(Carbon::parse($phaseEnd))) {
                        $score += 12;
                        $reasons[] = 'utilaj rezervat doar pana la ' . Carbon::parse($equipmentEnd)->format('d.m');
                        $factors[] = [
                            'label' => 'Acoperire utilaje',
                            'impact' => '+12',
                            'detail' => 'rezervare pana la ' . Carbon::parse($equipmentEnd)->format('d.m'),
                        ];
                    }

                    if (($stage['status'] ?? '') === 'pending' && !empty($stage['start_date']) && Carbon::parse($stage['start_date'])->lt(now()->startOfDay())) {
                        $score += 10;
                        $reasons[] = 'start intarziat';
                        $factors[] = [
                            'label' => 'Start etapa',
                            'impact' => '+10',
                            'detail' => 'data start depasita',
                        ];
                    }

                    if ($factors === []) {
                        $factors[] = [
                            'label' => 'Semnal preventiv',
                            'impact' => '+6',
                            'detail' => 'fara incidente majore',
                        ];
                    }

                    return [
                        'id' => $stage['id'],
                        'title' => $stage['title'],
                        'project_name' => $stage['project_name'],
                        'risk_pct' => min(95, $score),
                        'reason' => $reasons !== [] ? implode('; ', $reasons) : 'monitorizare preventiva',
                        'factors' => $factors,
                        'url' => $stage['url'] ?? route('wbs.index'),
                    ];
                })
                ->sortByDesc('risk_pct')
                ->take(3)
                ->values();

            $stageCostById = Document::query()
                ->whereIn('stage_id', $stages->pluck('id')->filter()->values())
                ->selectRaw('stage_id, SUM(amount) as total_amount')
                ->groupBy('stage_id')
                ->pluck('total_amount', 'stage_id');

            $avgStageCost = max(1, (float) collect($stageCostById)->avg());

            $budgetPredictions = $stages
                ->map(function (array $stage) use ($stageCostById, $avgStageCost) {
                    $currentCost = (float) ($stageCostById[$stage['id']] ?? 0);
                    if ($currentCost <= 0) {
                        return null;
                    }

                    $ratio = $currentCost / $avgStageCost;
                    $riskPct = (int) min(90, max(5, round(($ratio - 0.8) * 25)));

                    $factors = [
                        [
                            'label' => 'Cost etapa vs medie',
                            'impact' => number_format($ratio, 2) . 'x',
                            'detail' => 'cost curent ' . number_format($currentCost, 0),
                        ],
                        [
                            'label' => 'Formula risc',
                            'impact' => $riskPct . '%',
                            'detail' => 'bazata pe raport cost/medie',
                        ],
                    ];

                    return [
                        'id' => $stage['id'],
                        'title' => $stage['title'],
                        'project_name' => $stage['project_name'],
                        'risk_pct' => $riskPct,
                        'reason' => 'cost documentat peste media etapelor (materiale scumpe)',
                        'factors' => $factors,
                        'url' => $stage['url'] ?? route('wbs.index'),
                    ];
                })
                ->filter()
                ->sortByDesc('risk_pct')
                ->take(3)
                ->values();

            $subcontractorLoadMap = ProjectPhase::query()
                ->whereIn('contractor_id', $subcontractors->pluck('contractor_id')->filter()->values())
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
                ->selectRaw('contractor_id, COUNT(*) as active_assignments')
                ->groupBy('contractor_id')
                ->pluck('active_assignments', 'contractor_id');

            $subcontractorPredictions = $subcontractors
                ->map(function (array $sub) use ($subcontractorLoadMap) {
                    $parallelProjects = (int) ($subcontractorLoadMap[$sub['contractor_id']] ?? 0);
                    $riskPct = (int) min(90, max(8, ($parallelProjects * 11)));

                    $factors = [
                        [
                            'label' => 'Proiecte paralele',
                            'impact' => (string) $parallelProjects,
                            'detail' => 'alocari simultane active',
                        ],
                        [
                            'label' => 'Formula risc',
                            'impact' => $riskPct . '%',
                            'detail' => '11% per proiect paralel',
                        ],
                    ];

                    return [
                        'id' => $sub['id'],
                        'title' => $sub['title'],
                        'project_name' => $sub['project_name'],
                        'parallel_projects' => $parallelProjects,
                        'risk_pct' => $riskPct,
                        'reason' => $parallelProjects . ' proiecte paralele',
                        'factors' => $factors,
                        'url' => $sub['url'] ?? route('contractors.index'),
                    ];
                })
                ->sortByDesc('risk_pct')
                ->take(3)
                ->values();

            $riskCount = collect([$stages, $tasks, $equipment, $subcontractors, $documents, $quality])
                ->flatten(1)
                ->filter(fn ($item) => (bool) ($item['risk'] ?? false))
                ->count();

            $stageRiskRate = $stages->count() > 0 ? $stages->where('criticality', 'high')->count() / $stages->count() : 0;
            $taskRiskRate = $tasks->count() > 0 ? $tasks->where('criticality', 'high')->count() / $tasks->count() : 0;
            $equipmentRiskRate = $equipment->count() > 0 ? $equipment->where('criticality', 'high')->count() / $equipment->count() : 0;
            $subcontractorRiskRate = $subcontractors->count() > 0 ? $subcontractors->where('criticality', 'high')->count() / $subcontractors->count() : 0;
            $documentRiskRate = $documents->count() > 0 ? $documents->where('criticality', 'medium')->count() / $documents->count() : 0;

            $riskScore = (int) round((
                ($stageRiskRate * 0.28)
                + ($taskRiskRate * 0.24)
                + ($equipmentRiskRate * 0.18)
                + ($subcontractorRiskRate * 0.14)
                + ($documentRiskRate * 0.16)
            ) * 100);

            $riskLevel = $riskScore >= 60 ? 'high' : ($riskScore >= 35 ? 'medium' : 'low');

            $totalEvents = $stages->count() + $tasks->count() + $equipment->count() + $subcontractors->count() + $documents->count() + $quality->count();

            $loadLevel = $totalEvents >= 8 ? 'critical' : ($totalEvents >= 4 ? 'normal' : 'light');

            $loadLabel = $loadLevel === 'critical'
                ? 'Zi critica'
                : ($loadLevel === 'normal' ? 'Zi normala' : 'Zi lejera');

            return [
                'date' => now()->isoFormat('D MMMM YYYY'),
                'window' => $calendarWindow,
                'categories' => $calendarCategories,
                'total_events' => $totalEvents,
                'risk_events' => $riskCount,
                'stages' => $stages,
                'tasks' => $tasks,
                'equipment' => $equipment,
                'subcontractors' => $subcontractors,
                'documents' => $documents,
                'quality_checks' => $quality,
                'load' => [
                    'level' => $loadLevel,
                    'label' => $loadLabel,
                    'max' => 12,
                    'value' => $totalEvents,
                ],
                'risk' => [
                    'score' => $riskScore,
                    'level' => $riskLevel,
                    'blocked_tasks' => $tasks->where('status', 'blocked')->count(),
                    'risky_stages' => $stages->where('criticality', 'high')->count(),
                    'unpaid_documents' => $documents->whereIn('payment_status', ['unpaid', 'partial'])->count(),
                    'predictive' => [
                        'stage_delay' => $stageDelayPredictions,
                        'budget_overrun' => $budgetPredictions,
                        'subcontractor' => $subcontractorPredictions,
                    ],
                ],
            ];
        })(),
        'delayedPhases' => ProjectPhase::with(['project:id,name'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
            ->orderBy('end_date')
            ->take(6)
            ->get(['id', 'project_id', 'name', 'status', 'end_date']),
        'openDefects' => Defect::with(['project:id,name'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
            ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 WHEN priority = 'low' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->take(6)
            ->get(['id', 'project_id', 'title', 'status', 'priority', 'due_date']),
        'stagePlanVsReal' => ProjectPhase::query()
            ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
            ->with([
                'project:id,name',
                'stageReports' => fn ($q) => $q->orderByDesc('report_date')->orderByDesc('id')->limit(1),
            ])
            ->withSum('documents as documented_cost', 'amount')
            ->orderByDesc('updated_at')
            ->take(8)
            ->get(['id', 'project_id', 'name', 'status', 'progress_pct'])
            ->map(function (ProjectPhase $phase) {
                $latestReport = $phase->stageReports->first();
                $planned = (int) ($phase->progress_pct ?? 0);
                $actual = $latestReport ? (int) $latestReport->progress_pct : $planned;

                return [
                    'id' => $phase->id,
                    'project_name' => $phase->project?->name,
                    'stage_name' => $phase->name,
                    'status' => $phase->status,
                    'planned_progress' => $planned,
                    'actual_progress' => $actual,
                    'progress_delta' => $actual - $planned,
                    'documented_cost' => (float) ($phase->documented_cost ?? 0),
                    'latest_report_date' => optional($latestReport?->report_date)->format('Y-m-d'),
                ];
            })
            ->values(),
    ]);
})->middleware(['auth', 'verified', EnsureOnboardingCompleted::class])->name('dashboard');

Route::middleware('auth')->get('/help', function () {
    return Inertia::render('Help/Index', [
        'gettingStartedSteps' => [
            [
                'title' => '1. Deschide Dashboard-ul',
                'text' => 'Aici vezi riscul zilei, incarcarea pe zi si ce trebuie actionat imediat.',
                'href' => route('dashboard'),
                'cta' => 'Mergi la Dashboard',
            ],
            [
                'title' => '2. Adauga primul proiect',
                'text' => 'Creeaza proiectul, apoi leaga clientul, etapele si bugetul initial.',
                'href' => route('projects.create'),
                'cta' => 'Creeaza proiect',
            ],
            [
                'title' => '3. Planifica executia',
                'text' => 'Completeaza WBS, taskuri, echipe si utilaje pentru a vedea calendarul real.',
                'href' => route('wbs.index'),
                'cta' => 'Deschide WBS',
            ],
            [
                'title' => '4. Urmareste blocajele',
                'text' => 'Defecte, documente, restante si riscuri apar in zona de atentie de pe Dashboard.',
                'href' => route('defects.index'),
                'cta' => 'Vezi defectele',
            ],
        ],
        'moduleGuides' => [
            [
                'name' => 'Dashboard',
                'summary' => 'Centrul zilnic de comanda: risc, incarcare, calendar operational si linkuri rapide.',
                'route' => route('dashboard'),
                'example' => 'Daca vezi risc mare la utilaje, intri direct pe proiectul sau calendarul utilajelor.',
            ],
            [
                'name' => 'Proiecte + WBS',
                'summary' => 'Structura proiectului, etape, progres si responsabilitati.',
                'route' => route('projects.index'),
                'example' => 'Pornesti cu proiectul, apoi spargi lucrarile in etape si sub-etape.',
            ],
            [
                'name' => 'Planificare',
                'summary' => 'Taskuri, Gantt, calendar de echipe si calendar de utilaje.',
                'route' => route('tasks.index'),
                'example' => 'Daca o echipa sta fara front de lucru, ajustezi taskurile si datele de start.',
            ],
            [
                'name' => 'Resurse',
                'summary' => 'Echipe interne, subcontractori, utilaje si materiale.',
                'route' => route('teams.index'),
                'example' => 'Verifici cine este alocat pe mai multe proiecte si unde apar supraincarcari.',
            ],
            [
                'name' => 'Financiar',
                'summary' => 'Oferte, documente, facturi materiale, cost tracking si situatii de lucrari.',
                'route' => route('quotes.index'),
                'example' => 'Daca ai cost mare pe o etapa, vezi imediat ce documente o imping peste medie.',
            ],
            [
                'name' => 'Calitate',
                'summary' => 'Defecte, verificari si rapoarte pentru inchiderea lucrarilor.',
                'route' => route('defects.index'),
                'example' => 'Defectele deschise raman in vedere pana sunt inchise si semnate.',
            ],
        ],
        'practicalExamples' => [
            [
                'title' => 'Exemplu: lansezi un proiect nou',
                'steps' => [
                    'Creezi proiectul si legi clientul.',
                    'Adaugi WBS-ul si imparti lucrarea in etape.',
                    'Aloci echipe, subcontractori si utilaje.',
                    'Urmaresti Dashboard-ul pentru risc si incarcare zilnica.',
                ],
                'links' => [
                    ['label' => 'Proiect nou', 'href' => route('projects.create')],
                    ['label' => 'WBS', 'href' => route('wbs.index')],
                    ['label' => 'Echipe', 'href' => route('teams.index')],
                ],
            ],
            [
                'title' => 'Exemplu: planifici o zi aglomerata',
                'steps' => [
                    'Deschizi Dashboard-ul si verifici incarcarea pe zi.',
                    'Filtrezi calendarul pe 7 sau 30 de zile daca ai mai multe alocari.',
                    'Te uiti la riscurile predictive si deschizi detaliile factorilor.',
                    'Mergi direct pe itemul cu problema folosind linkul din calendar.',
                ],
                'links' => [
                    ['label' => 'Dashboard', 'href' => route('dashboard')],
                    ['label' => 'Calendar utilaje', 'href' => route('equipment-calendar.index')],
                    ['label' => 'Calendar echipe', 'href' => route('team-calendar.index')],
                ],
            ],
            [
                'title' => 'Exemplu: tratezi un defect',
                'steps' => [
                    'Inregistrezi defectul imediat ce apare.',
                    'Il asignzi pe proiectul corect si ii pui prioritate.',
                    'Il urmaresti pana cand este rezolvat si verificat.',
                    'Daca sunt mai multe, folosesti pagina de defecte ca lista de actiune.',
                ],
                'links' => [
                    ['label' => 'Defect nou', 'href' => route('defects.create')],
                    ['label' => 'Lista defecte', 'href' => route('defects.index')],
                    ['label' => 'Verificari', 'href' => route('quality-checks.index')],
                ],
            ],
            [
                'title' => 'Exemplu: faci deviz/oferta in 5 minute',
                'steps' => [
                    'Deschizi Oferte/Devize si alegi sablonul canonic potrivit tipului de proiect.',
                    'Completezi cantitatile inteligente si alegi strategia de materiale (cu/fara materiale).',
                    'Verifici cele doua scenarii de pret (fara materiale vs cu materiale plafonate).',
                    'Salvezi oferta, generezi PDF si trimiti direct clientului.',
                ],
                'links' => [
                    ['label' => 'Oferta noua', 'href' => route('quotes.create')],
                    ['label' => 'Lista oferte', 'href' => route('quotes.index')],
                    ['label' => 'Documente financiare', 'href' => route('documents.index')],
                ],
            ],
            [
                'title' => 'Exemplu: configurezi documente profesionale',
                'steps' => [
                    'Intrii in Documente > Configurare documente.',
                    'Setezi emitentul, logo-ul, culorile si datele companiei.',
                    'Folosesti Preview pentru a verifica aspectul final inainte de salvare.',
                    'Generezi un PDF de test din oferta/document si verifici antetul si datele firmei.',
                ],
                'links' => [
                    ['label' => 'Configurare documente', 'href' => route('documents.branding.index')],
                    ['label' => 'Registru documente', 'href' => route('documents.index')],
                    ['label' => 'Oferte / Devize', 'href' => route('quotes.index')],
                ],
            ],
        ],
        'focusGuides' => [
            [
                'title' => 'Deviz / Oferta: ce completezi obligatoriu',
                'items' => [
                    'Alege proiectul si titlul ofertei.',
                    'Completeaza cantitatile inteligente (mp/buc) pentru calcul automat.',
                    'Verifica marja minima si rezumatul pe etape.',
                    'Decide strategia de materiale: client supplied sau capped allowance.',
                ],
                'href' => route('quotes.create'),
                'cta' => 'Deschide creare oferta',
            ],
            [
                'title' => 'Deviz / Oferta: verificari inainte de trimitere',
                'items' => [
                    'Verifica etapele cu marja sub pragul minim.',
                    'Confirma valorile de materiale plafonate (parchet, gresie, vopsea/glet).',
                    'Compara devizul fara materiale cu cel cu materiale.',
                    'Genereaza PDF-ul final si revizuieste brandingul.',
                ],
                'href' => route('quotes.index'),
                'cta' => 'Vezi ofertele',
            ],
            [
                'title' => 'Configurare documente: ce poti personaliza',
                'items' => [
                    'Emitent document (persoana sau departament).',
                    'Logo companie (URL sau upload).',
                    'Culoare branding aleasa vizual din preseturi.',
                    'Date de contact si adresa afisate in PDF.',
                ],
                'href' => route('documents.branding.index'),
                'cta' => 'Deschide configurare',
            ],
        ],
        'faqs' => [
            [
                'question' => 'De unde incep daca nu am mai folosit aplicatia?',
                'answer' => 'Incepe cu Dashboard-ul, apoi adauga un proiect, o etapa si o echipa. Dupa aceea vei vedea calendarul si riscurile.',
            ],
            [
                'question' => 'Cum gasesc rapid ce este blocat azi?',
                'answer' => 'Uita-te la cardul de risc din Dashboard, la incarcarea pe zi si la lista de atentie. Fiecare card are link direct.',
            ],
            [
                'question' => 'Unde vad utilajele si echipele ocupate?',
                'answer' => 'In calendarele dedicate sau in zona de resurse. De acolo poti sari direct pe elementul care trebuie ajustat.',
            ],
            [
                'question' => 'Cum identific o problema financiara?',
                'answer' => 'Deschide documentele, cost tracking si situatiile de lucrari. Daca exista supracost, este vizibil si in Dashboard.',
            ],
            [
                'question' => 'Ce fac daca nu stiu unde se afla o functie?',
                'answer' => 'Deschide Ajutor si urmareste sectiunea de module. Fiecare modul are un exemplu si un link spre pagina lui.',
            ],
            [
                'question' => 'Cum fac rapid doua devize (cu si fara materiale)?',
                'answer' => 'In pagina de oferta selectezi strategia materiale, completezi cantitatile inteligente, apoi verifici in rezumat cele doua scenarii generate automat.',
            ],
            [
                'question' => 'Unde setez emitentul, logo-ul si culorile pentru PDF?',
                'answer' => 'Din Documente > Configurare documente. Acolo ai setarile de branding si preview inainte de salvare.',
            ],
        ],
    ]);
})->name('help.index');

Route::middleware('auth')->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding/step-1', [OnboardingController::class, 'storeStep1'])->name('onboarding.step1');
    Route::post('onboarding/step-2', [OnboardingController::class, 'storeStep2'])->name('onboarding.step2');
    Route::post('onboarding/step-3', [OnboardingController::class, 'storeStep3'])->name('onboarding.step3');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::middleware(EnsureOnboardingCompleted::class)->group(function () {
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('pilot-invites', [PilotInviteController::class, 'index'])->name('pilot-invites.index');
        Route::post('pilot-invites', [PilotInviteController::class, 'store'])->name('pilot-invites.store');
        Route::patch('pilot-invites/{pilotInvite}/status', [PilotInviteController::class, 'updateStatus'])->name('pilot-invites.status');

        Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
        Route::patch('billing', [BillingController::class, 'update'])->name('billing.update');

        Route::get('account/users', [TenantUserController::class, 'index'])->name('account.users.index');
        Route::post('account/users/invite', [TenantUserController::class, 'invite'])->name('account.users.invite');
        Route::patch('account/users/{membership}/status', [TenantUserController::class, 'updateStatus'])->name('account.users.status.update');
        Route::patch('account/users/{membership}/role', [TenantUserController::class, 'updateRole'])->name('account.users.role.update');

        Route::get('account/roles', [TenantRoleController::class, 'index'])->name('account.roles.index');
        Route::post('account/roles', [TenantRoleController::class, 'store'])->name('account.roles.store');
        Route::patch('account/roles/{role}', [TenantRoleController::class, 'update'])->name('account.roles.update');
        Route::delete('account/roles/{role}', [TenantRoleController::class, 'destroy'])->name('account.roles.destroy');
        Route::get('account/audit', [AccessAuditLogController::class, 'index'])->name('account.audit.index');
        Route::get('account/audit/export', [AccessAuditLogController::class, 'exportCsv'])->name('account.audit.export');
        Route::get('account/notifications', [NotificationCenterController::class, 'index'])->name('account.notifications.index');

        Route::get('admin', [AdminController::class, 'index'])->name('admin.index');
        Route::patch('admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
        Route::patch('admin/users/{user}/subscription', [AdminController::class, 'updateSubscription'])->name('admin.users.subscription.update');

        // Proiecte & Clienti
        Route::resource('projects', ProjectController::class)
            ->middlewareFor(['index', 'show'], 'permission:projects.view')
            ->middlewareFor(['create', 'store'], 'permission:projects.create')
            ->middlewareFor(['edit', 'update'], 'permission:projects.edit')
            ->middlewareFor(['destroy'], 'permission:projects.delete');
        Route::get('wbs', [WbsController::class, 'index'])->name('wbs.index');
        Route::patch('wbs/phases/{phase}', [WbsController::class, 'updatePhase'])->name('wbs.phases.update');
        Route::resource('clients', ClientController::class);
        Route::get('gantt', [GanttController::class, 'index'])->middleware('plan:gantt')->name('gantt.index');
        Route::resource('tasks', TaskController::class)->except('show');
        Route::resource('teams', TeamController::class);
        Route::get('team-calendar', [TeamCalendarController::class, 'index'])->name('team-calendar.index');
        Route::get('equipment-calendar', [EquipmentCalendarController::class, 'index'])->name('equipment-calendar.index');
        Route::get('resource-calendar', [ResourceCalendarController::class, 'index'])->name('resource-calendar.index');
        Route::resource('contractors', ContractorController::class)->except('show');
        Route::resource('equipment', EquipmentController::class)->except('show');
        Route::resource('documents', DocumentController::class)->except('show');
        Route::middleware('plan:document_branding')->group(function () {
            Route::get('documente/configurare', [DocumentBrandingController::class, 'index'])->name('documents.branding.index');
            Route::patch('documente/configurare', [DocumentBrandingController::class, 'update'])->name('documents.branding.update');
        });
        Route::resource('stage-reports', StageReportController::class)->except('show');
        Route::get('situatii-de-lucrari', [StageReportController::class, 'index'])->name('situatii-lucrari.index');
        Route::resource('stage-tasks', StageTaskController::class)->except('show');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('documents/{document}/pdf', [DocumentController::class, 'pdf'])->name('documents.pdf');
        Route::get('procese-verbale', [DocumentController::class, 'index'])->name('procese-verbale.index');
        Route::get('documente-subcontractori', [ContractorController::class, 'subcontractors'])->name('documente-subcontractori.index');
        Route::get('cost-tracking', [CostTrackingController::class, 'index'])->name('cost-tracking.index');
        Route::get('stage-progress', [StageProgressController::class, 'index'])->name('stage-progress.index');
        Route::resource('defects', DefectController::class)->except('show');
        Route::resource('quality-checks', QualityCheckController::class)->except('show');
        Route::get('quality-checks/{quality_check}/pdf', [QualityCheckController::class, 'pdf'])->name('quality-checks.pdf');
        Route::get('rapoarte-calitate', [QualityCheckController::class, 'index'])->name('rapoarte-calitate.index');
        Route::resource('materials', MaterialController::class)->except('show');
        Route::resource('quotes', QuoteController::class)->except('show')
            ->middlewareFor(['index'], 'permission:quotes.view')
            ->middlewareFor(['create', 'store'], 'permission:quotes.create')
            ->middlewareFor(['edit', 'update'], 'permission:quotes.edit')
            ->middlewareFor(['destroy'], 'permission:quotes.delete');
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->middleware('permission:quotes.view')->name('quotes.pdf');
        Route::patch('quotes/{quote}/accept', [QuoteController::class, 'accept'])->middleware('permission:quotes.approve')->name('quotes.accept');
        Route::patch('quotes/{quote}/send', [QuoteController::class, 'send'])->middleware('permission:quotes.edit')->name('quotes.send');
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convertToProject'])->middleware('permission:quotes.edit|projects.create')->name('quotes.convert');
        Route::post('quotes/{quote}/template', [QuoteController::class, 'saveAsTemplate'])->middleware('permission:quotes.create|quotes.edit')->name('quotes.template.store');
        Route::resource('material-invoices', MaterialInvoiceController::class)->except('show');

        Route::middleware('plan:exports_csv')->group(function () {
            Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
            Route::get('exports/projects', [ExportController::class, 'projectsCsv'])->name('exports.projects');
            Route::get('exports/quotes', [ExportController::class, 'quotesCsv'])->name('exports.quotes');
            Route::get('exports/materials', [ExportController::class, 'materialsCsv'])->name('exports.materials');
            Route::get('exports/costs', [ExportController::class, 'costsCsv'])->name('exports.costs');
            Route::get('exports/teams', [ExportController::class, 'teamsResponsibilitiesCsv'])->name('exports.teams');
            Route::get('exports/tasks', [ExportController::class, 'tasksCsv'])->name('exports.tasks');
            Route::get('exports/defects', [ExportController::class, 'defectsCsv'])->name('exports.defects');
            Route::get('exports/wbs', [ExportController::class, 'wbsCsv'])->name('exports.wbs');
            Route::get('exports/equipment', [ExportController::class, 'equipmentCsv'])->name('exports.equipment');
            Route::get('exports/documents', [ExportController::class, 'documentsCsv'])->name('exports.documents');
            Route::get('exports/stage-reports', [ExportController::class, 'stageReportsCsv'])->name('exports.stage-reports');
            Route::get('exports/stage-tasks', [ExportController::class, 'stageTasksCsv'])->name('exports.stage-tasks');
            Route::get('exports/stage-progress', [ExportController::class, 'stageProgressCsv'])->name('exports.stage-progress');
        });

        Route::middleware('plan:exports_enterprise')->group(function () {
            Route::get('exports/project/{project}/package', [ExportController::class, 'projectPackage'])->name('exports.project.package');
            Route::get('exports/workbook', [ExportController::class, 'workbook'])->name('exports.workbook');
            Route::get('exports/managerial-pdf', [ExportController::class, 'managerialPdf'])->name('exports.managerial-pdf');
            Route::post('exports/subscriptions', [ExportController::class, 'subscribe'])->name('exports.subscriptions.store');
            Route::post('exports/subscriptions/{subscription}/run', [ExportController::class, 'runSubscription'])->name('exports.subscriptions.run');
            Route::patch('exports/subscriptions/{subscription}/toggle', [ExportController::class, 'toggleSubscription'])->name('exports.subscriptions.toggle');
        });

        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
        Route::patch('defects/{defect}/status', [DefectController::class, 'updateStatus'])->name('defects.status');
        Route::patch('quality-checks/{quality_check}/status', [QualityCheckController::class, 'updateStatus'])->name('quality-checks.status');
        Route::post('teams/{team}/members', [TeamController::class, 'storeMember'])->name('teams.members.store');
        Route::delete('teams/{team}/members/{member}', [TeamController::class, 'removeMember'])->name('teams.members.destroy');

        // Etape proiect (nested sub projects)
        Route::post('projects/{project}/phases', [ProjectPhaseController::class, 'store'])->name('phases.store');
        Route::put('projects/{project}/phases/{phase}', [ProjectPhaseController::class, 'update'])->name('phases.update');
        Route::patch('projects/{project}/phases/{phase}/timeline', [ProjectPhaseController::class, 'updateTimeline'])->name('phases.timeline.update');
        Route::delete('projects/{project}/phases/{phase}', [ProjectPhaseController::class, 'destroy'])->name('phases.destroy');
        Route::patch('projects/{project}/phases/{phase}/progress', [ProjectPhaseController::class, 'updateProgress'])->name('phases.progress');
        Route::post('projects/{project}/phases/{phase}/assignments', [PhaseTeamAssignmentController::class, 'store'])->name('phase-assignments.store');
        Route::delete('projects/{project}/phases/{phase}/assignments/{assignment}', [PhaseTeamAssignmentController::class, 'destroy'])->name('phase-assignments.destroy');
        Route::post('projects/{project}/phases/{phase}/equipment', [StageEquipmentController::class, 'store'])->name('stage-equipment.store');
        Route::delete('projects/{project}/phases/{phase}/equipment/{reservation}', [StageEquipmentController::class, 'destroy'])->name('stage-equipment.destroy');
        Route::post('projects/{project}/roles', [ProjectController::class, 'storeRole'])->name('projects.roles.store');
        Route::post('projects/{project}/roles/bulk', [ProjectController::class, 'storeRolesBulk'])->name('projects.roles.bulk.store');
        Route::patch('projects/{project}/roles/{assignment}', [ProjectController::class, 'updateRole'])->name('projects.roles.update');
        Route::delete('projects/{project}/roles/{assignment}', [ProjectController::class, 'destroyRole'])->name('projects.roles.destroy');
        Route::post('projects/{project}/ai/invoice/extract', [ProjectAiToolsController::class, 'extractInvoice'])->name('projects.ai.invoice.extract');
        Route::post('projects/{project}/ai/invoice/commit', [ProjectAiToolsController::class, 'commitInvoice'])->name('projects.ai.invoice.commit');
        Route::post('projects/{project}/ai/budget-alert', [ProjectAiToolsController::class, 'budgetAlert'])->name('projects.ai.budget-alert');
        Route::post('projects/{project}/ai/estimate/generate', [ProjectAiToolsController::class, 'generateEstimate'])->name('projects.ai.estimate.generate');
        Route::post('projects/{project}/ai/estimate/commit', [ProjectAiToolsController::class, 'commitEstimate'])->name('projects.ai.estimate.commit');
    });
});

require __DIR__.'/auth.php';
