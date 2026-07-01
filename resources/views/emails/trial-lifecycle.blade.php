<div style="font-family: Arial, sans-serif; color: #111827; line-height: 1.45;">
    <h2 style="margin: 0 0 10px; color: #0f172a;">Salut, {{ $user->name }}!</h2>

    @if($campaignKey === 'welcome')
        <p style="margin: 0 0 12px;">Bine ai venit in <strong>Santier</strong>. Ai activat perioada de trial si poti folosi fluxurile complete pentru proiecte, taskuri, defecte si raportare.</p>
        <ul style="margin: 0 0 14px; padding-left: 18px;">
            <li>Finalizeaza onboarding-ul in 3 pasi.</li>
            <li>Creeaza primul proiect.</li>
            <li>Testeaza exporturile si dashboard-ul.</li>
        </ul>
    @elseif($campaignKey === 'trial_day_3')
        <p style="margin: 0 0 12px;">Au trecut 3 zile de trial. Acum este momentul ideal sa configurezi echipele si responsabilitatile pe proiecte active.</p>
        <ul style="margin: 0 0 14px; padding-left: 18px;">
            <li>Adauga taskuri cu deadline.</li>
            <li>Gestioneaza defectele din snag list.</li>
            <li>Urmareste progresul in dashboard.</li>
        </ul>
    @elseif($campaignKey === 'trial_day_10')
        <p style="margin: 0 0 12px;">Ziua 10 din trial: ai deja suficienta utilizare pentru a evalua impactul asupra operatiunilor.</p>
        <ul style="margin: 0 0 14px; padding-left: 18px;">
            <li>Verifica rapoartele exportate.</li>
            <li>Compara costurile vs buget.</li>
            <li>Pregateste activarea planului potrivit.</li>
        </ul>
    @else
        <p style="margin: 0 0 12px;">Trial-ul tau se apropie de final. Continua fara intreruperi prin activarea unui plan platit.</p>
        <p style="margin: 0 0 14px;">Data expirare trial: <strong>{{ optional($trialEndsAt)->format('Y-m-d') ?? 'N/A' }}</strong></p>
    @endif

    <p style="margin: 0;">Poti gestiona planul direct din sectiunea <strong>Plan si Billing</strong> in aplicatie.</p>
</div>
