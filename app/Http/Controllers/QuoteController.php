<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\Project;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
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
        $data['tva_pct'] = $data['tva_pct'] ?? 19;

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
        $data['tva_pct'] = $data['tva_pct'] ?? 19;

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
}
