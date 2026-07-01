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
use App\Http\Middleware\EnsureOnboardingCompleted;
use App\Support\AnalyticsTracker;
use App\Support\DemoScope;
use Illuminate\Http\Request;
use App\Models\Defect;
use App\Models\Document;
use App\Models\Quote;
use App\Models\Project;
use App\Models\ProjectPhase;
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
    });
});

require __DIR__.'/auth.php';
