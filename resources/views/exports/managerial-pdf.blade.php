<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { border-bottom: 2px solid {{ $branding['brand_color'] ?? '#f97316' }}; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 20px; margin: 0; }
        .meta { color: #6b7280; margin-top: 4px; }
        .section { margin-top: 18px; }
        .section h3 { margin: 0 0 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; font-size: 11px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .small { font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{{ $title }}</h1>
        <div class="meta">Companie: {{ $branding['company_name'] ?? 'Santier' }} | Email: {{ $branding['company_email'] ?? '' }} | Telefon: {{ $branding['company_phone'] ?? '' }}</div>
        <div class="meta">Generat la: {{ $generatedAt }}</div>
        <div class="small">Filtre: {{ json_encode($filters, JSON_UNESCAPED_UNICODE) }}</div>
    </div>

    @foreach($sections as $section)
        <div class="section">
            <h3>{{ $section['name'] }}</h3>
            @php
                $rows = $section['rows'];
            @endphp

            @if(!empty($rows) && count($rows) > 0)
                @php
                    $first = (array) $rows[0];
                    $headers = array_keys($first);
                @endphp
                <table>
                    <thead>
                        <tr>
                            @foreach($headers as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                @foreach($headers as $header)
                                    @php
                                        $value = is_array($row) ? ($row[$header] ?? '') : ($row->$header ?? '');

                                        if (is_array($value)) {
                                            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                                        } elseif (is_object($value)) {
                                            $value = method_exists($value, '__toString')
                                                ? (string) $value
                                                : json_encode((array) $value, JSON_UNESCAPED_UNICODE);
                                        }
                                    @endphp
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="small">Nu exista date pentru aceasta sectiune.</div>
            @endif
        </div>
    @endforeach
</body>
</html>
