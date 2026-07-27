<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Brosura Modulia</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .page { page-break-after: always; padding: 10px 0; }
        .page:last-child { page-break-after: auto; }
        .brand-color { color: #f97316; }
        .cover-logo { max-height: 70px; max-width: 220px; object-fit: contain; margin-bottom: 24px; }
        .cover-title { font-size: 30px; font-weight: bold; margin: 0 0 10px; }
        .cover-subtitle { font-size: 15px; color: #6b7280; margin: 0 0 40px; }
        .cover-meta { font-size: 11px; color: #9ca3af; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 0 0 12px; border-bottom: 2px solid #f97316; padding-bottom: 6px; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td { vertical-align: top; padding: 8px 10px 8px 0; width: 50%; }
        .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; }
        .card-title { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
        .card-text { font-size: 11px; color: #4b5563; }
        .steps td { padding: 10px; border: 1px solid #e5e7eb; text-align: center; width: 33.33%; }
        .step-number { display: inline-block; width: 22px; height: 22px; line-height: 22px; border-radius: 999px; background: #f97316; color: #fff; font-weight: bold; margin-bottom: 6px; }
        table.plans { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.plans th, table.plans td { border: 1px solid #e5e7eb; padding: 8px; font-size: 11px; text-align: left; vertical-align: top; }
        table.plans th { background: #f9fafb; }
        .highlight-col { background: #fff7ed; }
        .contact-box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-top: 20px; }
    </style>
</head>
<body>
    @php
        $logoUrl = $settings['document_logo_url'] ?? '';
        $isAbsoluteLogo = (bool) preg_match('#^https?://#i', (string) $logoUrl);
        $fallbackLogo = public_path('brand/logo_modulia.png');
        $logoSource = $isAbsoluteLogo ? $logoUrl : (file_exists($fallbackLogo) ? $fallbackLogo : null);

        $painPoints = [
            ['title' => 'Taskuri pierdute', 'text' => 'Backlog clar pe proiect, responsabil si deadline, fara mesaje imprastiate.'],
            ['title' => 'Etape intarziate', 'text' => 'Monitorizare zilnica a progresului, interventie inainte sa intarzie santierul.'],
            ['title' => 'Lipsa vizibilitate', 'text' => 'Dashboard unificat pentru status, cost, defecte si decizii operative.'],
            ['title' => 'Defecte deschise', 'text' => 'Snag list cu prioritati, asignare si termen, pana la rezolvare.'],
            ['title' => 'Oferte dispersate', 'text' => 'Versionare devize si istoric centralizat, usor de comparat.'],
            ['title' => 'Rapoarte lente', 'text' => 'Export XLSX/PDF managerial cu filtre avansate in cateva secunde.'],
        ];

        $features = [
            ['title' => 'Ofertare inteligenta', 'text' => 'Oferte generate rapid, cu sabloane, costuri reale si timeline automat.'],
            ['title' => 'Proiecte & WBS', 'text' => 'Structura clara pe etape, taskuri si progres.'],
            ['title' => 'Taskuri & Progres', 'text' => 'Actualizari, poze, rapoarte si responsabilitati intr-un singur loc.'],
            ['title' => 'Calendar & Planificare', 'text' => 'Planificare vizuala, clara, fara haos.'],
            ['title' => 'Documente', 'text' => 'Documente ordonate, aprobari, versiuni, exporturi.'],
            ['title' => 'Financiar & Cost Tracking', 'text' => 'Costuri reale, materiale, manopera, profit, devize.'],
            ['title' => 'AI Tools', 'text' => 'Generare oferte, analiza proiecte si riscuri din timp.'],
            ['title' => 'Roluri & Permisiuni Enterprise', 'text' => 'Superadmin, Admin firma, roluri custom, permisiuni granulare.'],
        ];

        $howItWorks = [
            ['title' => 'Configurezi proiectul in 10 minute', 'text' => 'Sabloane de etape, responsabili si termene.'],
            ['title' => 'Executi si urmaresti progresul zilnic', 'text' => 'Taskuri, defecte si costuri intr-un singur tablou de bord.'],
            ['title' => 'Raportezi clar catre management', 'text' => 'Export PDF/XLSX, riscuri si decizii pe date reale.'],
        ];
    @endphp

    <div class="page" style="text-align: center; padding-top: 120px;">
        @if($logoSource)
            <img class="cover-logo" src="{{ $logoSource }}" alt="{{ $settings['company_name'] ?? 'Modulia' }} logo">
        @endif
        <div class="cover-title">Șantierul devine clar.</div>
        <div class="cover-subtitle">Broșura de prezentare Modulia - platforma de management de șantier pentru antreprenori și firme de construcții.</div>
        <div class="cover-meta">Generat la: {{ $generatedAt }}</div>
    </div>

    <div class="page">
        <h2>Probleme pe care le rezolva Modulia</h2>
        <table class="grid">
            @foreach(array_chunk($painPoints, 2) as $row)
                <tr>
                    @foreach($row as $item)
                        <td>
                            <div class="card">
                                <div class="card-title">{{ $item['title'] }}</div>
                                <div class="card-text">{{ $item['text'] }}</div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>

        <h2 style="margin-top: 24px;">Functionalitati cheie</h2>
        <table class="grid">
            @foreach(array_chunk($features, 2) as $row)
                <tr>
                    @foreach($row as $item)
                        <td>
                            <div class="card">
                                <div class="card-title">{{ $item['title'] }}</div>
                                <div class="card-text">{{ $item['text'] }}</div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </div>

    <div class="page">
        <h2>Cum functioneaza, in 3 pasi</h2>
        <table class="steps">
            <tr>
                @foreach($howItWorks as $step)
                    <td>
                        <div class="step-number">{{ $loop->iteration }}</div>
                        <div class="card-title">{{ $step['title'] }}</div>
                        <div class="card-text">{{ $step['text'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>

        <h2 style="margin-top: 28px;">Pachete si preturi</h2>
        <table class="plans">
            <thead>
                <tr>
                    <th>Pachet</th>
                    <th>Pret / luna</th>
                    <th>Include</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plans as $plan)
                    <tr class="{{ $plan['highlight'] ? 'highlight-col' : '' }}">
                        <td><strong>{{ $plan['name'] }}</strong>@if($plan['highlight']) <span class="brand-color">({{ $plan['badge'] }})</span>@endif</td>
                        <td>{{ $plan['price'] > 0 ? number_format($plan['price'], 0, ',', '.') . ' lei' : 'Gratuit' }}</td>
                        <td>{{ implode(' · ', $plan['items']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="muted" style="font-size: 10px; margin-top: 6px;">Preturile sunt orientative si pot fi personalizate pentru pachete enterprise.</p>
    </div>

    <div class="page" style="text-align: center; padding-top: 60px;">
        <h1>Hai sa vedem daca se potriveste firmei tale.</h1>
        <p class="muted">Pornesti in 10 minute cu wizard-ul de onboarding, un proiect demo si raport exportabil pentru management.</p>

        <div class="contact-box" style="display: inline-block; text-align: left; margin-top: 30px; width: 360px;">
            <div class="card-title" style="margin-bottom: 10px;">Contact</div>
            @if(!empty($settings['sales_email']))
                <div>Vanzari: {{ $settings['sales_email'] }}</div>
            @endif
            @if(!empty($settings['support_email']))
                <div>Suport: {{ $settings['support_email'] }}</div>
            @endif
            @if(!empty($settings['company_phone']))
                <div>Telefon: {{ $settings['company_phone'] }}</div>
            @endif
            <div>Web: modulia.ro</div>
        </div>

        <p class="muted" style="margin-top: 30px; font-size: 10px;">Modulia - Șantierul devine clar. · modulia.ro</p>
    </div>
</body>
</html>
