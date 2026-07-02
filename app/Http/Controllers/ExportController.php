<?php

namespace App\Http\Controllers;

use App\Exports\EnterpriseWorkbookExport;
use App\Models\AppSetting;
use App\Jobs\RunExportSubscriptionJob;
use App\Models\ExportLog;
use App\Models\ExportSubscription;
use App\Models\Defect;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Support\DemoScope;
use App\Support\ExportAudit;
use App\Support\ExportDatasetBuilder;
use App\Support\ExportFilter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        $branding = AppSetting::allWithDefaults(config('platform.defaults', []));

        return Inertia::render('Exports/Index', [
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
            'teams' => Team::where('tenant_id', 1)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->where('leader_id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'users' => User::query()
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->where('id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'subscriptions' => ExportSubscription::query()
                ->where('tenant_id', 1)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->where('created_by', $user->id))
                ->orderByDesc('id')
                ->get(['id', 'name', 'export_type', 'format', 'frequency', 'schedule_time', 'schedule_weekday', 'active', 'next_run_at']),
            'recentLogs' => ExportLog::query()
                ->where('tenant_id', 1)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->where('user_id', $user->id))
                ->latest('id')
                ->take(20)
                ->get(['id', 'export_type', 'format', 'status', 'file_name', 'delivery_channel', 'delivery_target', 'created_at']),
            'branding' => [
                'company_name' => $branding['company_name'] ?? config('exports.company_name'),
                'company_email' => $branding['support_email'] ?? config('exports.company_email'),
                'company_phone' => $branding['company_phone'] ?? config('exports.company_phone'),
                'company_address' => $branding['company_address'] ?? '',
                'document_logo_url' => $branding['document_logo_url'] ?? '',
                'brand_color' => $branding['document_brand_color'] ?? config('exports.brand_color'),
            ],
        ]);
    }

    public function projectsCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('projects', $filters);
        $projects = $dataset['rows'];

        ExportAudit::log('projects', 'csv', $filters, [
            'file_name' => 'proiecte.csv',
        ]);

        return $this->downloadCsv(
            'proiecte.csv',
            ['ID', 'Nume', 'Client', 'Status', 'Data start', 'Data end', 'Buget total', 'Adresa'],
            $projects->map(fn ($project) => [
                $project->id,
                $project->name,
                $project->client?->name ?? '',
                $project->status,
                optional($project->start_date)->format('Y-m-d') ?? '',
                optional($project->end_date)->format('Y-m-d') ?? '',
                $project->total_budget,
                $project->address,
            ])->all()
        );
    }

    public function quotesCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('quotes', $filters);
        $quotes = $dataset['rows'];

        ExportAudit::log('quotes', 'csv', $filters, [
            'file_name' => 'oferte_devize.csv',
        ]);

        return $this->downloadCsv(
            'oferte_devize.csv',
            ['ID', 'Proiect', 'Versiune', 'Titlu', 'Status', 'Valabil pana la', 'Net', 'TVA', 'Brut'],
            $quotes->map(fn ($quote) => [
                $quote->id,
                $quote->project?->name ?? '',
                $quote->version,
                $quote->title,
                $quote->status,
                optional($quote->valid_until)->format('Y-m-d') ?? '',
                $quote->total_net,
                $quote->total_tva,
                $quote->total_gross,
            ])->all()
        );
    }

    public function materialsCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('materials', $filters);
        $materials = $dataset['rows'];

        ExportAudit::log('materials', 'csv', $filters, [
            'file_name' => 'materiale.csv',
        ]);

        return $this->downloadCsv(
            'materiale.csv',
            ['ID', 'Cod', 'Nume', 'Categorie', 'UM', 'Pret unitar', 'Furnizor', 'Activ'],
            $materials->map(fn ($material) => [
                $material->id,
                $material->code,
                $material->name,
                $material->category,
                $material->unit,
                $material->unit_price,
                $material->supplier,
                $material->active ? 'Da' : 'Nu',
            ])->all()
        );
    }

    public function costsCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('costs', $filters);
        $projects = $dataset['rows'];

        ExportAudit::log('costs', 'csv', $filters, [
            'file_name' => 'costuri_proiecte.csv',
        ]);

        return $this->downloadCsv(
            'costuri_proiecte.csv',
            [
                'Project ID',
                'Project',
                'Buget proiect',
                'Nr oferte',
                'Total net oferte',
                'Total TVA oferte',
                'Total brut oferte',
                'Total brut acceptat',
                'Diferenta vs buget',
            ],
            $projects->map(fn ($row) => [
                $row['project_id'] ?? '',
                $row['project_name'] ?? '',
                $row['budget'] ?? '',
                $row['quotes_count'] ?? 0,
                $row['total_net'] ?? 0,
                $row['total_tva'] ?? 0,
                $row['total_gross'] ?? 0,
                $row['accepted_total_gross'] ?? 0,
                $row['diff_vs_budget'] ?? '',
            ])->all()
        );
    }

    public function teamsResponsibilitiesCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('teams', $filters);
        $rows = $dataset['rows'];

        ExportAudit::log('teams', 'csv', $filters, [
            'file_name' => 'echipe_responsabilitati.csv',
        ]);

        return $this->downloadCsv(
            'echipe_responsabilitati.csv',
            [
                'Team ID',
                'Echipa',
                'Lider',
                'Membru',
                'Rol membru',
                'Specialitate',
                'Activa',
                'Nr alocari',
                'Proiecte alocate',
            ],
            $rows->map(fn ($row) => [
                $row['team_id'] ?? '',
                $row['team_name'] ?? '',
                $row['leader'] ?? '',
                $row['member'] ?? '',
                $row['member_role'] ?? '',
                $row['specialty'] ?? '',
                ($row['active'] ?? false) ? 'Da' : 'Nu',
                $row['assignments_count'] ?? 0,
                $row['projects'] ?? '',
            ])->all()
        );
    }

    public function tasksCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('tasks', $filters);
        $tasks = $dataset['rows'];

        ExportAudit::log('tasks', 'csv', $filters, [
            'file_name' => 'taskuri.csv',
        ]);

        return $this->downloadCsv(
            'taskuri.csv',
            ['ID', 'Proiect', 'Etapa', 'Titlu', 'Status', 'Prioritate', 'Responsabil', 'Deadline'],
            $tasks->map(fn ($task) => [
                $task->id,
                $task->project?->name ?? '',
                $task->phase?->name ?? '',
                $task->title,
                $task->status,
                $task->priority,
                $task->assignee?->name ?? '',
                optional($task->deadline)->format('Y-m-d') ?? '',
            ])->all()
        );
    }

    public function defectsCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('defects', $filters);
        $defects = $dataset['rows'];

        ExportAudit::log('defects', 'csv', $filters, [
            'file_name' => 'defecte_snag.csv',
        ]);

        return $this->downloadCsv(
            'defecte_snag.csv',
            ['ID', 'Proiect', 'Etapa', 'Titlu', 'Status', 'Prioritate', 'Responsabil', 'Locatie', 'Due date'],
            $defects->map(fn ($defect) => [
                $defect->id,
                $defect->project?->name ?? '',
                $defect->phase?->name ?? '',
                $defect->title,
                $defect->status,
                $defect->priority,
                $defect->assignee?->name ?? '',
                $defect->location,
                optional($defect->due_date)->format('Y-m-d') ?? '',
            ])->all()
        );
    }

    public function wbsCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('wbs', $filters);
        $rows = $dataset['rows'];

        ExportAudit::log('wbs', 'csv', $filters, [
            'file_name' => 'wbs_etape.csv',
        ]);

        return $this->downloadCsv(
            'wbs_etape.csv',
            ['ID', 'Proiect', 'Etapa', 'Nivel WBS', 'Path WBS', 'Parinte', 'Status', 'Progres %', 'Contractor', 'Start', 'End'],
            $rows->map(fn ($row) => [
                $row['id'] ?? '',
                $row['project'] ?? '',
                $row['name'] ?? '',
                $row['level'] ?? '',
                $row['wbs_path'] ?? '',
                $row['parent'] ?? '',
                $row['status'] ?? '',
                $row['progress_pct'] ?? '',
                $row['contractor'] ?? '',
                $row['start_date'] ?? '',
                $row['end_date'] ?? '',
            ])->all()
        );
    }

    public function equipmentCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('equipment', $filters);
        $rows = $dataset['rows'];

        ExportAudit::log('equipment', 'csv', $filters, [
            'file_name' => 'utilaje_rezervari.csv',
        ]);

        return $this->downloadCsv(
            'utilaje_rezervari.csv',
            ['Rezervare ID', 'Proiect', 'Etapa', 'Status etapa', 'Utilaj', 'Tip utilaj', 'Furnizor', 'Disponibilitate', 'Cantitate', 'Start', 'End', 'Zile', 'Cost/ora', 'Cost estimat'],
            $rows->map(fn ($row) => [
                $row['reservation_id'] ?? '',
                $row['project'] ?? '',
                $row['phase'] ?? '',
                $row['phase_status'] ?? '',
                $row['equipment'] ?? '',
                $row['equipment_type'] ?? '',
                $row['supplier'] ?? '',
                $row['availability_status'] ?? '',
                $row['quantity'] ?? '',
                $row['usage_start'] ?? '',
                $row['usage_end'] ?? '',
                $row['days'] ?? '',
                $row['hourly_cost'] ?? '',
                $row['estimated_cost'] ?? '',
            ])->all()
        );
    }

    public function documentsCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('documents', $filters);
        $rows = $dataset['rows'];

        ExportAudit::log('documents', 'csv', $filters, [
            'file_name' => 'documente_financiare.csv',
        ]);

        return $this->downloadCsv(
            'documente_financiare.csv',
            ['ID', 'Titlu', 'Tip', 'Proiect', 'Etapa', 'Contractor', 'Status plata', 'Suma', 'Data emitere', 'Fisier'],
            $rows->map(fn ($row) => [
                $row->id,
                $row->title,
                $row->type,
                $row->project?->name ?? '',
                $row->stage?->name ?? '',
                $row->contractor?->name ?? '',
                $row->payment_status,
                $row->amount,
                optional($row->issued_at)->format('Y-m-d') ?? '',
                $row->file_name,
            ])->all()
        );
    }

    public function stageReportsCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('stage-reports', $filters);
        $rows = $dataset['rows'];

        ExportAudit::log('stage-reports', 'csv', $filters, [
            'file_name' => 'rapoarte_etapa.csv',
        ]);

        return $this->downloadCsv(
            'rapoarte_etapa.csv',
            ['ID', 'Proiect', 'Etapa', 'Contractor', 'Raportat de', 'Data raport', 'Progres %', 'Activitati', 'Probleme'],
            $rows->map(fn ($row) => [
                $row->id,
                $row->stage?->project?->name ?? '',
                $row->stage?->name ?? '',
                $row->contractor?->name ?? '',
                $row->creator?->name ?? '',
                optional($row->report_date)->format('Y-m-d') ?? '',
                $row->progress_pct,
                $row->activities,
                $row->issues,
            ])->all()
        );
    }

    public function stageTasksCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('stage-tasks', $filters);
        $rows = $dataset['rows'];

        ExportAudit::log('stage-tasks', 'csv', $filters, [
            'file_name' => 'taskuri_etapa.csv',
        ]);

        return $this->downloadCsv(
            'taskuri_etapa.csv',
            ['ID', 'Proiect', 'Etapa', 'Titlu', 'Status', 'Tip responsabil', 'Responsabil', 'Deadline'],
            $rows->map(function ($row) {
                $assignee = match ($row->assignee_type) {
                    'user' => $row->userAssignee?->name,
                    'team' => $row->teamAssignee?->name,
                    'contractor' => $row->contractorAssignee?->name,
                    default => '',
                };

                return [
                    $row->id,
                    $row->stage?->project?->name ?? '',
                    $row->stage?->name ?? '',
                    $row->title,
                    $row->status,
                    $row->assignee_type,
                    $assignee,
                    optional($row->deadline)->format('Y-m-d H:i') ?? '',
                ];
            })->all()
        );
    }

    public function stageProgressCsv(Request $request): StreamedResponse
    {
        $filters = ExportFilter::fromRequest($request);
        $dataset = ExportDatasetBuilder::build('stage-progress', $filters);
        $rows = $dataset['rows'];

        ExportAudit::log('stage-progress', 'csv', $filters, [
            'file_name' => 'progres_etape.csv',
        ]);

        return $this->downloadCsv(
            'progres_etape.csv',
            ['ID etapa', 'Proiect', 'Etapa', 'Parinte', 'Contractor', 'Status', 'Progres %', 'Start', 'End', 'Ordine'],
            $rows->map(fn ($row) => [
                $row['phase_id'] ?? '',
                $row['project'] ?? '',
                $row['phase'] ?? '',
                $row['parent'] ?? '',
                $row['contractor'] ?? '',
                $row['status'] ?? '',
                $row['progress_pct'] ?? '',
                $row['start_date'] ?? '',
                $row['end_date'] ?? '',
                $row['order'] ?? '',
            ])->all()
        );
    }

    public function workbook(Request $request)
    {
        $filters = ExportFilter::fromRequest($request);
        $types = ExportFilter::csvToArray($request->string('types')->toString());
        $branding = AppSetting::allWithDefaults(config('platform.defaults', []));

        if (empty($types)) {
            $types = ['projects', 'quotes', 'materials', 'costs', 'teams', 'tasks', 'defects', 'wbs', 'equipment', 'documents', 'stage-reports', 'stage-tasks', 'stage-progress'];
        }

        $fileName = 'enterprise_export_' . now()->format('Ymd_His') . '.xlsx';

        ExportAudit::log('enterprise-workbook', 'xlsx', $filters, [
            'file_name' => $fileName,
        ]);

        return Excel::download(new EnterpriseWorkbookExport($types, $filters, [
            'company_name' => $branding['company_name'] ?? config('exports.company_name'),
            'company_email' => $branding['support_email'] ?? config('exports.company_email'),
            'company_phone' => $branding['company_phone'] ?? config('exports.company_phone'),
            'company_address' => $branding['company_address'] ?? '',
            'document_logo_url' => $branding['document_logo_url'] ?? '',
            'brand_color' => $branding['document_brand_color'] ?? config('exports.brand_color'),
        ]), $fileName);
    }

    public function managerialPdf(Request $request)
    {
        $filters = ExportFilter::fromRequest($request);
        $types = ExportFilter::csvToArray($request->string('types')->toString());
        $branding = AppSetting::allWithDefaults(config('platform.defaults', []));

        if (empty($types)) {
            $types = ['wbs', 'equipment', 'documents', 'stage-reports', 'stage-tasks', 'stage-progress', 'costs', 'tasks', 'defects'];
        }

        $sections = collect($types)->map(function ($type) use ($filters) {
            $dataset = ExportDatasetBuilder::build($type, $filters);

            return [
                'name' => ucfirst($type),
                'rows' => $dataset['rows']->map(function ($row) {
                    return is_array($row)
                        ? $row
                        : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
                })->values(),
            ];
        })->all();

        $fileName = 'managerial_report_' . now()->format('Ymd_His') . '.pdf';

        ExportAudit::log('managerial-pdf', 'pdf', $filters, [
            'file_name' => $fileName,
        ]);

        $pdf = Pdf::loadView('exports.managerial-pdf', [
            'title' => 'Raport managerial Santier',
            'branding' => [
                'company_name' => $branding['company_name'] ?? config('exports.company_name'),
                'company_email' => $branding['support_email'] ?? config('exports.company_email'),
                'company_phone' => $branding['company_phone'] ?? config('exports.company_phone'),
                'company_address' => $branding['company_address'] ?? '',
                'document_logo_url' => $branding['document_logo_url'] ?? '',
                'brand_color' => $branding['document_brand_color'] ?? config('exports.brand_color'),
            ],
            'generatedAt' => now()->toDateTimeString(),
            'filters' => $filters,
            'sections' => $sections,
        ])->setOptions([
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download($fileName);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'export_type' => ['required', 'in:projects,quotes,materials,costs,teams,tasks,defects,wbs,equipment,documents,stage-reports,stage-tasks,stage-progress'],
            'format' => ['required', 'in:csv,xlsx,pdf'],
            'frequency' => ['required', 'in:daily,weekly'],
            'schedule_time' => ['required', 'date_format:H:i'],
            'schedule_weekday' => ['nullable', 'integer', 'between:0,6'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['email'],
            'filters' => ['nullable', 'array'],
        ]);

        $subscription = ExportSubscription::create([
            'tenant_id' => 1,
            'created_by' => $request->user()?->id,
            'name' => $validated['name'],
            'export_type' => $validated['export_type'],
            'format' => $validated['format'],
            'frequency' => $validated['frequency'],
            'schedule_time' => $validated['schedule_time'],
            'schedule_weekday' => $validated['schedule_weekday'] ?? null,
            'filters' => $validated['filters'] ?? [],
            'recipients' => $validated['recipients'],
            'active' => true,
            'next_run_at' => $this->computeNextRunAt($validated['frequency'], $validated['schedule_time'], $validated['schedule_weekday'] ?? null),
        ]);

        ExportAudit::log('subscription', 'system', $validated['filters'] ?? [], [
            'status' => 'success',
            'delivery_channel' => 'email',
            'delivery_target' => implode(',', $validated['recipients']),
            'notes' => 'Created export subscription #' . $subscription->id,
        ]);

        return back()->with('success', 'Abonare export creata cu succes!');
    }

    public function runSubscription(ExportSubscription $subscription)
    {
        RunExportSubscriptionJob::dispatch($subscription->id);

        return back()->with('success', 'Exportul programat a fost pus in coada.');
    }

    public function toggleSubscription(ExportSubscription $subscription)
    {
        $subscription->update([
            'active' => !$subscription->active,
        ]);

        return back()->with('success', 'Stare abonare actualizata.');
    }

    public function projectPackage(Project $project)
    {
        $project->load([
            'client',
            'phases.assignments.team',
            'tasks.assignee',
            'tasks.phase',
            'defects.assignee',
            'defects.phase',
            'quotes',
        ]);

        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'project' => $project,
            'summary' => [
                'phases_count' => $project->phases->count(),
                'tasks_count' => $project->tasks->count(),
                'open_tasks_count' => $project->tasks->whereIn('status', ['todo', 'in_progress'])->count(),
                'defects_count' => $project->defects->count(),
                'open_defects_count' => $project->defects->whereIn('status', ['open', 'in_progress'])->count(),
                'quotes_count' => $project->quotes->count(),
                'quotes_total_gross' => $project->quotes->sum('total_gross'),
            ],
        ];

        ExportAudit::log('project-package', 'json', ['project_id' => $project->id], [
            'file_name' => 'project_' . $project->id . '_package.json',
        ]);

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'project_' . $project->id . '_package.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    private function computeNextRunAt(string $frequency, string $time, ?int $weekday)
    {
        $now = now();

        if ($frequency === 'daily') {
            return $now->copy()->addDay()->setTimeFromTimeString($time);
        }

        $targetWeekday = $weekday ?? 1;
        return $now->copy()->next($targetWeekday)->setTimeFromTimeString($time);
    }

    private function downloadCsv(string $fileName, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
