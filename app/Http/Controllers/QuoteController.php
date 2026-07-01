<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\Project;
use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    private const AI_BREAKDOWN_MARKER = '[AI_BREAKDOWN_JSON]';

    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id');

        $quotes = Quote::query()
            ->with(['project:id,name', 'creator:id,name'])
            ->where('tenant_id', 1)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'projects' => Project::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'project_id' => $projectId > 0 ? $projectId : '',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Quotes/Create', [
            'projects' => Project::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
            'selectedProjectId' => $request->integer('project_id') ?: null,
        ]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = 1;
        $data['created_by'] = $request->user()->id;
        $data['discount_pct'] = $data['discount_pct'] ?? 0;
        $data['tva_pct'] = $data['tva_pct'] ?? 21;

        $projectLastVersion = Quote::where('project_id', $data['project_id'])->max('version') ?? 0;
        $data['version'] = $projectLastVersion + 1;

        $totals = $this->buildTotals((float) $data['total_net'], (float) $data['discount_pct'], (float) $data['tva_pct']);
        $data = array_merge($data, $totals);

        if ($data['status'] === 'sent') {
            $data['sent_at'] = now();
        }

        if ($data['status'] === 'accepted') {
            $data['accepted_at'] = now();
        }

        Quote::create($data);

        return redirect()->route('quotes.index')->with('success', 'Oferta creata cu succes!');
    }

    public function edit(Quote $quote): Response
    {
        return Inertia::render('Quotes/Edit', [
            'quote' => $quote,
            'projects' => Project::where('tenant_id', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(StoreQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $data = $request->validated();
        $data['discount_pct'] = $data['discount_pct'] ?? 0;
        $data['tva_pct'] = $data['tva_pct'] ?? 21;

        $totals = $this->buildTotals((float) $data['total_net'], (float) $data['discount_pct'], (float) $data['tva_pct']);
        $data = array_merge($data, $totals);

        if ($data['status'] === 'sent' && !$quote->sent_at) {
            $data['sent_at'] = now();
        }

        if ($data['status'] === 'accepted' && !$quote->accepted_at) {
            $data['accepted_at'] = now();
        }

        if (in_array($data['status'], ['draft', 'rejected'], true)) {
            $data['accepted_at'] = null;
        }

        $quote->update($data);

        return redirect()->route('quotes.index')->with('success', 'Oferta actualizata!');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Oferta stearsa!');
    }

    public function pdf(Quote $quote): HttpResponse
    {
        $quote->loadMissing(['project:id,name', 'creator:id,name']);

        [$displayNotes, $breakdown] = $this->extractBreakdownFromNotes((string) ($quote->notes ?? ''));

        $pdf = Pdf::loadView('quotes.pdf', [
            'quote' => $quote,
            'notes' => $displayNotes,
            'breakdown' => $breakdown,
        ])->setPaper('a4');

        return $pdf->download(sprintf('oferta-%d-v%d.pdf', $quote->id, $quote->version));
    }

    public function accept(Quote $quote): RedirectResponse
    {
        $quote->update([
            'status' => 'accepted',
            'sent_at' => $quote->sent_at ?: now(),
            'accepted_at' => now(),
        ]);

        return redirect()->route('quotes.index')->with('success', 'Oferta a fost marcata ca acceptata. Poti continua executia in proiect.');
    }

    private function buildTotals(float $totalNet, float $discountPct, float $tvaPct): array
    {
        $discountAmount = $totalNet * ($discountPct / 100);
        $discountedNet = max($totalNet - $discountAmount, 0);
        $totalTva = $discountedNet * ($tvaPct / 100);
        $totalGross = $discountedNet + $totalTva;

        return [
            'total_net' => round($discountedNet, 2),
            'total_tva' => round($totalTva, 2),
            'total_gross' => round($totalGross, 2),
        ];
    }

    private function extractBreakdownFromNotes(string $notes): array
    {
        if ($notes === '' || !str_contains($notes, self::AI_BREAKDOWN_MARKER)) {
            return [trim($notes), null];
        }

        $parts = explode(self::AI_BREAKDOWN_MARKER, $notes, 2);
        $plainNotes = trim($parts[0]);
        $jsonRaw = trim($parts[1] ?? '');
        $decoded = json_decode($jsonRaw, true);

        if (!is_array($decoded)) {
            return [$plainNotes, null];
        }

        return [$plainNotes, $decoded];
    }
}
