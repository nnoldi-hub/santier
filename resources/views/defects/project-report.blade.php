<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Raport Defecte - {{ $project->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { border-bottom: 2px solid {{ $branding['brand_color'] ?? '#f97316' }}; padding-bottom: 10px; margin-bottom: 14px; }
        .brand-row { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; }
        .brand-logo { max-height: 48px; max-width: 170px; object-fit: contain; margin-bottom: 8px; }
        .title { font-size: 20px; margin: 0; }
        .meta { color: #6b7280; margin-top: 4px; }
        .summary { display: flex; gap: 10px; margin-bottom: 16px; }
        .summary-box { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; text-align: center; }
        .summary-box .value { font-size: 18px; font-weight: bold; }
        .summary-box .label { font-size: 10px; color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; font-size: 11px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; }
        .ok { background: #dcfce7; color: #166534; }
        .warn { background: #fef3c7; color: #92400e; }
        .danger { background: #fee2e2; color: #991b1b; }
        .neutral { background: #f3f4f6; color: #374151; }
        .small { font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    @php
        $whiteLabel = $branding['white_label'] ?? false;
        $statusLabels = ['open' => 'Deschis', 'in_progress' => 'In progres', 'resolved' => 'Rezolvat', 'rejected' => 'Respins'];
        $statusBadgeClass = fn ($status) => match ($status) {
            'resolved' => 'ok',
            'open' => 'danger',
            'in_progress' => 'warn',
            default => 'neutral',
        };
    @endphp
    <div class="header">
        <div class="brand-row">
            <div>
                @if(!empty($branding['document_logo_url']))
                    <img class="brand-logo" src="{{ $branding['document_logo_url'] }}" alt="{{ $branding['company_name'] ?? '' }} logo">
                @endif
                <h1 class="title">Raport de defecte agregat</h1>
                <div class="meta">Proiect: {{ $project->name }}</div>
                <div class="meta">Generat la: {{ $generatedAt }}</div>
            </div>
            <div class="meta" style="text-align:right;">
                <div><strong>{{ $branding['company_name'] ?? '' }}</strong></div>
                @if(!empty($branding['company_address']))<div>{{ $branding['company_address'] }}</div>@endif
                @if(!empty($branding['company_email']))<div>Email: {{ $branding['company_email'] }}</div>@endif
            </div>
        </div>
        @unless($whiteLabel)
            <div class="small" style="margin-top: 6px;">modulia.ro · © 2026 Modulia</div>
        @endunless
    </div>

    <div class="summary">
        <div class="summary-box"><div class="value">{{ $summary['total'] }}</div><div class="label">Total defecte</div></div>
        <div class="summary-box"><div class="value">{{ $summary['open'] }}</div><div class="label">Deschise</div></div>
        <div class="summary-box"><div class="value">{{ $summary['in_progress'] }}</div><div class="label">In progres</div></div>
        <div class="summary-box"><div class="value">{{ $summary['resolved'] }}</div><div class="label">Rezolvate</div></div>
        <div class="summary-box"><div class="value">{{ $summary['rejected'] }}</div><div class="label">Respinse</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Titlu</th>
                <th>Etapa</th>
                <th>Responsabil</th>
                <th>Status</th>
                <th>Poze</th>
                <th>Semnatura</th>
            </tr>
        </thead>
        <tbody>
            @forelse($defects as $defect)
                <tr>
                    <td>{{ $defect->title }}</td>
                    <td>{{ $defect->phase?->name ?? '-' }}</td>
                    <td>{{ $defect->assignee?->name ?? '-' }}</td>
                    <td><span class="badge {{ $statusBadgeClass($defect->status) }}">{{ $statusLabels[$defect->status] ?? $defect->status }}</span></td>
                    <td>{{ $defect->photos->count() }}</td>
                    <td>{{ $defect->signature_path ? ('Da - ' . ($defect->signed_by_name ?? '-')) : 'Nu' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="small">Nu exista defecte pentru acest proiect.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
