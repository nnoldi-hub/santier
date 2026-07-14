<div style="font-family: Arial, sans-serif; color: #111827; line-height: 1.45;">
    <h2 style="margin: 0 0 10px; color: #0f172a;">Buna{{ $invite->contact_name ? ', ' . $invite->contact_name : '' }}!</h2>

    <p style="margin: 0 0 12px;">
        Va scriu din partea <strong>Modulia</strong>, o platforma moderna de management de santier, pentru
        firma <strong>{{ $invite->company_name }}</strong>.
    </p>

    <p style="margin: 0 0 12px;">
        Cu Modulia, proiectele devin clare, ordonate si usor de controlat: proiecte si etape, taskuri si
        defecte, echipe si utilaje, financiar si rapoarte - totul intr-un singur loc.
    </p>

    <ul style="margin: 0 0 14px; padding-left: 18px;">
        <li>+35% taskuri inchise la timp</li>
        <li>-22% rework pe defecte</li>
        <li>Raport managerial la 1 click</li>
    </ul>

    <p style="margin: 0 0 16px;">
        Va invitam sa incercati Modulia intr-un pilot fara obligatii - configuram impreuna un proiect demo
        pe masura firmei dumneavoastra.
    </p>

    <p style="margin: 0 0 16px;">
        <a href="{{ $demoUrl }}" style="display: inline-block; background: #F57C00; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: bold;">
            Programeaza un demo
        </a>
    </p>

    <p style="margin: 0 0 4px;">Cu stima,</p>
    <p style="margin: 0 0 16px;"><strong>{{ $senderName }}</strong><br />Echipa Modulia</p>

    <p style="margin: 8px 0 0; color: #6b7280;">Modulia - Șantierul devine clar. · modulia.ro</p>
</div>
