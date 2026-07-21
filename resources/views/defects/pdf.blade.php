<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Raport Defect #{{ $defect->id }}</title>
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
    <h1>Raport PDF Defect</h1>
    <div class="muted">Generat la: {{ now()->format('d.m.Y H:i') }}</div>

    <h2>Detalii defect</h2>
    <table>
        <tr>
            <th>Titlu</th>
            <td>{{ $defect->title }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td>{{ ['open' => 'Deschis', 'in_progress' => 'In progres', 'resolved' => 'Rezolvat', 'rejected' => 'Respins'][$defect->status] ?? $defect->status }}</td>
        </tr>
        <tr>
            <th>Prioritate</th>
            <td>{{ ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'][$defect->priority] ?? $defect->priority }}</td>
        </tr>
        <tr>
            <th>Proiect</th>
            <td>{{ $defect->project?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Etapa</th>
            <td>{{ $defect->phase?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Locatie</th>
            <td>{{ $defect->location ?? '-' }}</td>
        </tr>
        <tr>
            <th>Raportat de</th>
            <td>{{ $defect->reporter?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Responsabil</th>
            <td>{{ $defect->assignee?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Deadline remediere</th>
            <td>{{ optional($defect->due_date)->format('d.m.Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Rezolvat la</th>
            <td>{{ optional($defect->resolved_at)->format('d.m.Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Rezolvat de</th>
            <td>{{ $defect->resolvedBy?->name ?? '-' }}</td>
        </tr>
    </table>

    @if($defect->description)
        <h2>Descriere</h2>
        <div>{{ $defect->description }}</div>
    @endif

    @if($defect->resolution_notes)
        <h2>Note rezolvare</h2>
        <div class="small">{!! nl2br(e($defect->resolution_notes)) !!}</div>
    @endif

    <h2>Poze ({{ $defect->photos->count() }})</h2>
    @if($defect->photos->isNotEmpty())
        <div class="small">
            @foreach($defect->photos as $photo)
                {{ $photo->name }}@if(!$loop->last), @endif
            @endforeach
        </div>
    @else
        <div class="muted">Nu exista poze incarcate.</div>
    @endif

    @if($defect->signature_path)
        <h2>Semnatura</h2>
        <img src="{{ storage_path('app/public/' . $defect->signature_path) }}" style="max-width: 240px; max-height: 100px;">
        <div class="small muted">Semnat de {{ $defect->signed_by_name ?? '-' }} la {{ optional($defect->signed_at)->format('d.m.Y H:i') ?? '-' }}</div>
    @endif
</body>
</html>
