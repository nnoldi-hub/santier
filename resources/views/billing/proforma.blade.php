<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Factura proforma</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; }
        .header-band { height: 6px; background: #f97316; margin-bottom: 14px; }
        .title { font-size: 20px; font-weight: 700; margin: 0 0 4px; color: #f97316; }
        .meta { color: #6b7280; font-size: 11px; }
        .parties { width: 100%; margin-top: 18px; }
        .party { width: 48%; vertical-align: top; }
        .party-title { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
        .party-name { font-size: 13px; font-weight: 700; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 22px; }
        .items th, .items td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        .items th { background: #fff7ed; color: #9a3412; font-weight: 700; }
        .text-right { text-align: right; }
        .totals { width: 100%; margin-top: 10px; }
        .totals td { padding: 4px 8px; }
        .totals .label { text-align: right; color: #6b7280; }
        .totals .value { text-align: right; width: 120px; font-weight: 600; }
        .totals .grand-total .label, .totals .grand-total .value { font-size: 15px; font-weight: 700; color: #111827; }
        .discount-badge { display: inline-block; background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; margin-top: 8px; }
        .bank-details { margin-top: 24px; border: 1px solid #e5e7eb; background: #f8fafc; padding: 12px; }
        .bank-details .label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .footer { margin-top: 20px; color: #6b7280; font-size: 10px; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="header-band"></div>
    <div class="title">Factura proforma</div>
    <div class="meta">Numar: PF-{{ str_pad((string) $proformaRequest->id, 5, '0', STR_PAD_LEFT) }} · Data emiterii: {{ $generatedAt->format('d.m.Y') }} · Valabila pana la: {{ $validUntil->format('d.m.Y') }}</div>

    <table class="parties">
        <tr>
            <td class="party">
                <div class="party-title">Furnizor</div>
                <div class="party-name">{{ $issuer['company_name'] }}</div>
                @if(!empty($issuer['company_cui']))<div>CUI: {{ $issuer['company_cui'] }}</div>@endif
                @if(!empty($issuer['company_address']))<div>{{ $issuer['company_address'] }}</div>@endif
                @if(!empty($issuer['support_email']))<div>Email: {{ $issuer['support_email'] }}</div>@endif
                @if(!empty($issuer['company_phone']))<div>Tel: {{ $issuer['company_phone'] }}</div>@endif
            </td>
            <td class="party">
                <div class="party-title">Client</div>
                <div class="party-name">{{ $proformaRequest->company_name }}</div>
                <div>CUI: {{ $proformaRequest->company_cui }}</div>
                @if(!empty($proformaRequest->company_address))<div>{{ $proformaRequest->company_address }}</div>@endif
                <div>Persoana de contact: {{ $proformaRequest->contact_name }}</div>
                <div>Email: {{ $proformaRequest->contact_email }}</div>
                <div>Tel: {{ $proformaRequest->contact_phone }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Produs</th>
                <th class="text-right">Perioada</th>
                <th class="text-right">Pret listat</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Abonament Modulia - {{ $planLabel }}</td>
                <td class="text-right">{{ $proformaRequest->interval === 'yearly' ? '1 an' : '1 luna' }}</td>
                <td class="text-right">{{ number_format($basePrice, 2, ',', '.') }} RON</td>
                <td class="text-right">-{{ $proformaRequest->discount_pct }}%</td>
                <td class="text-right">{{ number_format($discountedPrice, 2, ',', '.') }} RON</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ number_format($basePrice, 2, ',', '.') }} RON</td>
        </tr>
        <tr>
            <td class="label">Discount de lansare ({{ $proformaRequest->discount_pct }}%)</td>
            <td class="value">-{{ number_format($basePrice - $discountedPrice, 2, ',', '.') }} RON</td>
        </tr>
        <tr class="grand-total">
            <td class="label">Total de plata</td>
            <td class="value">{{ number_format($discountedPrice, 2, ',', '.') }} RON</td>
        </tr>
    </table>

    <div class="discount-badge">Discount de lansare {{ $proformaRequest->discount_pct }}% - valabil {{ $validUntil->format('d.m.Y') }}</div>

    <div class="bank-details">
        <div class="label">Plata prin transfer bancar</div>
        <div><strong>{{ $issuer['company_name'] }}</strong></div>
        @if(!empty($issuer['company_iban']))
            <div>IBAN: {{ $issuer['company_iban'] }}</div>
        @else
            <div>IBAN-ul va fi comunicat de echipa de vanzari - contactati {{ $issuer['sales_email'] }}.</div>
        @endif
        <div>Mentioneaza pe ordinul de plata: PF-{{ str_pad((string) $proformaRequest->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="footer">
        Document generat automat de Modulia la {{ $generatedAt->format('d.m.Y H:i') }}. Abonamentul se activeaza in maximum o zi lucratoare de la confirmarea platii.
    </div>
</body>
</html>
