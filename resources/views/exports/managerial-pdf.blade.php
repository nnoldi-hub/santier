<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .header { border-bottom: 2px solid {{ $branding['brand_color'] ?? '#f97316' }}; padding-bottom: 10px; margin-bottom: 14px; }
        .brand-row { display: flex; justify-content: space-between; gap: 16px; align-items: flex-start; }
        .brand-logo { max-height: 48px; max-width: 170px; object-fit: contain; margin-bottom: 8px; }
        .title { font-size: 20px; margin: 0; }
        .meta { color: #6b7280; margin-top: 4px; }
        .section { margin-top: 18px; }
        .section h3 { margin: 0 0 8px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; font-size: 11px; vertical-align: top; }
        th { background: #f3f4f6; text-align: left; }
        .small { font-size: 10px; color: #6b7280; }
        .charts { margin-bottom: 12px; }
        .chart-block { margin-bottom: 10px; }
        .chart-title { font-size: 11px; font-weight: bold; margin-bottom: 4px; }
        .chart-row { margin-bottom: 4px; }
        .chart-label { font-size: 9px; color: #475569; margin-bottom: 2px; }
        .chart-bar-bg { width: 100%; height: 7px; background: #e5e7eb; border-radius: 999px; }
        .chart-bar { height: 7px; background: {{ $branding['brand_color'] ?? '#f97316' }}; border-radius: 999px; }
    </style>
</head>
<body>
    @php
        $whiteLabel = $branding['white_label'] ?? false;
        $fallbackLogo = public_path('brand/logo_modulia.png');
        $logoSource = !empty($branding['document_logo_url'])
            ? $branding['document_logo_url']
            : (!$whiteLabel && file_exists($fallbackLogo) ? $fallbackLogo : null);
    @endphp
    <div class="header">
        <div class="brand-row">
            <div>
                @if(!empty($logoSource))
                    <img class="brand-logo" src="{{ $logoSource }}" alt="{{ $branding['company_name'] ?? '' }} logo">
                @endif
                @unless($whiteLabel)
                    <div class="small" style="margin-bottom: 4px;">Șantierul devine clar.</div>
                @endunless
                <h1 class="title">{{ $title }}</h1>
                <div class="meta">Generat la: {{ $generatedAt }}</div>
            </div>
            <div class="meta" style="text-align:right;">
                <div><strong>{{ $branding['company_name'] ?? '' }}</strong></div>
                @if(!empty($branding['company_address']))<div>{{ $branding['company_address'] }}</div>@endif
                @if(!empty($branding['company_email']))<div>Email: {{ $branding['company_email'] }}</div>@endif
                @if(!empty($branding['company_phone']))<div>Telefon: {{ $branding['company_phone'] }}</div>@endif
            </div>
        </div>
        <div class="small">Filtre: {{ json_encode($filters, JSON_UNESCAPED_UNICODE) }}</div>
        @unless($whiteLabel)
            <div class="small" style="margin-top: 6px;">modulia.ro · © 2026 Modulia</div>
        @endunless
    </div>

    @foreach($sections as $section)
        <div class="section">
            <h3>{{ $section['name'] }}</h3>
            @php
                $rows = $section['rows'];
                $charts = $section['charts'] ?? [];
            @endphp

            @if(!empty($charts))
                <div class="charts">
                    @foreach($charts as $chart)
                        @php
                            $max = !empty($chart['series']) ? max($chart['series']) : 0;
                        @endphp
                        <div class="chart-block">
                            <div class="chart-title">{{ $chart['title'] }}</div>
                            @foreach($chart['labels'] as $index => $label)
                                @php
                                    $value = $chart['series'][$index] ?? 0;
                                    $width = $max > 0 ? round(($value / $max) * 100, 2) : 0;
                                @endphp
                                <div class="chart-row">
                                    <div class="chart-label">{{ $label }} ({{ $value }})</div>
                                    <div class="chart-bar-bg">
                                        <div class="chart-bar" style="width: {{ $width }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endif

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
