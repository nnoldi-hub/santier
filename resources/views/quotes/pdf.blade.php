<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Oferta #{{ $quote->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header { border-bottom: 4px solid {{ $branding['document_brand_color'] ?? '#f97316' }}; padding-bottom: 10px; margin-bottom: 14px; }
        .header-band { height: 6px; background: {{ $branding['document_brand_color'] ?? '#f97316' }}; margin-bottom: 10px; }
        .brand-row { width: 100%; }
        .brand-left { width: 68%; vertical-align: top; }
        .brand-right { width: 32%; vertical-align: top; text-align: right; color: #6b7280; font-size: 10px; }
        .brand-logo { max-height: 52px; max-width: 170px; object-fit: contain; margin-bottom: 6px; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 2px; color: {{ $branding['document_brand_color'] ?? '#f97316' }}; }
        .meta { color: #6b7280; font-size: 10px; }
        .section { margin-top: 14px; }
        .section-title { font-size: 12px; font-weight: 700; color: {{ $branding['document_brand_color'] ?? '#f97316' }}; border-left: 3px solid {{ $branding['document_brand_color'] ?? '#f97316' }}; padding-left: 6px; margin-bottom: 6px; }
        .value { font-size: 12px; font-weight: 600; }
        .summary-cards { width: 100%; border-collapse: separate; border-spacing: 6px 0; }
        .summary-card { background: #f8fafc; border: 1px solid #dbeafe; padding: 8px; }
        .summary-label { font-size: 9px; color: #6b7280; text-transform: uppercase; }
        .summary-value { font-size: 12px; font-weight: 700; color: #111827; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #eff6ff; font-weight: 700; color: #1e3a8a; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 9px; background: #e0e7ff; color: #3730a3; }
        .timeline-wrap { border: 1px solid #dbeafe; background: #f8fafc; padding: 8px; }
        .timeline-row { margin-bottom: 5px; }
        .timeline-label { font-size: 9px; color: #475569; margin-bottom: 2px; }
        .timeline-bar-bg { width: 100%; height: 7px; background: #e5e7eb; border-radius: 999px; }
        .timeline-bar { height: 7px; background: {{ $branding['document_brand_color'] ?? '#f97316' }}; border-radius: 999px; }
        .footer { margin-top: 16px; color: #6b7280; font-size: 9px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    @php
        $metaData = is_array($meta ?? null) ? $meta : [];
        $quoteItems = $quote->items ?? collect();
        $stagesMeta = is_array($metaData['stages'] ?? null) ? $metaData['stages'] : [];
        $smartInputs = is_array($metaData['smart_inputs'] ?? null) ? $metaData['smart_inputs'] : [];
        $indirectCosts = is_array($metaData['indirect_costs'] ?? null) ? $metaData['indirect_costs'] : [];
        $optionalFeatures = is_array($metaData['optional_features'] ?? null) ? $metaData['optional_features'] : [];
        $stageSummary = is_array($metaData['stage_summary'] ?? null) ? $metaData['stage_summary'] : [];
        $timelineDays = (int) ($metaData['timeline_days_total'] ?? 0);
        $clientName = $metaData['client_name'] ?? null;
        $packageTier = strtoupper((string) ($metaData['package_tier'] ?? 'STANDARD'));
        $projectType = strtoupper((string) ($metaData['project_type'] ?? 'N/A'));
        $recommendedMargin = (float) ($metaData['recommended_margin_pct'] ?? ($metaData['min_margin_pct'] ?? 0));
        $documentIssuer = trim((string) ($branding['document_issuer_name'] ?? ''));
        $materialMode = (string) ($metaData['material_mode'] ?? 'capped_allowance');
        $materialCaps = is_array($metaData['material_caps'] ?? null) ? $metaData['material_caps'] : [];
        $pricingScenarios = is_array($metaData['pricing_scenarios'] ?? null) ? $metaData['pricing_scenarios'] : [];

        $materials = is_array($breakdown['materials'] ?? null) ? $breakdown['materials'] : [];
        $labor = is_array($breakdown['labor'] ?? null) ? $breakdown['labor'] : [];
        $equipment = is_array($breakdown['equipment'] ?? null) ? $breakdown['equipment'] : [];
        $totals = is_array($breakdown['totals'] ?? null) ? $breakdown['totals'] : [];
        $materialsTotal = (float) ($totals['materials_cost'] ?? 0);
        $laborTotal = (float) ($totals['labor_cost'] ?? 0);
        $equipmentTotal = (float) ($totals['equipment_cost'] ?? 0);

        $itemsByStage = $quoteItems->groupBy(function ($item) {
            return trim((string) ($item->stage_name ?? 'General'));
        });

        $indirectAndOptionTotal = 0.0;
        foreach ($indirectCosts as $row) {
            $indirectAndOptionTotal += (float) ($row['amount'] ?? 0);
        }
        foreach ($optionalFeatures as $row) {
            if (!empty($row['enabled'])) {
                $indirectAndOptionTotal += (float) ($row['amount'] ?? 0);
            }
        }

        $stageRows = collect($stagesMeta)->map(function ($stage) use ($quoteItems, $indirectAndOptionTotal) {
            $name = (string) ($stage['name'] ?? 'Etapa');
            $days = (float) ($stage['duration_days'] ?? 0);
            $rows = $quoteItems->where('stage_name', $name);

            $materialSell = (float) $rows->where('item_type', 'material')->sum('line_sell_total');
            $laborSell = (float) $rows->where('item_type', 'labor')->sum('line_sell_total');
            $equipmentSell = (float) $rows->where('item_type', 'equipment')->sum('line_sell_total');
            $otherSell = (float) $rows->whereNotIn('item_type', ['material', 'labor', 'equipment'])->sum('line_sell_total');

            return [
                'name' => $name,
                'days' => $days,
                'materials' => $materialSell,
                'labor' => $laborSell,
                'equipment' => $equipmentSell,
                'indirect' => $otherSell,
                'total' => $materialSell + $laborSell + $equipmentSell + $otherSell,
            ];
        })->values();

        $timelineBase = max((float) $stageRows->max('days'), 1);
        $profitValue = (float) $quote->total_net - ($materialsTotal + $laborTotal + $equipmentTotal + $indirectAndOptionTotal);
        $profitMargin = (float) $quote->total_net > 0 ? ($profitValue / (float) $quote->total_net) * 100 : 0;

        $defaultCappedMaterialsTotal = ((float) ($smartInputs['walls_area'] ?? 0) * (float) ($materialCaps['paint_max_per_mp'] ?? 18))
            + ((float) ($smartInputs['floor_area'] ?? 0) * (float) ($materialCaps['parquet_max_per_mp'] ?? 100))
            + ((float) ($smartInputs['tile_area'] ?? 0) * (float) ($materialCaps['tile_max_per_mp'] ?? 80));
        $laborOnlyTotal = (float) ($pricingScenarios['labor_only_total'] ?? ((float) $quoteItems->where('item_type', '!=', 'material')->sum('line_sell_total')));
        $cappedMaterialsTotal = (float) ($pricingScenarios['capped_materials_total'] ?? $defaultCappedMaterialsTotal);
        $withCappedMaterialsTotal = (float) ($pricingScenarios['with_capped_materials_total'] ?? ($laborOnlyTotal + $cappedMaterialsTotal));
    @endphp

    <div class="header">
        <div class="header-band"></div>
        <table class="brand-row">
            <tr>
                <td class="brand-left">
                    @if(!empty($branding['document_logo_url']))
                        <img class="brand-logo" src="{{ $branding['document_logo_url'] }}" alt="{{ $branding['company_name'] ?? 'Santier' }} logo">
                    @endif
                    <p class="title">{{ $quote->title }}</p>
                    <div class="meta">
                        Oferta #{{ $quote->id }} · Versiunea {{ $quote->version }} · Proiect: {{ $quote->project?->name ?? 'N/A' }}
                    </div>
                </td>
                <td class="brand-right">
                    <div><strong>{{ $branding['company_name'] ?? 'Santier' }}</strong></div>
                    @if(!empty($branding['company_address']))<div>{{ $branding['company_address'] }}</div>@endif
                    @if(!empty($branding['company_phone']))<div>Tel: {{ $branding['company_phone'] }}</div>@endif
                    @if(!empty($branding['support_email']))<div>Email: {{ $branding['support_email'] }}</div>@endif
                    <div style="margin-top:5px;">Beneficiar: <strong>{{ $clientName ?: 'Nespecificat' }}</strong></div>
                </td>
            </tr>
        </table>
    </div>

    <div>
        <span class="badge">Status: {{ strtoupper($quote->status) }}</span>
        <span class="badge" style="margin-left:6px;">Pachet: {{ $packageTier }}</span>
        <span class="badge" style="margin-left:6px;">Tip proiect: {{ $projectType }}</span>
    </div>

    <div class="section">
        <div class="section-title">A. Sumar costuri</div>
        <table class="summary-cards">
            <tr>
                <td class="summary-card">
                    <div class="summary-label">Total de plata</div>
                    <div class="summary-value">{{ number_format((float) $quote->total_gross, 2, ',', '.') }} RON</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">Timeline estimat</div>
                    <div class="summary-value">{{ $timelineDays > 0 ? $timelineDays . ' zile' : 'Nespecificat' }}</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">Marja recomandata</div>
                    <div class="summary-value">{{ number_format($recommendedMargin, 2, ',', '.') }}%</div>
                </td>
                <td class="summary-card">
                    <div class="summary-label">Profit estimat</div>
                    <div class="summary-value">{{ number_format($profitValue, 2, ',', '.') }} RON ({{ number_format($profitMargin, 2, ',', '.') }}%)</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">B. Etape oferta</div>
        <table>
            <thead>
                <tr>
                    <th>Etapa</th>
                    <th class="text-right">Materiale</th>
                    <th class="text-right">Manopera</th>
                    <th class="text-right">Utilaje</th>
                    <th class="text-right">Indirecte</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Zile</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stageRows as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-right">{{ number_format($row['materials'], 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format($row['labor'], 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format($row['equipment'], 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format($row['indirect'], 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format($row['total'], 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format($row['days'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Nu exista etape definite pentru aceasta oferta.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">C. Optiuni suplimentare</div>
        @php
            $enabledOptions = collect($optionalFeatures)->filter(fn ($row) => !empty($row['enabled']))->values();
        @endphp
        <table>
            <thead>
                <tr>
                    <th>Optiune</th>
                    <th class="text-right">Status</th>
                    <th class="text-right">Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($optionalFeatures as $row)
                    <tr>
                        <td>{{ $row['label'] ?? 'Optiune' }}</td>
                        <td class="text-right">{{ !empty($row['enabled']) ? 'Bifata' : 'Nebifata' }}</td>
                        <td class="text-right">{{ number_format((float) ($row['amount'] ?? 0), 2, ',', '.') }} RON</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">Nu exista optiuni suplimentare configurate.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">D. Timeline vizual</div>
        <div class="timeline-wrap">
            @forelse($stageRows as $row)
                @php
                    $width = $timelineBase > 0 ? min(100, round(($row['days'] / $timelineBase) * 100, 2)) : 0;
                @endphp
                <div class="timeline-row">
                    <div class="timeline-label">{{ $row['name'] }} ({{ number_format($row['days'], 0, ',', '.') }} zile)</div>
                    <div class="timeline-bar-bg">
                        <div class="timeline-bar" style="width: {{ $width }}%;"></div>
                    </div>
                </div>
            @empty
                <div class="meta">Nu exista date suficiente pentru timeline.</div>
            @endforelse
        </div>
    </div>

    <div class="section">
        <div class="section-title">E. Note & conditii</div>
        <div class="value">Valabil pana la: {{ optional($quote->valid_until)->format('d.m.Y') ?? 'Nespecificat' }}</div>
        <div class="meta" style="margin-top:4px;">Emisa de: {{ $documentIssuer !== '' ? $documentIssuer : ($quote->creator?->name ?? 'Utilizator necunoscut') }}</div>
        @if(!empty($notes))
            <div style="margin-top:6px;">{{ $notes }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">F. Detaliere smart input</div>
        <table>
            <thead>
                <tr>
                    <th>Indicator</th>
                    <th class="text-right">Valoare</th>
                </tr>
            </thead>
            <tbody>
                @forelse($smartInputs as $key => $value)
                    <tr>
                        <td>{{ str_replace('_', ' ', (string) $key) }}</td>
                        <td class="text-right">{{ number_format((float) $value, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">Nu exista date de cantitati inteligente.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="meta" style="margin-top:6px;">
            Strategie materiale: <strong>{{ $materialMode === 'client_supplied' ? 'Fara materiale (client achizitioneaza)' : 'Cu materiale plafonate' }}</strong>
            · Plafon parchet: {{ number_format((float) ($materialCaps['parquet_max_per_mp'] ?? 100), 2, ',', '.') }} RON/mp
            · Plafon gresie/faianta: {{ number_format((float) ($materialCaps['tile_max_per_mp'] ?? 80), 2, ',', '.') }} RON/mp
            · Plafon vopsea + glet: {{ number_format((float) ($materialCaps['paint_max_per_mp'] ?? 18), 2, ',', '.') }} RON/mp
        </div>
    </div>

    <div class="section">
        <div class="section-title">G. Total ofertare</div>
        <table class="summary">
            <tbody>
                <tr>
                    <td>Subtotal materiale</td>
                    <td class="text-right">{{ number_format($materialsTotal, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Subtotal manopera</td>
                    <td class="text-right">{{ number_format($laborTotal, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Subtotal utilaje</td>
                    <td class="text-right">{{ number_format($equipmentTotal, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Indirecte + optiuni</td>
                    <td class="text-right">{{ number_format($indirectAndOptionTotal, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Deviz 1 - fara materiale (client achizitioneaza)</td>
                    <td class="text-right">{{ number_format($laborOnlyTotal, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Materiale plafonate estimate</td>
                    <td class="text-right">{{ number_format($cappedMaterialsTotal, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Deviz 2 - cu materiale plafonate</td>
                    <td class="text-right">{{ number_format($withCappedMaterialsTotal, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Profit estimat</td>
                    <td class="text-right">{{ number_format($profitValue, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>Total net</td>
                    <td class="text-right">{{ number_format((float) $quote->total_net, 2, ',', '.') }} RON</td>
                </tr>
                <tr>
                    <td>TVA ({{ number_format((float) $quote->tva_pct, 2, ',', '.') }}%)</td>
                    <td class="text-right">{{ number_format((float) $quote->total_tva, 2, ',', '.') }} RON</td>
                </tr>
                <tr class="total">
                    <td>Total de plata</td>
                    <td class="text-right">{{ number_format((float) $quote->total_gross, 2, ',', '.') }} RON</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer">
        Document generat automat de Santier la {{ now()->format('d.m.Y H:i') }}.
    </div>
</body>
</html>
