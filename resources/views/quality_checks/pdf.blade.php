<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Raport Calitate #{{ $qualityCheck->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin-bottom: 8px; }
        h2 { font-size: 14px; margin-top: 20px; margin-bottom: 8px; }
        .muted { color: #6b7280; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; font-size: 11px; }
        .ok { background: #dcfce7; color: #166534; }
        .warn { background: #fef3c7; color: #92400e; }
        .danger { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    <h1>Raport PDF Calitate</h1>
    <div class="muted">Generat la: {{ now()->format('d.m.Y H:i') }}</div>

    <h2>Detalii verificare</h2>
    <table>
        <tr>
            <th>Titlu</th>
            <td>{{ $qualityCheck->title }}</td>
        </tr>
        <tr>
            <th>Tip receptie</th>
            <td>{{ $qualityCheck->reception_type === 'final' ? 'Finala' : 'Partiala' }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ $statusLabels[$qualityCheck->status] ?? $qualityCheck->status }}</td>
        </tr>
        <tr>
            <th>Proiect</th>
            <td>{{ $qualityCheck->project?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Etapa</th>
            <td>{{ $qualityCheck->phase?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Responsabil</th>
            <td>{{ $qualityCheck->assignee?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Planificat la</th>
            <td>{{ optional($qualityCheck->planned_at)->format('d.m.Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Finalizat la</th>
            <td>{{ optional($qualityCheck->completed_at)->format('d.m.Y H:i') ?? '-' }}</td>
        </tr>
    </table>

    @if($qualityCheck->description)
        <h2>Descriere</h2>
        <div>{{ $qualityCheck->description }}</div>
    @endif

    <h2>Checklist verificare</h2>
    @if(!empty($qualityCheck->checklist))
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Rezultat</th>
                </tr>
            </thead>
            <tbody>
                @foreach($qualityCheck->checklist as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['text'] ?? '-' }}</td>
                        <td>{{ !empty($item['done']) ? 'Bifat' : 'Nebifat' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="muted">Nu exista checklist definit.</div>
    @endif

    <h2>Insight AI</h2>
    <div>
        {{ $aiInsight }}
    </div>

    @if($qualityCheck->notes)
        <h2>Observatii</h2>
        <div class="small">{!! nl2br(e($qualityCheck->notes)) !!}</div>
    @endif
</body>
</html>
