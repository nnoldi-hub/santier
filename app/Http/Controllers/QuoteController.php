<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Mail\QuoteSentMail;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteTemplate;
use App\Support\DocumentBranding;
use App\Support\QuotePdfPresenter;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class QuoteController extends Controller
{
    private const AI_BREAKDOWN_MARKER = '[AI_BREAKDOWN_JSON]';

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $status = $request->string('status')->toString();
        $projectId = $request->integer('project_id');

        $quotes = Quote::query()
            ->with(['project:id,name', 'creator:id,name'])
            ->where('tenant_id', $tenantId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($projectId > 0, fn ($q) => $q->where('project_id', $projectId))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'projects' => Project::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'project_id' => $projectId > 0 ? $projectId : '',
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());
        $catalog = $this->quoteCatalogData();
        $canonicalTemplates = collect(config('quote_templates.canonical', []))
            ->map(fn (array $template) => [
                'id' => (string) ($template['id'] ?? ''),
                'name' => (string) ($template['name'] ?? 'Template canonic'),
                'project_type' => (string) ($template['project_type'] ?? ''),
                'complexity_factor' => (float) ($template['complexity_factor'] ?? 1),
                'template_payload' => is_array($template['template_payload'] ?? null) ? $template['template_payload'] : [],
            ])
            ->filter(fn (array $row) => $row['id'] !== '')
            ->values();

        $recommendedTemplates = QuoteTemplate::query()
            ->with('sourceProject:id,name,status')
            ->recommended($tenantId)
            ->limit(8)
            ->get()
            ->map(fn (QuoteTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'usage_count' => (int) $template->usage_count,
                'quality_score' => (float) $template->quality_score,
                'source_project_name' => $template->sourceProject?->name,
                'project_type' => (string) ((is_array($template->template_payload) ? ($template->template_payload['project_type'] ?? null) : null) ?? ''),
                'template_payload' => is_array($template->template_payload) ? $template->template_payload : [],
            ])
            ->values();

        return Inertia::render('Quotes/Create', [
            'projects' => Project::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'selectedProjectId' => $request->integer('project_id') ?: null,
            'materials' => $catalog['materials'],
            'equipment' => $catalog['equipment'],
            'canonicalTemplates' => $canonicalTemplates,
            'recommendedTemplates' => $recommendedTemplates,
            'defaultOfferStages' => [
                ['name' => 'Demolare', 'duration_days' => 2],
                ['name' => 'Instalatii electrice', 'duration_days' => 3],
                ['name' => 'Instalatii sanitare', 'duration_days' => 3],
                ['name' => 'Tencuieli', 'duration_days' => 4],
                ['name' => 'Glet + vopsitorie', 'duration_days' => 4],
                ['name' => 'Pardoseli', 'duration_days' => 3],
                ['name' => 'Bai', 'duration_days' => 4],
                ['name' => 'Bucatarie', 'duration_days' => 3],
                ['name' => 'Usi', 'duration_days' => 2],
                ['name' => 'Curatenie finala', 'duration_days' => 1],
            ],
        ]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tenantId = TenantContext::id($request->user());
        $data['tenant_id'] = $tenantId;
        $data['created_by'] = $request->user()->id;
        $data['discount_pct'] = $data['discount_pct'] ?? 0;
        $data['tva_pct'] = $data['tva_pct'] ?? 21;
        $minMarginPct = (float) ($data['min_margin_pct'] ?? 12);
        $quoteMeta = is_array($data['quote_meta'] ?? null) ? $data['quote_meta'] : [];
        $sourceTemplateId = !empty($quoteMeta['source_template_id']) ? (int) $quoteMeta['source_template_id'] : null;

        $normalizedItems = $this->normalizeQuoteItems($data['items'] ?? [], $tenantId);
        $normalizedItems = $this->applyAutoMarkupForSpecialStages($normalizedItems, $minMarginPct);
        $itemsSummary = $this->summarizeItems($normalizedItems);
        $baseTotalNet = !empty($normalizedItems) ? $itemsSummary['sell_total'] : (float) $data['total_net'];

        if (!empty($normalizedItems)) {
            $this->assertMargin(
                costTotal: $itemsSummary['cost_total'],
                baseTotalNet: $baseTotalNet,
                discountPct: (float) $data['discount_pct'],
                minMarginPct: $minMarginPct,
            );
            $this->assertStageMargins($normalizedItems, $minMarginPct);
        }

        $projectLastVersion = Quote::where('project_id', $data['project_id'])->max('version') ?? 0;
        $data['version'] = $projectLastVersion + 1;

        $totals = $this->buildTotals($baseTotalNet, (float) $data['discount_pct'], (float) $data['tva_pct']);
        $data = array_merge($data, $totals);
        $data['total_net'] = $totals['total_net'];
        $data['meta'] = $this->enrichQuoteMeta($quoteMeta, $normalizedItems, $itemsSummary, $minMarginPct);
        unset($data['items'], $data['min_margin_pct'], $data['quote_meta']);

        if ($data['status'] === 'sent') {
            $data['sent_at'] = now();
        }

        if ($data['status'] === 'accepted') {
            $data['accepted_at'] = now();
        }

        $quote = DB::transaction(function () use ($data, $normalizedItems): Quote {
            $quote = Quote::create($data);

            if (!empty($normalizedItems)) {
                $this->syncQuoteItems($quote, $normalizedItems);
            }

            return $quote;
        });

        if ($sourceTemplateId) {
            $template = QuoteTemplate::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($sourceTemplateId)
                ->first();
            if ($template) {
                $template->markUsed();
            }
        }

        $project = Project::query()->find($quote->project_id);
        if ($project && $project->status === 'completed') {
            QuoteTemplate::upsertFromQuote($quote->loadMissing(['project:id,name,status', 'items']), $request->user()->id);
        }

        return redirect()->route('quotes.index')->with('success', 'Oferta creata cu succes!');
    }

    public function edit(Quote $quote): Response
    {
        $this->ensureTenantAccess($quote);
        $tenantId = TenantContext::id();
        $quote->loadMissing('items');
        $catalog = $this->quoteCatalogData();

        return Inertia::render('Quotes/Edit', [
            'quote' => $quote,
            'projects' => Project::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']),
            'materials' => $catalog['materials'],
            'equipment' => $catalog['equipment'],
        ]);
    }

    public function update(StoreQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $this->ensureTenantAccess($quote, $request->user());
        $data = $request->validated();
        $data['discount_pct'] = $data['discount_pct'] ?? 0;
        $data['tva_pct'] = $data['tva_pct'] ?? 21;
        $minMarginPct = (float) ($data['min_margin_pct'] ?? 12);
        $quoteMeta = is_array($data['quote_meta'] ?? null) ? $data['quote_meta'] : [];

        $normalizedItems = $this->normalizeQuoteItems($data['items'] ?? [], (int) $quote->tenant_id);
        $normalizedItems = $this->applyAutoMarkupForSpecialStages($normalizedItems, $minMarginPct);
        $itemsSummary = $this->summarizeItems($normalizedItems);
        $baseTotalNet = !empty($normalizedItems) ? $itemsSummary['sell_total'] : (float) $data['total_net'];

        if (!empty($normalizedItems)) {
            $this->assertMargin(
                costTotal: $itemsSummary['cost_total'],
                baseTotalNet: $baseTotalNet,
                discountPct: (float) $data['discount_pct'],
                minMarginPct: $minMarginPct,
            );
            $this->assertStageMargins($normalizedItems, $minMarginPct);
        }

        $totals = $this->buildTotals($baseTotalNet, (float) $data['discount_pct'], (float) $data['tva_pct']);
        $data = array_merge($data, $totals);
        $data['total_net'] = $totals['total_net'];
        $data['meta'] = $this->enrichQuoteMeta($quoteMeta, $normalizedItems, $itemsSummary, $minMarginPct);
        unset($data['items'], $data['min_margin_pct'], $data['quote_meta']);

        if ($data['status'] === 'sent' && !$quote->sent_at) {
            $data['sent_at'] = now();
        }

        if ($data['status'] === 'accepted' && !$quote->accepted_at) {
            $data['accepted_at'] = now();
        }

        if (in_array($data['status'], ['draft', 'rejected'], true)) {
            $data['accepted_at'] = null;
        }

        DB::transaction(function () use ($quote, $data, $normalizedItems): void {
            $quote->update($data);
            $this->syncQuoteItems($quote, $normalizedItems);
        });

        return redirect()->route('quotes.index')->with('success', 'Oferta actualizata!');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $this->ensureTenantAccess($quote);
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Oferta stearsa!');
    }

    public function pdf(Quote $quote): HttpResponse
    {
        $this->ensureTenantAccess($quote);
        $quote->loadMissing(['project:id,name', 'creator:id,name', 'items']);
        $branding = DocumentBranding::resolve((int) $quote->tenant_id);

        [$displayNotes, $breakdown] = $this->extractBreakdownFromNotes((string) ($quote->notes ?? ''));

        if (!is_array($breakdown) && $quote->items->isNotEmpty()) {
            $breakdown = $this->buildBreakdownFromItems($quote->items);
        }

        $meta = is_array($quote->meta) ? $quote->meta : [];
        $presented = QuotePdfPresenter::present($quote, $meta, is_array($breakdown) ? $breakdown : [], $displayNotes, $branding);
        $template = in_array($branding['document_template'] ?? 'classic', ['classic', 'modern'], true) ? $branding['document_template'] : 'classic';

        $pdf = Pdf::loadView('quotes.pdf-' . $template, array_merge($presented, [
            'quote' => $quote,
            'branding' => $branding,
        ]))->setPaper('a4')->setOptions([
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download(sprintf('oferta-%d-v%d.pdf', $quote->id, $quote->version));
    }

    public function accept(Quote $quote): RedirectResponse
    {
        $this->ensureTenantAccess($quote);
        $quote->update([
            'status' => 'accepted',
            'sent_at' => $quote->sent_at ?: now(),
            'accepted_at' => now(),
        ]);

        return redirect()->route('quotes.index')->with('success', 'Oferta a fost marcata ca acceptata. Poti continua executia in proiect.');
    }

    public function send(Quote $quote): RedirectResponse
    {
        $this->ensureTenantAccess($quote);
        $quote->loadMissing(['project.client:id,name,email', 'project:id,name,client_id', 'creator:id,name', 'items']);

        $clientEmail = trim((string) ($quote->project?->client?->email ?? ''));
        if ($clientEmail === '') {
            return back()->with('error', 'Oferta nu poate fi trimisa: clientul proiectului nu are email definit.');
        }

        [$displayNotes, $breakdown] = $this->extractBreakdownFromNotes((string) ($quote->notes ?? ''));

        if (!is_array($breakdown) && $quote->items->isNotEmpty()) {
            $breakdown = $this->buildBreakdownFromItems($quote->items);
        }

        $branding = DocumentBranding::resolve((int) $quote->tenant_id);
        $meta = is_array($quote->meta) ? $quote->meta : [];
        $presented = QuotePdfPresenter::present($quote, $meta, is_array($breakdown) ? $breakdown : [], $displayNotes, $branding);
        $template = in_array($branding['document_template'] ?? 'classic', ['classic', 'modern'], true) ? $branding['document_template'] : 'classic';

        $pdfBinary = Pdf::loadView('quotes.pdf-' . $template, array_merge($presented, [
            'quote' => $quote,
            'branding' => $branding,
        ]))->setPaper('a4')->setOptions([
            'isRemoteEnabled' => true,
        ])->output();

        $pdfFileName = sprintf('oferta-%d-v%d.pdf', $quote->id, $quote->version);

        try {
            Mail::to($clientEmail)->send(new QuoteSentMail(
                quote: $quote,
                pdfBinary: $pdfBinary,
                fileName: $pdfFileName,
                recipientName: (string) ($quote->project?->client?->name ?? ''),
                whiteLabel: (bool) $branding['white_label'],
            ));
        } catch (Throwable $e) {
            return back()->with('error', 'Trimiterea emailului a esuat: ' . $e->getMessage());
        }

        $meta['last_sent_email'] = $clientEmail;
        $meta['last_sent_email_at'] = now()->toDateTimeString();

        $quote->update([
            'status' => $quote->status === 'accepted' ? 'accepted' : 'sent',
            'sent_at' => $quote->sent_at ?: now(),
            'meta' => $meta,
        ]);

        return back()->with('success', 'Oferta a fost trimisa pe email catre client.');
    }

    public function saveAsTemplate(Request $request, Quote $quote): RedirectResponse
    {
        $this->ensureTenantAccess($quote, $request->user());
        $quote->loadMissing(['project:id,name,status', 'items']);

        $templateName = trim((string) $request->input('name', ''));
        $template = QuoteTemplate::upsertFromQuote(
            quote: $quote,
            createdBy: $request->user()->id,
            name: $templateName !== '' ? $templateName : null,
        );

        return back()->with('success', 'Sablon salvat: ' . $template->name);
    }

    public function convertToProject(Quote $quote): RedirectResponse
    {
        $this->ensureTenantAccess($quote);
        $meta = is_array($quote->meta) ? $quote->meta : [];
        $meta['converted_to_project'] = true;
        $meta['converted_to_project_at'] = now()->toDateTimeString();

        $quote->update([
            'status' => 'accepted',
            'sent_at' => $quote->sent_at ?: now(),
            'accepted_at' => $quote->accepted_at ?: now(),
            'meta' => $meta,
        ]);

        return redirect()
            ->route('projects.show', $quote->project_id)
            ->with('success', 'Oferta a fost convertita in executie proiect.');
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

    private function quoteCatalogData(): array
    {
        $tenantId = TenantContext::id();

        return [
            'materials' => Material::query()
                ->where('tenant_id', $tenantId)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'unit', 'unit_price']),
            'equipment' => Equipment::query()
                ->where('tenant_id', $tenantId)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'cost_per_hour']),
        ];
    }

    private function ensureTenantAccess(Quote $quote, ?\App\Models\User $user = null): void
    {
        if (TenantContext::isSuperadmin($user)) {
            return;
        }

        abort_unless((int) $quote->tenant_id === TenantContext::id($user), 404);
    }

    private function normalizeQuoteItems(array $rawItems, int $tenantId): array
    {
        $normalized = [];

        foreach ($rawItems as $index => $item) {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $quantity = max((float) ($item['quantity'] ?? 0), 0);
            $costUnitPrice = max((float) ($item['cost_unit_price'] ?? 0), 0);
            $sellUnitPrice = max((float) ($item['sell_unit_price'] ?? 0), 0);

            if ($quantity <= 0) {
                continue;
            }

            $normalized[] = [
                'tenant_id' => $tenantId,
                'item_type' => (string) ($item['item_type'] ?? 'custom'),
                'reference_id' => !empty($item['reference_id']) ? (int) $item['reference_id'] : null,
                'name' => $name,
                'stage_name' => trim((string) ($item['stage_name'] ?? 'General')),
                'unit' => (string) ($item['unit'] ?? 'buc'),
                'quantity' => round($quantity, 3),
                'cost_unit_price' => round($costUnitPrice, 2),
                'sell_unit_price' => round($sellUnitPrice, 2),
                'line_cost_total' => round($quantity * $costUnitPrice, 2),
                'line_sell_total' => round($quantity * $sellUnitPrice, 2),
                'sort_order' => $index,
                'stage_order' => max((int) ($item['stage_order'] ?? 0), 0),
            ];
        }

        return $normalized;
    }

    private function summarizeItems(array $items): array
    {
        $costTotal = 0.0;
        $sellTotal = 0.0;

        foreach ($items as $item) {
            $costTotal += (float) ($item['line_cost_total'] ?? 0);
            $sellTotal += (float) ($item['line_sell_total'] ?? 0);
        }

        return [
            'cost_total' => round($costTotal, 2),
            'sell_total' => round($sellTotal, 2),
        ];
    }

    private function assertMargin(float $costTotal, float $baseTotalNet, float $discountPct, float $minMarginPct): void
    {
        if ($baseTotalNet <= 0) {
            throw ValidationException::withMessages([
                'total_net' => 'Totalul net calculat pe articole trebuie sa fie mai mare ca 0.',
            ]);
        }

        $discountAmount = $baseTotalNet * ($discountPct / 100);
        $effectiveNet = max($baseTotalNet - $discountAmount, 0);

        if ($effectiveNet <= $costTotal) {
            throw ValidationException::withMessages([
                'total_net' => 'Oferta este sub costul estimat al articolelor. Ajusteaza preturile sau discountul.',
            ]);
        }

        $marginPct = (($effectiveNet - $costTotal) / $effectiveNet) * 100;
        if ($marginPct < $minMarginPct) {
            throw ValidationException::withMessages([
                'total_net' => 'Marja estimata este ' . number_format($marginPct, 2, ',', '.') . '% si este sub pragul minim de ' . number_format($minMarginPct, 2, ',', '.') . '%.',
            ]);
        }
    }

    private function assertStageMargins(array $items, float $minMarginPct): void
    {
        $byStage = [];

        foreach ($items as $item) {
            $stage = trim((string) ($item['stage_name'] ?? 'General'));
            $byStage[$stage]['cost'] = ($byStage[$stage]['cost'] ?? 0) + (float) ($item['line_cost_total'] ?? 0);
            $byStage[$stage]['sell'] = ($byStage[$stage]['sell'] ?? 0) + (float) ($item['line_sell_total'] ?? 0);
        }

        foreach ($byStage as $stage => $totals) {
            $sell = (float) ($totals['sell'] ?? 0);
            $cost = (float) ($totals['cost'] ?? 0);

            if ($sell <= 0 || $sell <= $cost) {
                throw ValidationException::withMessages([
                    'total_net' => 'Etapa "' . $stage . '" este sub cost. Ajusteaza preturile pe etapa.',
                ]);
            }

            $margin = (($sell - $cost) / $sell) * 100;
            if ($margin < $minMarginPct) {
                throw ValidationException::withMessages([
                    'total_net' => 'Etapa "' . $stage . '" are marja ' . number_format($margin, 2, ',', '.') . '%, sub pragul minim de ' . number_format($minMarginPct, 2, ',', '.') . '%.',
                ]);
            }
        }
    }

    private function applyAutoMarkupForSpecialStages(array $items, float $minMarginPct): array
    {
        $targetMarginPct = max($minMarginPct, 10.0);
        $targetMarginPct = min($targetMarginPct, 95.0);
        $sellFactor = 1 / (1 - ($targetMarginPct / 100));

        foreach ($items as $index => $item) {
            $stage = mb_strtolower(trim((string) ($item['stage_name'] ?? '')));
            $isSpecialStage = in_array($stage, ['costuri indirecte', 'optiuni suplimentare'], true);

            if (!$isSpecialStage) {
                continue;
            }

            $quantity = max((float) ($item['quantity'] ?? 0), 0);
            $costUnitPrice = max((float) ($item['cost_unit_price'] ?? 0), 0);
            $sellUnitPrice = max((float) ($item['sell_unit_price'] ?? 0), 0);

            if ($quantity <= 0 || $costUnitPrice <= 0) {
                continue;
            }

            $requiredSellUnitPrice = round($costUnitPrice * $sellFactor, 2);

            if ($sellUnitPrice < $requiredSellUnitPrice) {
                $sellUnitPrice = $requiredSellUnitPrice;
                $items[$index]['sell_unit_price'] = $sellUnitPrice;
                $items[$index]['line_sell_total'] = round($quantity * $sellUnitPrice, 2);
            }
        }

        return $items;
    }

    private function enrichQuoteMeta(array $meta, array $items, array $itemsSummary, float $minMarginPct): array
    {
        $stageSummary = [];

        foreach ($items as $item) {
            $stage = trim((string) ($item['stage_name'] ?? 'General'));
            if (!isset($stageSummary[$stage])) {
                $stageSummary[$stage] = ['cost' => 0.0, 'sell' => 0.0, 'margin_pct' => 0.0];
            }

            $stageSummary[$stage]['cost'] += (float) ($item['line_cost_total'] ?? 0);
            $stageSummary[$stage]['sell'] += (float) ($item['line_sell_total'] ?? 0);
        }

        foreach ($stageSummary as $stage => $values) {
            $sell = (float) $values['sell'];
            $stageSummary[$stage]['cost'] = round((float) $values['cost'], 2);
            $stageSummary[$stage]['sell'] = round($sell, 2);
            $stageSummary[$stage]['margin_pct'] = $sell > 0
                ? round((($sell - (float) $values['cost']) / $sell) * 100, 2)
                : 0.0;
        }

        $meta['min_margin_pct'] = $minMarginPct;
        $meta['items_summary'] = [
            'cost_total' => (float) ($itemsSummary['cost_total'] ?? 0),
            'sell_total' => (float) ($itemsSummary['sell_total'] ?? 0),
        ];
        $meta['stage_summary'] = $stageSummary;

        return $meta;
    }

    private function syncQuoteItems(Quote $quote, array $normalizedItems): void
    {
        $quote->items()->delete();

        if (empty($normalizedItems)) {
            return;
        }

        $payload = array_map(function (array $item) use ($quote): array {
            return [
                ...$item,
                'quote_id' => $quote->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $normalizedItems);

        QuoteItem::query()->insert($payload);
    }

    private function buildBreakdownFromItems(Collection $items): array
    {
        $materials = [];
        $labor = [];
        $equipment = [];
        $materialsTotal = 0.0;
        $laborTotal = 0.0;
        $equipmentTotal = 0.0;

        foreach ($items as $item) {
            $lineTotal = (float) $item->line_sell_total;

            if ($item->item_type === 'material') {
                $materials[] = [
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => (float) $item->sell_unit_price,
                    'estimated_cost' => $lineTotal,
                ];
                $materialsTotal += $lineTotal;
                continue;
            }

            if ($item->item_type === 'equipment') {
                $equipment[] = [
                    'name' => $item->name,
                    'estimated_hours' => (float) $item->quantity,
                    'hour_rate' => (float) $item->sell_unit_price,
                    'estimated_cost' => $lineTotal,
                ];
                $equipmentTotal += $lineTotal;
                continue;
            }

            $labor[] = [
                'name' => $item->name,
                'estimated_hours' => (float) $item->quantity,
                'hour_rate' => (float) $item->sell_unit_price,
                'estimated_cost' => $lineTotal,
            ];
            $laborTotal += $lineTotal;
        }

        return [
            'materials' => $materials,
            'labor' => $labor,
            'equipment' => $equipment,
            'totals' => [
                'materials_cost' => round($materialsTotal, 2),
                'labor_cost' => round($laborTotal, 2),
                'equipment_cost' => round($equipmentTotal, 2),
                'total_net' => round($materialsTotal + $laborTotal + $equipmentTotal, 2),
            ],
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
