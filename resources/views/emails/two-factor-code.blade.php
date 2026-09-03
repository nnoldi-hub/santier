<div style="font-family: Arial, sans-serif; color: #111827;">
    <h2 style="margin-bottom: 8px;">Codul tau de autentificare</h2>

    <p style="margin: 0 0 12px;">
        Buna ziua {{ $recipientName }},
    </p>

    <p style="margin: 0 0 16px;">
        Foloseste codul de mai jos pentru a continua autentificarea in contul tau Modulia:
    </p>

    <div style="margin: 0 0 16px; padding: 16px 20px; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; text-align: center;">
        <span style="font-size: 32px; font-weight: 700; letter-spacing: 6px; color: #9a3412;">{{ $code }}</span>
    </div>

    <p style="margin: 0 0 12px;">
        Codul este valabil {{ $expiresInMinutes }} minute. Daca nu tu ai initiat aceasta autentificare, iti recomandam sa iti schimbi parola imediat.
    </p>

    <p style="margin: 0; color: #6b7280;">Modulia - Șantierul devine clar. · modulia.ro</p>
</div>
