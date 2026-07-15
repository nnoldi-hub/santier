<div style="font-family: Arial, sans-serif; color: #111827;">
    <h2 style="margin-bottom: 8px;">Oferta{{ $whiteLabel ?? false ? '' : ' Modulia' }} pentru proiectul {{ $quote->project?->name ?? 'N/A' }}</h2>

    <p style="margin: 0 0 12px;">
        @if(!empty($recipientName))
            Buna ziua {{ $recipientName }},
        @else
            Buna ziua,
        @endif
    </p>

    <p style="margin: 0 0 12px;">
        Va transmitem oferta <strong>{{ $quote->title }}</strong> (versiunea {{ $quote->version }}), in format PDF, atasata acestui email.
    </p>

    <ul style="margin: 0 0 16px; padding-left: 18px;">
        <li>Valoare neta: {{ number_format((float) $quote->total_net, 2, ',', '.') }} RON</li>
        <li>TVA: {{ number_format((float) $quote->total_tva, 2, ',', '.') }} RON</li>
        <li>Total: {{ number_format((float) $quote->total_gross, 2, ',', '.') }} RON</li>
        <li>Valabila pana la: {{ optional($quote->valid_until)->format('d.m.Y') ?? 'Nespecificat' }}</li>
    </ul>

    @if(!empty($publicUrl))
        <p style="margin: 0 0 16px;">
            <a href="{{ $publicUrl }}" style="display: inline-block; background: #f97316; color: #ffffff; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: bold;">Vezi oferta online</a>
        </p>
    @endif

    @unless($whiteLabel ?? false)
        <p style="margin: 0; color: #6b7280;">Modulia - Șantierul devine clar. · modulia.ro</p>
    @endunless
</div>
