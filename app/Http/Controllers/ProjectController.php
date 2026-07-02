<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use App\Models\QualityCheck;
use App\Models\StageTask;
use App\Models\Team;
use App\Http\Requests\StoreProjectRequest;
use App\Support\AnalyticsTracker;
use App\Support\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $projects = Project::with('client')
            ->where('tenant_id', 1)
            ->latest()
            ->paginate(15);

        return Inertia::render('Projects/Index', ['projects' => $projects]);
    }

    public function create(): Response
    {
        $clients = Client::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'type']);
        return Inertia::render('Projects/Create', ['clients' => $clients]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        if (!PricingPlan::canCreateProject($request->user())) {
            return back()
                ->withInput()
                ->with('error', PricingPlan::projectLimitMessage($request->user()));
        }

        $data = $request->validated();
        $data['tenant_id'] = 1;
        $data['created_by'] = $request->user()->id;
        $project = Project::create($data);

        $userProjectCount = Project::query()
            ->where('tenant_id', 1)
            ->where('created_by', $request->user()->id)
            ->count();

        if ($userProjectCount === 1) {
            AnalyticsTracker::track($request, 'first_project_created', [
                'project_id' => $project->id,
            ], oncePerUser: true);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Proiect creat cu succes!');
    }

    public function show(Request $request, Project $project): Response
    {
        $window = $request->string('calendar_window')->toString();
        if (!in_array($window, ['today', '7d', '30d'], true)) {
            $window = 'today';
        }

        $windowStart = now()->startOfDay();
        $windowEnd = match ($window) {
            '7d' => now()->copy()->addDays(6)->endOfDay(),
            '30d' => now()->copy()->addDays(29)->endOfDay(),
            default => now()->copy()->endOfDay(),
        };

        $windowStartDate = $windowStart->toDateString();
        $windowEndDate = $windowEnd->toDateString();

        $project->load([
            'client',
            'creator',
            'phases.assignments.team',
            'phases.contractor',
            'phases.equipmentReservations.equipment',
            'tasks.assignee',
            'tasks.phase',
            'defects.assignee',
            'defects.phase',
        ]);

        $todayStages = $project->phases
            ->filter(function (ProjectPhase $phase) use ($windowStartDate, $windowEndDate) {
                $start = optional($phase->start_date)->format('Y-m-d');
                $end = optional($phase->end_date)->format('Y-m-d');

                return ($start && $start <= $windowEndDate && (!$end || $end >= $windowStartDate))
                    || ($start && $start >= $windowStartDate && $start <= $windowEndDate)
                    || ($end && $end >= $windowStartDate && $end <= $windowEndDate);
            })
            ->take(4)
            ->map(fn (ProjectPhase $phase) => [
                'id' => $phase->id,
                'title' => $phase->name,
                'status' => $phase->status,
                'criticality' => in_array($phase->status, ['blocked', 'delayed'], true) ? 'high' : 'normal',
                'url' => route('projects.show', ['project' => $project->id, 'calendar_window' => $window]) . '#phase-' . $phase->id,
            ])
            ->values();

        $todayStageTasks = StageTask::query()
            ->whereHas('stage', fn ($query) => $query->where('project_id', $project->id))
            ->whereBetween('deadline', [$windowStart, $windowEnd])
            ->whereIn('status', ['todo', 'in_progress', 'blocked'])
            ->orderByRaw("CASE WHEN status = 'blocked' THEN 1 WHEN status = 'in_progress' THEN 2 ELSE 3 END")
            ->take(4)
            ->get(['id', 'stage_id', 'title', 'status', 'deadline'])
            ->map(fn (StageTask $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'deadline' => optional($task->deadline)->format('H:i'),
                'criticality' => $task->status === 'blocked' ? 'high' : ($task->status === 'in_progress' ? 'medium' : 'normal'),
                'url' => route('stage-tasks.edit', ['stage_task' => $task->id]),
            ])
            ->values();

        $todayEquipment = $project->phases
            ->flatMap(fn (ProjectPhase $phase) => $phase->equipmentReservations)
            ->filter(function ($reservation) use ($windowStartDate, $windowEndDate) {
                $start = optional($reservation->usage_start)->format('Y-m-d');
                $end = optional($reservation->usage_end)->format('Y-m-d');

                return ($start && $start <= $windowEndDate && (!$end || $end >= $windowStartDate))
                    || ($start && $start >= $windowStartDate && $start <= $windowEndDate)
                    || ($end && $end >= $windowStartDate && $end <= $windowEndDate);
            })
            ->take(4)
            ->map(fn ($reservation) => [
                'id' => $reservation->id,
                'title' => $reservation->equipment?->name,
                'window' => trim((optional($reservation->usage_start)->format('d.m') ?? '-') . ' - ' . (optional($reservation->usage_end)->format('d.m') ?? '-')),
                'criticality' => ($reservation->equipment?->availability_status !== 'available') ? 'high' : 'normal',
                'url' => route('equipment-calendar.index', [
                    'equipment_id' => $reservation->equipment_id,
                    'start_date' => $windowStartDate,
                    'end_date' => $windowEndDate,
                ]),
            ])
            ->values();

        $todaySubcontractors = $project->phases
            ->filter(fn (ProjectPhase $phase) => in_array($phase->contractor?->type, [Contractor::TYPE_SUBCONTRACTOR, Contractor::TYPE_PFA], true))
            ->filter(function (ProjectPhase $phase) use ($windowStartDate, $windowEndDate) {
                $start = optional($phase->start_date)->format('Y-m-d');
                $end = optional($phase->end_date)->format('Y-m-d');

                return ($start && $start <= $windowEndDate && (!$end || $end >= $windowStartDate))
                    || ($start && $start >= $windowStartDate && $start <= $windowEndDate)
                    || ($end && $end >= $windowStartDate && $end <= $windowEndDate);
            })
            ->take(4)
            ->map(fn (ProjectPhase $phase) => [
                'id' => $phase->id,
                'title' => $phase->contractor?->name,
                'stage' => $phase->name,
                'contractor_id' => $phase->contractor_id,
            ])
            ->values();

        $subcontractorLoadMap = ProjectPhase::query()
            ->whereIn('contractor_id', $todaySubcontractors->pluck('contractor_id')->filter()->unique()->values())
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

        $todaySubcontractors = $todaySubcontractors
            ->map(function (array $item) use ($subcontractorLoadMap) {
                $load = (int) ($subcontractorLoadMap[$item['contractor_id']] ?? 0);

                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'stage' => $item['stage'],
                    'active_assignments' => $load,
                    'criticality' => $load >= 3 ? 'high' : ($load === 2 ? 'medium' : 'normal'),
                    'url' => $item['contractor_id'] ? route('contractors.edit', ['contractor' => $item['contractor_id']]) : route('contractors.index'),
                ];
            })
            ->values();

        $todayDocuments = Document::query()
            ->where('project_id', $project->id)
            ->whereBetween('issued_at', [$windowStart, $windowEnd])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->orderByDesc('amount')
            ->take(4)
            ->get(['id', 'title', 'amount', 'payment_status'])
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $document->title,
                'amount' => (float) $document->amount,
                'status' => $document->payment_status,
                'criticality' => in_array($document->payment_status, ['unpaid', 'partial'], true) ? 'medium' : 'normal',
                'url' => route('documents.edit', ['document' => $document->id]),
            ])
            ->values();

        $todayQuality = QualityCheck::query()
            ->where('project_id', $project->id)
            ->whereBetween('planned_at', [$windowStart, $windowEnd])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderBy('planned_at')
            ->take(4)
            ->get(['id', 'title', 'status', 'planned_at'])
            ->map(fn (QualityCheck $check) => [
                'id' => $check->id,
                'title' => $check->title,
                'status' => $check->status,
                'time' => optional($check->planned_at)->format('H:i'),
                'criticality' => $check->status === 'in_progress' ? 'medium' : 'normal',
                'url' => route('quality-checks.edit', ['quality_check' => $check->id]),
            ])
            ->values();

        $riskyStages = $todayStages->where('criticality', 'high')->count();
        $blockedTasks = $todayStageTasks->where('status', 'blocked')->count();
        $unavailableEquipment = $todayEquipment->where('criticality', 'high')->count();
        $overloadedSubcontractors = $todaySubcontractors->where('criticality', 'high')->count();
        $unpaidDocuments = $todayDocuments->whereIn('status', ['unpaid', 'partial'])->count();

        $stageRiskRate = $todayStages->count() > 0 ? $riskyStages / $todayStages->count() : 0;
        $taskRiskRate = $todayStageTasks->count() > 0 ? $blockedTasks / $todayStageTasks->count() : 0;
        $equipmentRiskRate = $todayEquipment->count() > 0 ? $unavailableEquipment / $todayEquipment->count() : 0;
        $subcontractorRiskRate = $todaySubcontractors->count() > 0 ? $overloadedSubcontractors / $todaySubcontractors->count() : 0;
        $documentRiskRate = $todayDocuments->count() > 0 ? $unpaidDocuments / $todayDocuments->count() : 0;

        $riskScore = (int) round((
            ($stageRiskRate * 0.28)
            + ($taskRiskRate * 0.24)
            + ($equipmentRiskRate * 0.18)
            + ($subcontractorRiskRate * 0.14)
            + ($documentRiskRate * 0.16)
        ) * 100);

        $riskLevel = $riskScore >= 60 ? 'high' : ($riskScore >= 35 ? 'medium' : 'low');

        return Inertia::render('Projects/Show', [
            'project'    => $project,
            'typeLabels' => ProjectPhase::$typeLabels,
            'teams' => Team::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'type']),
            'equipment' => Equipment::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'cost_per_hour', 'availability_status']),
            'todayCalendar' => [
                'date' => $windowStart->isoFormat('D MMMM YYYY') . ($window === 'today' ? '' : ' - ' . $windowEnd->isoFormat('D MMMM YYYY')),
                'window' => $window,
                'stages' => $todayStages,
                'tasks' => $todayStageTasks,
                'equipment' => $todayEquipment,
                'subcontractors' => $todaySubcontractors,
                'documents' => $todayDocuments,
                'quality_checks' => $todayQuality,
                'risk' => [
                    'score' => $riskScore,
                    'level' => $riskLevel,
                    'blocked_tasks' => $blockedTasks,
                    'risky_stages' => $riskyStages,
                    'unavailable_equipment' => $unavailableEquipment,
                    'overloaded_subcontractors' => $overloadedSubcontractors,
                    'unpaid_documents' => $unpaidDocuments,
                ],
            ],
        ]);
    }

    public function edit(Project $project): Response
    {
        $clients = Client::where('tenant_id', 1)->where('active', true)->orderBy('name')->get(['id', 'name', 'type']);
        return Inertia::render('Projects/Edit', ['project' => $project, 'clients' => $clients]);
    }

    public function update(StoreProjectRequest $request, Project $project): RedirectResponse
    {
        $previousStatus = (string) $project->status;
        $project->update($request->validated());

        if ($previousStatus !== 'completed' && $project->status === 'completed') {
            $referenceQuote = Quote::query()
                ->where('project_id', $project->id)
                ->whereIn('status', ['accepted', 'sent'])
                ->latest('version')
                ->with(['project:id,name,status', 'items'])
                ->first();

            if ($referenceQuote) {
                QuoteTemplate::upsertFromQuote($referenceQuote, $request->user()->id);
            }
        }

        return redirect()->route('projects.show', $project)->with('success', 'Proiect actualizat!');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Proiect sters!');
    }
}