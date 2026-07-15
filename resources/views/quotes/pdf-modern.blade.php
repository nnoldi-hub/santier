<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Oferta #{{ $quote->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        .header { background: {{ $branding['document_brand_color'] ?? '#f97316' }}; padding: 16px 18px; margin-bottom: 14px; }
        .brand-row { width: 100%; }
        .brand-left { width: 68%; vertical-align: top; }
        .brand-right { width: 32%; vertical-align: top; text-align: right; color: #fdf4ff; font-size: 10px; }
        .brand-logo { max-height: 46px; max-width: 160px; object-fit: contain; margin-bottom: 6px; }
        .title { font-size: 19px; font-weight: 700; margin: 0 0 2px; color: #ffffff; }
        .meta { color: #fef3f0; font-size: 10px; opacity: 0.9; }
        .section { margin-top: 16px; }
        .section-title { display: inline-block; font-size: 10px; font-weight: 700; color: #ffffff; background: {{ $branding['document_brand_color'] ?? '#f97316' }}; border-radius: 999px; padding: 4px 12px; margin-bottom: 8px; letter-spacing: 0.03em; }
        .value { font-size: 12px; font-weight: 600; }
        .hero { width: 100%; border: 2px solid {{ $branding['document_brand_color'] ?? '#f97316' }}; border-radius: 10px; padding: 12px 16px; margin-bottom: 4px; }
        .hero-total-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .hero-total-value { font-size: 24px; font-weight: 700; color: {{ $branding['document_brand_color'] ?? '#f97316' }}; margin-top: 2px; }
        .hero-stats { width: 100%; margin-top: 8px; }
        .hero-stat { padding-right: 18px; }
        .hero-stat-label { font-size: 9px; color: #6b7280; text-transform: uppercase; }
        .hero-stat-value { font-size: 12px; font-weight: 700; color: #111827; margin-top: 1px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #fafafa; font-weight: 700; color: #374151; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 9px; background: #ffffff; color: {{ $branding['document_brand_color'] ?? '#f97316' }}; border: 1px solid {{ $branding['document_brand_color'] ?? '#f97316' }}; }
        .rounded-box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; background: #fafafa; }
        .timeline-wrap { border-radius: 10px; background: #fafafa; padding: 10px 12px; }
        .timeline-row { margin-bottom: 5px; }
        .timeline-label { font-size: 9px; color: #475569; margin-bottom: 2px; }
        .timeline-bar-bg { width: 100%; height: 7px; background: #e5e7eb; border-radius: 999px; }
        .timeline-bar { height: 7px; background: {{ $branding['document_brand_color'] ?? '#f97316' }}; border-radius: 999px; }
        .footer { margin-top: 16px; color: #6b7280; font-size: 9px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header">
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
        <div class="hero">
            <div class="hero-total-label">Total de plata</div>
            <div class="hero-total-value">{{ number_format((float) $quote->total_gross, 2, ',', '.') }} RON</div>
            <table class="hero-stats">
                <tr>
                    <td class="hero-stat">
                        <div class="hero-stat-label">Timeline estimat</div>
                        <div class="hero-stat-value">{{ $timelineDays > 0 ? $timelineDays . ' zile' : 'Nespecificat' }}</div>
                    </td>
                    <td class="hero-stat">
                        <div class="hero-stat-label">Marja recomandata</div>
                        <div class="hero-stat-value">{{ number_format($recommendedMargin, 2, ',', '.') }}%</div>
                    </td>
                    <td class="hero-stat">
                        <div class="hero-stat-label">Profit estimat</div>
                        <div class="hero-stat-value">{{ number_format($profitValue, 2, ',', '.') }} RON ({{ number_format($profitMargin, 2, ',', '.') }}%)</div>
                    </td>
                </tr>
            </table>
        </div>
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
        <div class="rounded-box">
            <div class="value">Valabil pana la: {{ optional($quote->valid_until)->format('d.m.Y') ?? 'Nespecificat' }}</div>
            <div class="meta" style="margin-top:4px;">Emisa de: {{ $documentIssuer !== '' ? $documentIssuer : ($quote->creator?->name ?? 'Utilizator necunoscut') }}</div>
            @if(!empty($notes))
                <div style="margin-top:6px;">{{ $notes }}</div>
            @endif
        </div>
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
        <div class="rounded-box" style="margin-top:8px;">
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
