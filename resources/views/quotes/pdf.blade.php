<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Oferta #{{ $quote->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { border-bottom: 3px solid #f97316; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
        .meta { color: #6b7280; font-size: 11px; }
        .section { margin-top: 14px; }
        .section-title { font-size: 13px; font-weight: 700; margin-bottom: 6px; }
        .label { color: #6b7280; font-size: 11px; }
        .value { font-size: 13px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #e5e7eb; padding: 7px; text-align: left; }
        th { background: #f9fafb; font-weight: 700; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; font-size: 10px; background: #eef2ff; color: #3730a3; }
        .summary { width: 320px; margin-left: auto; }
        .summary td { font-size: 12px; }
        .summary tr.total td { font-weight: 700; background: #fff7ed; }
        .footer { margin-top: 22px; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
    @php
        $materials = is_array($breakdown['materials'] ?? null) ? $breakdown['materials'] : [];
        $labor = is_array($breakdown['labor'] ?? null) ? $breakdown['labor'] : [];
        $equipment = is_array($breakdown['equipment'] ?? null) ? $breakdown['equipment'] : [];
        $totals = is_array($breakdown['totals'] ?? null) ? $breakdown['totals'] : [];
        $materialsTotal = (float) ($totals['materials_cost'] ?? 0);
        $laborTotal = (float) ($totals['labor_cost'] ?? 0);
        $equipmentTotal = (float) ($totals['equipment_cost'] ?? 0);
    @endphp

    <div class="header">
        <p class="title">{{ $quote->title }}</p>
        <div class="meta">
            Oferta #{{ $quote->id }} · Versiunea {{ $quote->version }} · Proiect: {{ $quote->project?->name ?? 'N/A' }}
        </div>
    </div>

    <div>
        <span class="badge">Status: {{ strtoupper($quote->status) }}</span>
    </div>

    <div class="section">
        <div class="label">Emisa de</div>
        <div class="value">{{ $quote->creator?->name ?? 'Utilizator necunoscut' }}</div>
    </div>

    <div class="section">
        <div class="label">Valabil pana la</div>
        <div class="value">{{ optional($quote->valid_until)->format('d.m.Y') ?? 'Nespecificat' }}</div>
    </div>

    <div class="section">
        <div class="section-title">A. Materiale</div>
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th class="text-right">Cantitate</th>
                    <th class="text-right">Pret unitar</th>
                    <th class="text-right">Valoare</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $item)
                    <tr>
                        <td>{{ $item['name'] ?? '-' }}</td>
                        <td class="text-right">{{ number_format((float) ($item['quantity'] ?? 0), 2, ',', '.') }} {{ $item['unit'] ?? '' }}</td>
                        <td class="text-right">{{ number_format((float) ($item['unit_price'] ?? 0), 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format((float) ($item['estimated_cost'] ?? 0), 2, ',', '.') }} RON</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Nu exista detalii de materiale pentru aceasta oferta.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">B. Manopera</div>
        <table>
            <thead>
                <tr>
                    <th>Tip manopera</th>
                    <th class="text-right">Ore estimate</th>
                    <th class="text-right">Tarif / ora</th>
                    <th class="text-right">Valoare</th>
                </tr>
            </thead>
            <tbody>
                @forelse($labor as $item)
                    <tr>
                        <td>{{ $item['name'] ?? 'Manopera' }}</td>
                        <td class="text-right">{{ number_format((float) ($item['estimated_hours'] ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) ($item['hour_rate'] ?? 0), 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format((float) ($item['estimated_cost'] ?? 0), 2, ',', '.') }} RON</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Nu exista detalii de manopera pentru aceasta oferta.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">C. Utilaje</div>
        <table>
            <thead>
                <tr>
                    <th>Tip utilaj</th>
                    <th class="text-right">Ore estimate</th>
                    <th class="text-right">Tarif / ora</th>
                    <th class="text-right">Valoare</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipment as $item)
                    <tr>
                        <td>{{ $item['name'] ?? 'Utilaj' }}</td>
                        <td class="text-right">{{ number_format((float) ($item['estimated_hours'] ?? 0), 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) ($item['hour_rate'] ?? 0), 2, ',', '.') }} RON</td>
                        <td class="text-right">{{ number_format((float) ($item['estimated_cost'] ?? 0), 2, ',', '.') }} RON</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Nu exista detalii de utilaje pentru aceasta oferta.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Total ofertare</div>
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

    @if(!empty($notes))
        <div class="section">
            <div class="label">Note</div>
            <div>{{ $notes }}</div>
        </div>
    @endif

    <div class="footer">
        Document generat automat la {{ now()->format('d.m.Y H:i') }}.
    </div>
</body>
</html>
