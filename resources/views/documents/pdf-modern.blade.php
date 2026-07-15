<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.45; margin: 0; }
        .page { padding: 24px; }
        .header { background: {{ $branding['document_brand_color'] ?? '#f97316' }}; padding: 16px 18px; margin-bottom: 14px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 46px; max-width: 170px; margin-bottom: 6px; }
        .doc-title { font-size: 20px; font-weight: 700; margin: 0; color: #ffffff; }
        .doc-subtitle { color: #fef3f0; margin-top: 4px; opacity: 0.9; }
        .doc-meta { margin-top: 6px; color: #fef3f0; font-size: 10px; opacity: 0.9; }
        .company-box { text-align: right; color: #fef3f0; font-size: 10px; }
        .code-pill { display: inline-block; background: #ffffff; color: {{ $branding['document_brand_color'] ?? '#f97316' }}; border-radius: 999px; padding: 2px 8px; font-size: 9px; font-weight: 700; }
        .section { margin-top: 14px; }
        .section-title { display: inline-block; font-size: 10px; font-weight: 700; color: #ffffff; background: {{ $branding['document_brand_color'] ?? '#f97316' }}; border-radius: 999px; padding: 4px 12px; margin-bottom: 8px; letter-spacing: 0.03em; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { border: 1px solid #e5e7eb; padding: 7px 8px; vertical-align: top; }
        .label { display: block; font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
        .box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px 12px; background: #fafafa; }
        .hint { color: #6b7280; font-size: 10px; }
        .status-ok { color: #166534; font-weight: 700; }
        .status-no { color: #b91c1c; font-weight: 700; }
        .hero { width: 100%; border: 2px solid {{ $branding['document_brand_color'] ?? '#f97316' }}; border-radius: 10px; padding: 12px 16px; margin-bottom: 4px; }
        .hero-label { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .hero-value { font-size: 22px; font-weight: 700; color: {{ $branding['document_brand_color'] ?? '#f97316' }}; margin-top: 2px; }
        .hero-meta { margin-top: 6px; font-size: 10px; color: #4b5563; }
        .decision-box { border: 1px solid #e5e7eb; border-radius: 10px; padding: 10px; background: #fafafa; }
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
                        <p class="doc-title">{{ $document->type_label }}</p>
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
            <div class="hero">
                <div class="hero-label">Situatie financiara</div>
                <div class="hero-value">{{ number_format((float) $document->amount, 2, ',', '.') }} RON</div>
                <div class="hero-meta">
                    {{ $document->type_label ?? $document->type }} · Status plata: {{ $document->payment_status_label ?? $document->payment_status }}
                    @if($document->invoice_number) · Referinta contract: {{ $document->invoice_number }} @endif
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">B. Date proiect</div>
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
            <div class="section-title">C. Descrierea lucrarii</div>
            <div class="box">
                @if($document->type === 'proc_verbal_receptie')
                    <div><strong>Comisie de receptie:</strong></div>
                    <div style="white-space: pre-line;">{{ $typeData['comisie'] ?? '-' }}</div>
                    <div style="margin-top:6px;"><strong>Descriere lucrari receptionate:</strong></div>
                    <div style="white-space: pre-line;">{{ $typeData['descriere_lucrari'] ?? '-' }}</div>
                @elseif($document->type === 'proc_verbal_lucrari_ascunse')
                    <div><strong>Descriere lucrari ascunse:</strong></div>
                    <div style="white-space: pre-line;">{{ $typeData['descriere_lucrari_ascunse'] ?? '-' }}</div>
                    <div style="margin-top:6px;"><strong>Verificari efectuate:</strong></div>
                    <div style="white-space: pre-line;">{{ $typeData['verificari_efectuate'] ?? '-' }}</div>
                    <div style="margin-top:6px;"><strong>Responsabil tehnic:</strong> {{ $typeData['responsabil_tehnic'] ?? '-' }}</div>
                @else
                    <div><strong>Ce s-a executat:</strong> {{ $document->title }}</div>
                    <div><strong>Conformitate cu proiectul:</strong> {{ $isConform ? 'Conforma cu cerintele stabilite' : 'Necesita verificari suplimentare' }}</div>
                    <div><strong>Materiale folosite:</strong> Conform documentatiei tehnice si devizului aferent.</div>
                    <div><strong>Observatii tehnice:</strong></div>
                    <div style="white-space: pre-line;">{{ !empty($document->notes) ? $document->notes : 'Nu au fost consemnate observatii tehnice suplimentare.' }}</div>
                @endif
            </div>
        </div>

        @if($document->type === 'proc_verbal_receptie')
            <div class="section">
                <div class="section-title">D. Constatari la receptie</div>
                <div class="box">
                    <div>Concluzie: {!! ($typeData['concluzie'] ?? null) === 'admis' ? '<span class="status-ok">Admis</span>' : '<span class="status-no">Respins</span>' !!}</div>
                    <div style="margin-top:6px;"><strong>Defecte constatate:</strong></div>
                    <div style="white-space: pre-line;">{{ $typeData['defecte'] ?? 'Nu au fost consemnate defecte.' }}</div>
                </div>
            </div>
        @elseif(!in_array($document->type, ['proc_verbal_lucrari_ascunse'], true))
            <div class="section">
                <div class="section-title">D. Constatari la receptie</div>
                <div class="box">
                    <div>Stare lucrare: {!! $isConform ? '<span class="status-ok">Conforma</span>' : '<span class="status-no">Neconforma</span>' !!}</div>
                    <div><strong>Defecte constatate:</strong> {{ $isConform ? 'Nu s-au identificat defecte majore la momentul receptiei.' : 'Sunt necesare remedieri inainte de inchiderea receptiei.' }}</div>
                    <div><strong>Recomandari:</strong> Monitorizare post-receptie si validarea documentata a eventualelor observatii.</div>
                    <div><strong>Termen de remediere:</strong> {{ $isConform ? 'Nu este necesar' : 'Se stabileste de comun acord intre beneficiar si executant' }}</div>
                </div>
            </div>
        @endif

        <div class="section">
            <div class="section-title">F. Declaratii finale</div>
            <div class="decision-box">
                <div>{{ $acceptanceText }}</div>
                <div class="hint" style="margin-top:4px;">Documentul poate fi utilizat pentru arhivare, audit intern si verificari ulterioare.</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">G. Semnaturi</div>
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
