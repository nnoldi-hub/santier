<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.45; margin: 0; }
        .page { padding: 24px; }
        .header { border-bottom: 3px solid {{ $branding['document_brand_color'] ?? '#f97316' }}; padding-bottom: 10px; margin-bottom: 14px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 52px; max-width: 180px; margin-bottom: 6px; }
        .doc-title { font-size: 20px; font-weight: 700; margin: 0; }
        .doc-subtitle { color: #6b7280; margin-top: 4px; }
        .doc-meta { margin-top: 6px; color: #374151; font-size: 10px; }
        .company-box { text-align: right; color: #4b5563; font-size: 10px; }
        .code-pill { display: inline-block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 999px; padding: 2px 8px; font-size: 9px; font-weight: 700; }
        .section { margin-top: 12px; }
        .section-title { font-size: 12px; font-weight: 700; margin: 0 0 6px; color: #0f172a; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { border: 1px solid #d1d5db; padding: 7px 8px; vertical-align: top; }
        .label { display: block; font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
        .box { border: 1px solid #d1d5db; padding: 9px 10px; }
        .hint { color: #6b7280; font-size: 10px; }
        .status-ok { color: #166534; font-weight: 700; }
        .status-no { color: #b91c1c; font-weight: 700; }
        .financial { width: 100%; border-collapse: collapse; }
        .financial th, .financial td { border: 1px solid #d1d5db; padding: 7px 8px; text-align: left; }
        .financial th { background: #f8fafc; font-size: 10px; color: #374151; }
        .decision-box { border: 1px solid #d1d5db; padding: 10px; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        .signature-table td { width: 50%; vertical-align: bottom; padding-right: 14px; }
        .signature-line { margin-top: 26px; border-top: 1px solid #9ca3af; padding-top: 4px; color: #4b5563; font-size: 10px; }
        .footer { margin-top: 14px; padding-top: 8px; border-top: 1px solid #d1d5db; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td>
                        @if(!empty($logoSource))
                            <img class="logo" src="{{ $logoSource }}" alt="{{ $branding['company_name'] }} logo">
                        @endif
                        @unless($whiteLabel)
                            <div class="doc-subtitle" style="margin-top: 0; margin-bottom: 4px;">Șantierul devine clar.</div>
                        @endunless
                        <p class="doc-title">Proces verbal de receptie</p>
                        <div class="doc-subtitle">{{ $document->title }}</div>
                        <div class="doc-meta">Nr. document: {{ $document->id }} · Data emitere: {{ $issuedAt }} · <span class="code-pill">Cod intern: {{ $internalCode }}</span></div>
                    </td>
                    <td class="company-box">
                        <div><strong>{{ $branding['company_name'] }}</strong></div>
                        @if($documentIssuer !== '')<div>Emitent: {{ $documentIssuer }}</div>@endif
                        @if(!empty($branding['company_address']))<div>{{ $branding['company_address'] }}</div>@endif
                        @if(!empty($branding['company_phone']))<div>Tel: {{ $branding['company_phone'] }}</div>@endif
                        @if(!empty($branding['support_email']))<div>Email: {{ $branding['support_email'] }}</div>@endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h3 class="section-title">B. Date proiect</h3>
            <table class="grid">
                <tr>
                    <td>
                        <span class="label">Proiect</span>
                        {{ $document->project?->name ?? '-' }}
                    </td>
                    <td>
                        <span class="label">Etapa / Lucrare</span>
                        {{ $document->stage?->name ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Locatie</span>
                        {{ $document->project?->address ?? '-' }}
                    </td>
                    <td>
                        <span class="label">Beneficiar</span>
                        {{ $document->project?->client?->name ?? 'Beneficiar nespecificat' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Executant / Contractor</span>
                        {{ $document->contractor?->name ?? '-' }}
                    </td>
                    <td>
                        <span class="label">Perioada executiei</span>
                        {{ $executionPeriod }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h3 class="section-title">C. Descrierea lucrarii</h3>
            <div class="box">
                <div><strong>Ce s-a executat:</strong> {{ $document->title }}</div>
                <div><strong>Conformitate cu proiectul:</strong> {{ $isConform ? 'Conforma cu cerintele stabilite' : 'Necesita verificari suplimentare' }}</div>
                <div><strong>Materiale folosite:</strong> Conform documentatiei tehnice si devizului aferent.</div>
                <div><strong>Observatii tehnice:</strong></div>
                <div style="white-space: pre-line;">{{ !empty($document->notes) ? $document->notes : 'Nu au fost consemnate observatii tehnice suplimentare.' }}</div>
            </div>
        </div>

        <div class="section">
            <h3 class="section-title">D. Constatari la receptie</h3>
            <div class="box">
                <div>Stare lucrare: {!! $isConform ? '<span class="status-ok">Conforma</span>' : '<span class="status-no">Neconforma</span>' !!}</div>
                <div><strong>Defecte constatate:</strong> {{ $isConform ? 'Nu s-au identificat defecte majore la momentul receptiei.' : 'Sunt necesare remedieri inainte de inchiderea receptiei.' }}</div>
                <div><strong>Recomandari:</strong> Monitorizare post-receptie si validarea documentata a eventualelor observatii.</div>
                <div><strong>Termen de remediere:</strong> {{ $isConform ? 'Nu este necesar' : 'Se stabileste de comun acord intre beneficiar si executant' }}</div>
            </div>
        </div>

        <div class="section">
            <h3 class="section-title">E. Situatie financiara</h3>
            <table class="financial">
                <thead>
                    <tr>
                        <th>Tip document</th>
                        <th>Valoare</th>
                        <th>Status plata</th>
                        <th>Referinta contract</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $document->type_label ?? $document->type }}</td>
                        <td>{{ number_format((float) $document->amount, 2, ',', '.') }} RON</td>
                        <td>{{ $document->payment_status_label ?? $document->payment_status }}</td>
                        <td>{{ $document->invoice_number ?: 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3 class="section-title">F. Declaratii finale</h3>
            <div class="decision-box">
                <div>{{ $acceptanceText }}</div>
                <div class="hint" style="margin-top:4px;">Documentul poate fi utilizat pentru arhivare, audit intern si verificari ulterioare.</div>
            </div>
        </div>

        <div class="section">
            <h3 class="section-title">G. Semnaturi</h3>
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-line">Reprezentant beneficiar</div>
                    </td>
                    <td>
                        <div class="signature-line">Reprezentant executant</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="signature-line">Data semnarii: ____ / ____ / ______</div>
                    </td>
                    <td>
                        <div class="signature-line">Stampila (optional)</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            @if(!empty($branding['app_name']))
                Document generat automat de {{ $branding['app_name'] }} la {{ now()->format('d.m.Y H:i') }} ·
            @else
                Document generat la {{ now()->format('d.m.Y H:i') }} ·
            @endif
            Cod unic document: {{ $uniqueCode }}
            @unless($whiteLabel)
                · modulia.ro · © 2026 Modulia
            @endunless
        </div>
    </div>
</body>
</html>
