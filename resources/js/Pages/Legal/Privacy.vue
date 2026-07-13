<template>
    <LegalLayout title="Confidentialitate (GDPR) - Modulia">
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Acest document este un draft generic si necesita revizuire juridica inainte de publicare oficiala.
        </div>

        <h1 class="mt-6 text-3xl font-black text-slate-900">Politica de confidentialitate (GDPR)</h1>
        <p class="mt-2 text-sm text-slate-500">Ultima actualizare: {{ lastUpdated }}</p>

        <div class="mt-8 space-y-8 text-sm leading-6 text-slate-700">
            <section>
                <h2 class="text-lg font-bold text-slate-900">1. Operator de date</h2>
                <p class="mt-2">
                    Modulia actioneaza in calitate de operator de date pentru informatiile despre contul si
                    utilizatorii Clientului, si in calitate de persoana imputernicita pentru datele incarcate
                    de Client in Platforma (ex: date din proiecte, documente financiare).
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">2. Ce date colectam</h2>
                <ul class="mt-2 space-y-1.5 list-disc pl-5">
                    <li>Date de identificare si contact: nume, email, telefon, companie.</li>
                    <li>Date de utilizare a Platformei: actiuni in cont, jurnal de acces, preferinte.</li>
                    <li>Date tehnice: adresa IP, tip dispozitiv/browser, cookie-uri.</li>
                    <li>Continutul incarcat de Client (proiecte, documente, date financiare) - procesat doar in numele Clientului.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">3. Scopurile prelucrarii</h2>
                <p class="mt-2">
                    Datele sunt prelucrate pentru: furnizarea si mentenanta Serviciului, gestionarea contului si
                    facturarii, comunicare cu privire la Serviciu, imbunatatirea Platformei si respectarea
                    obligatiilor legale.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">4. Temei legal</h2>
                <p class="mt-2">
                    Prelucrarea se bazeaza pe executarea contractului dintre Client si Modulia, pe interesul
                    legitim al Companiei (securitate, prevenirea fraudei, imbunatatirea produsului) si, unde
                    este cazul, pe consimtamant (ex: comunicari de marketing optionale).
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">5. Perioada de retentie</h2>
                <p class="mt-2">
                    Datele contului sunt pastrate pe durata relatiei contractuale si ulterior, atat cat este
                    necesar pentru respectarea obligatiilor legale (ex: fiscale, contabile) sau pentru
                    solutionarea eventualelor litigii.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">6. Drepturile persoanei vizate</h2>
                <p class="mt-2">In baza Regulamentului GDPR, aveti dreptul de a solicita:</p>
                <ul class="mt-2 space-y-1.5 list-disc pl-5">
                    <li>Acces la datele personale prelucrate.</li>
                    <li>Rectificarea datelor inexacte sau incomplete.</li>
                    <li>Stergerea datelor ("dreptul de a fi uitat"), in conditiile legii.</li>
                    <li>Portabilitatea datelor catre un alt furnizor.</li>
                    <li>Restrictionarea sau opozitia la anumite prelucrari.</li>
                    <li>Retragerea consimtamantului, acolo unde prelucrarea se bazeaza pe acesta.</li>
                </ul>
                <p class="mt-2">
                    Aceste drepturi pot fi exercitate prin contactarea noastra la adresa de mai jos. Aveti de
                    asemenea dreptul de a depune o plangere la Autoritatea Nationala de Supraveghere a
                    Prelucrarii Datelor cu Caracter Personal (ANSPDCP).
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">7. Cookie-uri</h2>
                <p class="mt-2">
                    Platforma foloseste cookie-uri esentiale pentru functionare (autentificare, sesiune) si,
                    optional, cookie-uri de analiza pentru a intelege modul de utilizare a paginii publice.
                    Cookie-urile de analiza pot fi dezactivate din setarile browser-ului.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">8. Securitatea datelor</h2>
                <p class="mt-2">
                    Aplicam masuri tehnice si organizatorice rezonabile (criptare in tranzit, control al
                    accesului bazat pe roluri, izolare a datelor per tenant) pentru a proteja datele impotriva
                    accesului neautorizat, pierderii sau divulgarii.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-slate-900">9. Contact</h2>
                <p class="mt-2">
                    Pentru orice solicitare privind datele personale, ne poti contacta la
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
