<template>
    <LegalLayout title="Termeni si Conditii - Modulia">
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Acest document este un draft generic si necesita revizuire juridica inainte de publicare oficiala.
        </div>

        <h1 class="mt-6 text-3xl font-black text-slate-900">Termeni si Conditii</h1>
        <p class="mt-2 text-sm text-slate-500">Ultima actualizare: {{ lastUpdated }}</p>

        <div class="mt-8 space-y-8 text-sm leading-6 text-slate-700">
            <section>
                <h2 class="text-lg font-bold text-slate-900">1. Obiect</h2>
                <p class="mt-2">
                    Prezentul document reglementeaza termenii si conditiile de utilizare a platformei Modulia
                    ("Platforma", "Serviciul"), operata de Modulia ("noi", "Compania"), pusa la dispozitia
                    utilizatorilor persoane juridice ("Client", "Utilizator") pentru gestiunea de proiecte de
                    constructii si renovari.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">2. Definitii</h2>
                <ul class="mt-2 space-y-1.5 list-disc pl-5">
                    <li><strong>Cont</strong> - accesul creat pentru un Client si utilizatorii sai autorizati.</li>
                    <li><strong>Trial</strong> - perioada de evaluare gratuita a Platformei.</li>
                    <li><strong>Continut</strong> - orice date, documente sau informatii incarcate de Client in Platforma.</li>
                    <li><strong>Abonament</strong> - planul platit ales de Client pentru utilizarea continua a Platformei.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">3. Crearea si utilizarea contului</h2>
                <p class="mt-2">
                    Clientul este responsabil pentru acuratetea datelor furnizate la inregistrare si pentru
                    pastrarea confidentialitatii credentialelor de acces. Clientul raspunde pentru toate
                    actiunile efectuate prin contul sau, inclusiv de catre utilizatorii pe care ii invita.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">4. Abonamente si plata</h2>
                <p class="mt-2">
                    Planurile de abonament, preturile si perioadele de facturare sunt afisate in Platforma la
                    momentul incheierii contractului. Perioada de trial nu implica nicio obligatie de plata.
                    Continuarea utilizarii dupa incheierea trial-ului presupune alegerea unui plan platit.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">5. Continutul Clientului</h2>
                <p class="mt-2">
                    Clientul ramane unicul proprietar al continutului incarcat in Platforma (proiecte, documente,
                    date financiare). Compania proceseaza acest continut exclusiv in scopul furnizarii Serviciului
                    si nu il utilizeaza in alte scopuri fara acordul explicit al Clientului.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">6. Proprietate intelectuala</h2>
                <p class="mt-2">
                    Platforma, codul sursa, design-ul si marca Modulia raman proprietatea exclusiva a Companiei.
                    Utilizarea Platformei nu confera Clientului niciun drept de proprietate asupra acestora.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">7. Disponibilitate si limitare a raspunderii</h2>
                <p class="mt-2">
                    Compania depune diligente rezonabile pentru a asigura disponibilitatea si functionarea
                    corecta a Platformei, fara a garanta functionare neintrerupta. In limitele permise de lege,
                    raspunderea Companiei este limitata la valoarea abonamentului platit in ultimele 12 luni.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">8. Incetarea contractului</h2>
                <p class="mt-2">
                    Clientul poate inceta utilizarea Platformei oricand, prin dezactivarea contului. Compania
                    poate suspenda sau inchide un cont in caz de neplata sau incalcare a acestor termeni, cu
                    notificare prealabila, exceptand cazurile de incalcare grava.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">9. Lege aplicabila</h2>
                <p class="mt-2">
                    Prezentul document este guvernat de legislatia romana. Orice litigiu se solutioneaza pe cale
                    amiabila sau, in lipsa unui acord, de instantele competente din Romania.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">10. Contact</h2>
                <p class="mt-2">
                    Pentru intrebari legate de acesti termeni, ne poti contacta la
                    <a href="mailto:suport@modulia.ro" class="text-[var(--brand-blue,#0057FF)] hover:underline">suport@modulia.ro</a>.
                </p>
            </section>
        </div>
    </LegalLayout>
</template>

<script setup>
import LegalLayout from '@/Layouts/LegalLayout.vue';

const lastUpdated = '13 iulie 2026';
</script>
