<div style="font-family: Arial, sans-serif; color: #111827;">
    <h2 style="margin-bottom: 8px;">Memento zilnic{{ $whiteLabel ?? false ? '' : ' Modulia' }} - {{ $project->name }}</h2>

    <p style="margin: 0 0 12px;">
        @if(!empty($recipientName))
            Buna ziua {{ $recipientName }},
        @else
            Buna ziua,
        @endif
    </p>

    <p style="margin: 0 0 16px;">Iata ce este programat azi ({{ \Illuminate\Support\Carbon::parse($briefing['date'])->format('d.m.Y') }}) pe santierul <strong>{{ $project->name }}</strong>.</p>

    @php
        $riskColors = [
            'green' => ['bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#166534', 'emoji' => '🟢'],
            'orange' => ['bg' => '#fffbeb', 'border' => '#fde68a', 'text' => '#92400e', 'emoji' => '🟠'],
            'red' => ['bg' => '#fef2f2', 'border' => '#fecaca', 'text' => '#b91c1c', 'emoji' => '🔴'],
        ];
        $risk = $riskColors[$briefing['risk_level']] ?? $riskColors['green'];
    @endphp

    <div style="margin: 0 0 16px; padding: 12px 16px; background: {{ $risk['bg'] }}; border: 1px solid {{ $risk['border'] }}; border-radius: 6px; color: {{ $risk['text'] }};">
        <strong>{{ $risk['emoji'] }} {{ $briefing['risk_label'] }}</strong>
        <p style="margin: 4px 0 0;">{{ $briefing['summary'] }}</p>
    </div>

    @if(count($briefing['blockers']))
        <div style="margin: 0 0 16px; padding: 12px 16px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;">
            <strong style="color: #b91c1c;">Blocaje ({{ count($briefing['blockers']) }})</strong>
            <ul style="margin: 8px 0 0; padding-left: 18px; color: #b91c1c;">
                @foreach($briefing['blockers'] as $blocker)
                    <li>{{ $blocker }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(count($briefing['timeline']))
        <div style="margin: 0 0 16px;">
            <strong>Cronologie</strong>
            <ul style="margin: 8px 0 0; padding-left: 18px;">
                @foreach($briefing['timeline'] as $entry)
                    <li style="{{ $entry['blocked'] ? 'color: #b91c1c; font-weight: bold;' : '' }}">
                        {{ $entry['all_day'] ? 'Toata ziua' : $entry['time'] }} - {{ $entry['label'] }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <p style="margin: 0 0 4px;"><strong>Echipe astazi:</strong> {{ count($briefing['teams']) }}</p>
    <p style="margin: 0 0 4px;"><strong>Subcontractori astazi:</strong> {{ count($briefing['subcontractors']) }}</p>
    <p style="margin: 0 0 4px;"><strong>Materiale cu livrare azi:</strong> {{ count($briefing['materials']) }}</p>
    <p style="margin: 0 0 4px;"><strong>Utilaje rezervate azi:</strong> {{ count($briefing['equipment']) }}</p>
    <p style="margin: 0 0 4px;"><strong>Documente cu scadenta azi:</strong> {{ count($briefing['documents']) }}</p>
    <p style="margin: 0 0 16px;"><strong>Task-uri critice azi:</strong> {{ count($briefing['tasks']) }}</p>

    @if(count($briefing['recommendations']))
        <div style="margin: 0 0 16px; padding: 12px 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px;">
            <strong style="color: #92400e;">Recomandari</strong>
            <ul style="margin: 8px 0 0; padding-left: 18px; color: #92400e;">
                @foreach($briefing['recommendations'] as $recommendation)
                    <li>{{ $recommendation['message'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p style="margin: 0 0 16px;">
        <a href="{{ route('daily-briefing.show', $project->id) }}" style="display: inline-block; background: #2563eb; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold;">Deschide agenda zilei in Modulia</a>
    </p>

    @unless($whiteLabel ?? false)
        <p style="margin: 0; color: #6b7280;">Modulia - Șantierul devine clar. · modulia.ro</p>
    @endunless
</div>
