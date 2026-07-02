<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Contractor;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectPhase;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\DemoScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Document::class, 'document');
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = [
            'q' => $request->string('q')->toString(),
            'type' => $request->string('type')->toString(),
            'payment_status' => $request->string('payment_status')->toString(),
            'project_id' => $request->integer('project_id') > 0 ? $request->integer('project_id') : null,
            'stage_id' => $request->integer('stage_id') > 0 ? $request->integer('stage_id') : null,
            'contractor_id' => $request->integer('contractor_id') > 0 ? $request->integer('contractor_id') : null,
        ];

        $documents = Document::query()
            ->where('tenant_id', 1)
            ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
            ->with(['project:id,name', 'stage:id,name', 'contractor:id,name'])
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $query->where(function ($inner) use ($filters) {
                    $inner->where('title', 'like', '%' . $filters['q'] . '%')
                        ->orWhere('notes', 'like', '%' . $filters['q'] . '%');
                });
            })
            ->when($filters['type'] !== '', fn ($query) => $query->where('type', $filters['type']))
            ->when($filters['payment_status'] !== '', fn ($query) => $query->where('payment_status', $filters['payment_status']))
            ->when($filters['project_id'], fn ($query, $value) => $query->where('project_id', $value))
            ->when($filters['stage_id'], fn ($query, $value) => $query->where('stage_id', $value))
            ->when($filters['contractor_id'], fn ($query, $value) => $query->where('contractor_id', $value))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $baseSummary = Document::query()
            ->where('tenant_id', 1)
            ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user));
        $baseSummary
            ->when($filters['project_id'], fn ($query, $value) => $query->where('project_id', $value))
            ->when($filters['contractor_id'], fn ($query, $value) => $query->where('contractor_id', $value));

        $summaryByStage = (clone $baseSummary)
            ->when($filters['stage_id'], fn ($query, $value) => $query->where('stage_id', $value))
            ->selectRaw('stage_id, SUM(amount) as total_amount, COUNT(*) as documents_count')
            ->groupBy('stage_id')
            ->with('stage:id,name')
            ->get()
            ->map(fn ($row) => [
                'stage_id' => $row->stage_id,
                'stage_name' => $row->stage?->name ?? 'Fara etapa',
                'documents_count' => (int) $row->documents_count,
                'total_amount' => (float) $row->total_amount,
            ])
            ->values();

        $insightsQuery = (clone $baseSummary)
            ->when($filters['stage_id'], fn ($query, $value) => $query->where('stage_id', $value));

        $financialInsights = [
            'paid_count' => (clone $insightsQuery)->where('payment_status', 'paid')->count(),
            'partial_count' => (clone $insightsQuery)->where('payment_status', 'partial')->count(),
            'unpaid_count' => (clone $insightsQuery)->where('payment_status', 'unpaid')->count(),
            'total_unpaid_amount' => (float) (clone $insightsQuery)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->sum('amount'),
            'overdue_invoices_count' => (clone $insightsQuery)
                ->where('type', 'invoice')
                ->where('payment_status', 'unpaid')
                ->whereDate('issued_at', '<=', now()->subDays(30)->toDateString())
                ->count(),
        ];

        $totalsByContractor = (clone $insightsQuery)
            ->selectRaw('contractor_id, SUM(amount) as total_amount, COUNT(*) as documents_count')
            ->groupBy('contractor_id')
            ->with('contractor:id,name')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'contractor_id' => $row->contractor_id,
                'contractor_name' => $row->contractor?->name ?? 'Fara contractor',
                'documents_count' => (int) $row->documents_count,
                'total_amount' => (float) $row->total_amount,
            ])
            ->values();

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $filters,
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
                ->orderBy('name')
                ->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('phases.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => Document::$typeLabels,
            'paymentStatuses' => Document::$paymentStatusLabels,
            'summaryByStage' => $summaryByStage,
            'financialInsights' => $financialInsights,
            'totalsByContractor' => $totalsByContractor,
        ]);
    }

    public function create(): Response
    {
        $user = request()->user();

        return Inertia::render('Documents/Create', [
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
                ->orderBy('name')
                ->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('phases.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => Document::$typeLabels,
            'paymentStatuses' => Document::$paymentStatusLabels,
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            ...$validated,
            'tenant_id' => 1,
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $payload['file_path'] = $file->store('documents', 'local');
            $payload['file_name'] = $file->getClientOriginalName();
            $payload['mime_type'] = $file->getClientMimeType();
            $payload['file_size'] = $file->getSize();
        }

        Document::create($payload);

        return redirect()->route('documents.index')->with('success', 'Document adaugat cu succes!');
    }

    public function edit(Document $document): Response
    {
        $user = request()->user();

        return Inertia::render('Documents/Edit', [
            'document' => $document,
            'projects' => DemoScope::applyProjectScope(Project::query(), $user)->orderBy('name')->get(['id', 'name']),
            'stages' => ProjectPhase::query()
                ->whereHas('project', fn ($query) => DemoScope::applyProjectScope($query, $user))
                ->orderBy('name')
                ->get(['id', 'name']),
            'contractors' => Contractor::where('tenant_id', 1)
                ->when(DemoScope::isDemoUser($user), fn ($query) => $query->whereHas('phases.project', fn ($projectQuery) => DemoScope::applyProjectScope($projectQuery, $user)))
                ->orderBy('name')
                ->get(['id', 'name']),
            'types' => Document::$typeLabels,
            'paymentStatuses' => Document::$paymentStatusLabels,
        ]);
    }

    public function update(StoreDocumentRequest $request, Document $document): RedirectResponse
    {
        $validated = $request->validated();

        $payload = [
            ...$validated,
        ];

        if ($request->hasFile('attachment')) {
            if ($document->file_path) {
                Storage::disk('local')->delete($document->file_path);
            }

            $file = $request->file('attachment');
            $payload['file_path'] = $file->store('documents', 'local');
            $payload['file_name'] = $file->getClientOriginalName();
            $payload['mime_type'] = $file->getClientMimeType();
            $payload['file_size'] = $file->getSize();
        }

        $document->update($payload);

        return redirect()->route('documents.index')->with('success', 'Document actualizat!');
    }

    public function destroy(Document $document): RedirectResponse
    {
        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document sters!');
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document);

        if (!$document->file_path || !Storage::disk('local')->exists($document->file_path)) {
            return back()->with('error', 'Fisierul nu mai exista pe storage.');
        }

        return response()->download(
            storage_path('app/private/' . $document->file_path),
            $document->file_name ?: basename($document->file_path)
        );
    }

    public function pdf(Document $document): HttpResponse
    {
        $this->authorize('view', $document);

        $document->loadMissing([
            'project:id,name,client_id,address,start_date,end_date',
            'project.client:id,name',
            'stage:id,name,start_date,end_date',
            'contractor:id,name',
        ]);
        $branding = AppSetting::allWithDefaults(config('platform.defaults', []));

        $fileName = sprintf('%s-%d.pdf', str($document->title)->slug('-'), $document->id);

        $pdf = Pdf::loadView('documents.pdf', [
            'document' => $document,
            'branding' => $branding,
        ])->setPaper('a4')->setOptions([
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download($fileName);
    }
}
