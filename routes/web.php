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
use App\Http\Controllers\CostTrackingController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\GanttController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BillingController;
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
use App\Http\Middleware\EnsureOnboardingCompleted;
use App\Support\AnalyticsTracker;
use App\Support\DemoScope;
use Illuminate\Http\Request;
use App\Models\Defect;
use App\Models\Document;
use App\Models\Quote;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\StageReport;
use App\Models\StageEquipment;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    AnalyticsTracker::track($request, 'landing_view', [
        'path' => '/',
    ]);

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $today = now()->toDateString();
    $user = request()->user();

    return Inertia::render('Dashboard', [
        'stats' => [
            'activeProjects' => DemoScope::applyProjectScope(Project::query(), $user)
                ->where('status', 'active')
                ->count(),
            'teams'   => Team::where('tenant_id', 1)
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
            'overdueTasks' => Task::where('tenant_id', 1)
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
        ],
        'recentProjects' => DemoScope::applyProjectScope(Project::query()->with('client'), $user)
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'status', 'client_id']),
        'todayTasks' => Task::with(['project:id,name'])
            ->where('tenant_id', 1)
            ->whereDate('deadline', $today)
            ->whereIn('status', ['todo', 'in_progress'])
            ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
            ->orderByRaw("CASE WHEN priority = 'high' THEN 1 WHEN priority = 'medium' THEN 2 WHEN priority = 'low' THEN 3 ELSE 4 END")
            ->take(6)
            ->get(['id', 'project_id', 'title', 'status', 'priority', 'deadline']),
        'todayCalendar' => (function () use ($today, $user) {
            $stages = ProjectPhase::query()
                ->with(['project:id,name'])
                ->whereIn('status', ['pending', 'in_progress'])
                ->where(function ($query) use ($today) {
                    $query
                        ->whereDate('start_date', $today)
                        ->orWhereDate('end_date', $today)
                        ->orWhere(function ($inner) use ($today) {
                            $inner
                                ->whereNotNull('start_date')
                                ->whereNotNull('end_date')
                                ->whereDate('start_date', '<=', $today)
                                ->whereDate('end_date', '>=', $today);
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
                ])
                ->values();

            $tasks = StageTask::query()
                ->with(['stage:id,name,project_id', 'stage.project:id,name'])
                ->whereDate('deadline', $today)
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
                ])
                ->values();

            $equipment = StageEquipment::query()
                ->with(['equipment:id,name', 'phase:id,name,project_id', 'phase.project:id,name'])
                ->where(function ($query) use ($today) {
                    $query
                        ->whereDate('usage_start', $today)
                        ->orWhereDate('usage_end', $today)
                        ->orWhere(function ($inner) use ($today) {
                            $inner
                                ->whereNotNull('usage_start')
                                ->whereNotNull('usage_end')
                                ->whereDate('usage_start', '<=', $today)
                                ->whereDate('usage_end', '>=', $today);
                        });
                })
                ->whereHas('phase.project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->orderBy('usage_start')
                ->take(6)
                ->get(['id', 'stage_id', 'equipment_id', 'quantity', 'usage_start', 'usage_end'])
                ->map(fn (StageEquipment $reservation) => [
                    'id' => $reservation->id,
                    'title' => $reservation->equipment?->name,
                    'project_name' => $reservation->phase?->project?->name,
                    'stage_name' => $reservation->phase?->name,
                    'time_range' => trim((optional($reservation->usage_start)->format('Y-m-d') ?? '-') . ' - ' . (optional($reservation->usage_end)->format('Y-m-d') ?? '-')),
                    'quantity' => (int) $reservation->quantity,
                    'risk' => false,
                ])
                ->values();

            $subcontractors = ProjectPhase::query()
                ->with(['project:id,name', 'contractor:id,name,type'])
                ->whereHas('project', fn ($q) => DemoScope::applyProjectScope($q, $user))
                ->whereHas('contractor', fn ($q) => $q->whereIn('type', ['subcontractor', 'pfa']))
                ->where(function ($query) use ($today) {
                    $query
                        ->whereDate('start_date', $today)
                        ->orWhereDate('end_date', $today)
                        ->orWhere(function ($inner) use ($today) {
                            $inner
                                ->whereNotNull('start_date')
                                ->whereNotNull('end_date')
                                ->whereDate('start_date', '<=', $today)
                                ->whereDate('end_date', '>=', $today);
                        });
                })
                ->orderBy('start_date')
                ->take(6)
                ->get(['id', 'project_id', 'name', 'contractor_id', 'status', 'start_date', 'end_date'])
                ->map(fn (ProjectPhase $phase) => [
                    'id' => $phase->id,
                    'title' => $phase->contractor?->name,
                    'project_name' => $phase->project?->name,
                    'stage_name' => $phase->name,
                    'status' => $phase->status,
                    'window' => trim((optional($phase->start_date)->format('Y-m-d') ?? '-') . ' - ' . (optional($phase->end_date)->format('Y-m-d') ?? '-')),
                    'risk' => $phase->status === 'pending'
                        && !empty($phase->start_date)
                        && Carbon::parse($phase->start_date)->lt(now()->startOfDay()),
                ])
                ->values();

            $documents = Document::query()
                ->with(['project:id,name', 'stage:id,name'])
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->whereDate('issued_at', $today)
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
                ])
                ->values();

            $quality = QualityCheck::query()
                ->with(['project:id,name', 'phase:id,name'])
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereDate('planned_at', $today)
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
                ])
                ->values();

            $riskCount = collect([$stages, $tasks, $equipment, $subcontractors, $documents, $quality])
                ->flatten(1)
                ->filter(fn ($item) => (bool) ($item['risk'] ?? false))
                ->count();

            return [
                'date' => now()->isoFormat('D MMMM YYYY'),
                'total_events' => $stages->count() + $tasks->count() + $equipment->count() + $subcontractors->count() + $documents->count() + $quality->count(),
                'risk_events' => $riskCount,
                'stages' => $stages,
                'tasks' => $tasks,
                'equipment' => $equipment,
                'subcontractors' => $subcontractors,
                'documents' => $documents,
                'quality_checks' => $quality,
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
            ->where('tenant_id', 1)
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

Route::middleware('auth')->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('onboarding/step-1', [OnboardingController::class, 'storeStep1'])->name('onboarding.step1');
    Route::post('onboarding/step-2', [OnboardingController::class, 'storeStep2'])->name('onboarding.step2');
    Route::post('onboarding/step-3', [OnboardingController::class, 'storeStep3'])->name('onboarding.step3');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(EnsureOnboardingCompleted::class)->group(function () {
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('pilot-invites', [PilotInviteController::class, 'index'])->name('pilot-invites.index');
        Route::post('pilot-invites', [PilotInviteController::class, 'store'])->name('pilot-invites.store');
        Route::patch('pilot-invites/{pilotInvite}/status', [PilotInviteController::class, 'updateStatus'])->name('pilot-invites.status');

        Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
        Route::patch('billing', [BillingController::class, 'update'])->name('billing.update');

        // Proiecte & Clienti
        Route::resource('projects', ProjectController::class);
        Route::get('wbs', [WbsController::class, 'index'])->name('wbs.index');
        Route::patch('wbs/phases/{phase}', [WbsController::class, 'updatePhase'])->name('wbs.phases.update');
        Route::resource('clients', ClientController::class);
        Route::get('gantt', [GanttController::class, 'index'])->middleware('plan:gantt')->name('gantt.index');
        Route::resource('tasks', TaskController::class)->except('show');
        Route::resource('teams', TeamController::class);
        Route::get('team-calendar', [TeamCalendarController::class, 'index'])->name('team-calendar.index');
        Route::get('equipment-calendar', [EquipmentCalendarController::class, 'index'])->name('equipment-calendar.index');
        Route::resource('contractors', ContractorController::class)->except('show');
        Route::resource('equipment', EquipmentController::class)->except('show');
        Route::resource('documents', DocumentController::class)->except('show');
        Route::resource('stage-reports', StageReportController::class)->except('show');
        Route::get('situatii-de-lucrari', [StageReportController::class, 'index'])->name('situatii-lucrari.index');
        Route::resource('stage-tasks', StageTaskController::class)->except('show');
        Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
        Route::get('procese-verbale', [DocumentController::class, 'index'])->name('procese-verbale.index');
        Route::get('documente-subcontractori', [ContractorController::class, 'subcontractors'])->name('documente-subcontractori.index');
        Route::get('cost-tracking', [CostTrackingController::class, 'index'])->name('cost-tracking.index');
        Route::get('stage-progress', [StageProgressController::class, 'index'])->name('stage-progress.index');
        Route::resource('defects', DefectController::class)->except('show');
        Route::resource('quality-checks', QualityCheckController::class)->except('show');
        Route::get('rapoarte-calitate', [QualityCheckController::class, 'index'])->name('rapoarte-calitate.index');
        Route::resource('materials', MaterialController::class)->except('show');
        Route::resource('quotes', QuoteController::class)->except('show');
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
        Route::patch('quotes/{quote}/accept', [QuoteController::class, 'accept'])->name('quotes.accept');
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
        Route::delete('projects/{project}/phases/{phase}', [ProjectPhaseController::class, 'destroy'])->name('phases.destroy');
        Route::patch('projects/{project}/phases/{phase}/progress', [ProjectPhaseController::class, 'updateProgress'])->name('phases.progress');
        Route::post('projects/{project}/phases/{phase}/assignments', [PhaseTeamAssignmentController::class, 'store'])->name('phase-assignments.store');
        Route::delete('projects/{project}/phases/{phase}/assignments/{assignment}', [PhaseTeamAssignmentController::class, 'destroy'])->name('phase-assignments.destroy');
        Route::post('projects/{project}/phases/{phase}/equipment', [StageEquipmentController::class, 'store'])->name('stage-equipment.store');
        Route::delete('projects/{project}/phases/{phase}/equipment/{reservation}', [StageEquipmentController::class, 'destroy'])->name('stage-equipment.destroy');
        Route::post('projects/{project}/ai/invoice/extract', [ProjectAiToolsController::class, 'extractInvoice'])->name('projects.ai.invoice.extract');
        Route::post('projects/{project}/ai/invoice/commit', [ProjectAiToolsController::class, 'commitInvoice'])->name('projects.ai.invoice.commit');
        Route::post('projects/{project}/ai/budget-alert', [ProjectAiToolsController::class, 'budgetAlert'])->name('projects.ai.budget-alert');
        Route::post('projects/{project}/ai/estimate/generate', [ProjectAiToolsController::class, 'generateEstimate'])->name('projects.ai.estimate.generate');
        Route::post('projects/{project}/ai/estimate/commit', [ProjectAiToolsController::class, 'commitEstimate'])->name('projects.ai.estimate.commit');
    });
});

require __DIR__.'/auth.php';
