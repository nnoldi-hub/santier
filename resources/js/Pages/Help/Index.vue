<template>
    <AppLayout title="Ajutor">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="absolute inset-0 bg-gradient-to-r from-orange-50 via-white to-slate-50"></div>
                <div class="relative p-6 sm:p-8 lg:p-10 grid lg:grid-cols-[1.4fr_0.9fr] gap-8 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-semibold text-[#F57C00]">
                            Ghid pentru utilizatorii noi
                        </div>
                        <h1 class="mt-4 text-3xl sm:text-4xl font-black text-[#1A237E] leading-tight">
                            Afli rapid ce face aplicatia si cum o folosesti la maxim.
                        </h1>
                        <p class="mt-4 max-w-2xl text-slate-600 text-base sm:text-lg">
                            Foloseste aceasta pagina ca punct de pornire atunci cand intri prima data in platforma.
                            Gasesti pasi simpli, exemple practice si raspunsuri scurte la cele mai frecvente intrebari.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link :href="route('dashboard')" class="rounded-xl bg-[#F57C00] px-5 py-3 text-sm font-semibold text-white transition hover:bg-orange-600">
                                Deschide Dashboard
                            </Link>
                            <Link :href="route('projects.create')" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                                Creeaza primul proiect
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-[#1A237E] p-5 text-slate-100 shadow-xl">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-300">Flux rapid</div>
                        <div class="mt-4 space-y-3">
                            <div v-for="step in shortFlow" :key="step.title" class="rounded-xl border border-white/10 bg-white/5 p-4">
                                <div class="text-sm font-semibold">{{ step.title }}</div>
                                <div class="mt-1 text-sm text-slate-300">{{ step.text }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div v-for="step in gettingStartedSteps" :key="step.title" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Primii pasi</div>
                    <h2 class="mt-2 text-lg font-bold text-slate-900">{{ step.title }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ step.text }}</p>
                    <Link :href="step.href" class="mt-4 inline-flex rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">
                        {{ step.cta }}
                    </Link>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Module esentiale</div>
                        <h2 class="mt-2 text-2xl font-black text-[#1A237E]">Unde intra fiecare utilizator nou</h2>
                        <p class="mt-2 text-sm text-slate-600">Fiecare modul are un rol clar si poate fi deschis direct din meniu.</p>
                    </div>
                    <Link :href="route('help.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        Ramai aici pentru exemple
                    </Link>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    <div v-for="module in moduleGuides" :key="module.name" class="rounded-2xl border border-slate-200 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">{{ module.name }}</h3>
                                <p class="mt-2 text-sm text-slate-600">{{ module.summary }}</p>
                            </div>
                            <Link :href="module.route" class="shrink-0 rounded-lg bg-[#1A237E] px-3 py-2 text-xs font-semibold text-white hover:bg-[#141b5c]">
                                Deschide
                            </Link>
                        </div>
                        <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
                            <span class="font-semibold text-slate-900">Exemplu:</span>
                            {{ module.example }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[#F57C00]">Ghiduri Focus</div>
                        <h2 class="mt-2 text-2xl font-black text-[#1A237E]">Ajutor pentru Deviz / Oferta si Configurare Documente</h2>
                        <p class="mt-2 text-sm text-slate-600">Checklist practic pentru ce trebuie completat, verificat si trimis catre client.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div v-for="guide in focusGuides" :key="guide.title" class="rounded-2xl border border-slate-200 p-5">
                        <h3 class="text-base font-bold text-slate-900">{{ guide.title }}</h3>
                        <ul class="mt-3 space-y-2">
                            <li v-for="item in guide.items" :key="item" class="flex gap-2 text-sm text-slate-700">
                                <span class="mt-1 h-1.5 w-1.5 rounded-full bg-[#F57C00]"></span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                        <Link :href="guide.href" class="mt-4 inline-flex rounded-lg bg-[#1A237E] px-4 py-2 text-xs font-semibold text-white hover:bg-[#141b5c]">
                            {{ guide.cta }}
                        </Link>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 xl:grid-cols-3">
                <div v-for="example in practicalExamples" :key="example.title" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Scenariu practic</div>
                    <h2 class="mt-2 text-xl font-black text-slate-900">{{ example.title }}</h2>
                    <ol class="mt-4 space-y-3">
                        <li v-for="(step, index) in example.steps" :key="step" class="flex gap-3 text-sm text-slate-600">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-700">{{ index + 1 }}</span>
                            <span>{{ step }}</span>
                        </li>
                    </ol>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <Link v-for="link in example.links" :key="link.label" :href="link.href" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            {{ link.label }}
                        </Link>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[#F57C00]">Ghiduri interactive</div>
                        <h2 class="mt-2 text-2xl font-black text-[#1A237E]">Checklist operational cu bife</h2>
                        <p class="mt-2 text-sm text-slate-600">Bifeaza pasii pe masura ce configurezi aplicatia. Progresul se actualizeaza instant.</p>
                    </div>
                    <div class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-[#F57C00]">
                        {{ checklistDoneCount }} / {{ checklistItems.length }} completat
                    </div>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    <label
                        v-for="item in checklistItems"
                        :key="item.id"
                        class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 cursor-pointer"
                    >
                        <input v-model="item.done" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-[#F57C00] focus:ring-[#F57C00]" />
                        <div>
                            <div class="text-sm font-semibold text-slate-900" :class="item.done ? 'line-through text-slate-500' : ''">{{ item.title }}</div>
                            <p class="mt-1 text-xs text-slate-600">{{ item.description }}</p>
                        </div>
                    </label>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-[#F57C00]">Mini-tutoriale integrate</div>
                        <h2 class="mt-2 text-2xl font-black text-[#1A237E]">Arata-mi cum fac rapid</h2>
                        <p class="mt-2 text-sm text-slate-600">Tutoriale scurte pentru actiunile cele mai frecvente in Modulia.</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div v-for="tutorial in miniTutorials" :key="tutorial.title" class="rounded-2xl border border-slate-200 p-5">
                        <div class="text-sm font-bold text-slate-900">{{ tutorial.title }}</div>
                        <p class="mt-2 text-sm text-slate-600">{{ tutorial.text }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link :href="tutorial.href" class="rounded-lg bg-[#1A237E] px-3 py-2 text-xs font-semibold text-white hover:bg-[#141b5c]">
                                {{ tutorial.cta }}
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Cum folosesti bine platforma</div>
                    <h2 class="mt-2 text-2xl font-black text-slate-900">Reguli simple care te ajuta in fiecare zi</h2>
                    <div class="mt-5 space-y-4">
                        <div v-for="item in bestPractices" :key="item.title" class="rounded-2xl border border-slate-200 p-4">
                            <div class="font-semibold text-slate-900">{{ item.title }}</div>
                            <p class="mt-1 text-sm text-slate-600">{{ item.text }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-orange-200 bg-gradient-to-br from-orange-50 via-white to-sky-50 p-6 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-500">Intrebari frecvente</div>
                    <h2 class="mt-2 text-2xl font-black text-slate-900">Cand te blochezi, cauta aici</h2>
                    <p class="mt-2 text-sm text-slate-600">Raspunsuri scurte la cele mai comune situatii de inceput.</p>
                    <div class="mt-4">
                        <input
                            v-model="faqQuery"
                            type="text"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 focus:border-orange-400 focus:ring-orange-400"
                            placeholder="Cauta in FAQ (ex: proiect, oferta, raport, roluri...)"
                        />
                    </div>
                    <div class="mt-5 space-y-3">
                        <div v-for="(faq, index) in filteredFaqs" :key="faq.question" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <button
                                type="button"
                                class="w-full flex items-start justify-between gap-4 p-4 text-left hover:bg-slate-50 transition"
                                @click="toggleFaq(index)"
                                :aria-expanded="openFaqIndex === index"
                            >
                                <div>
                                    <div class="font-semibold text-slate-900">{{ faq.question }}</div>
                                    <p v-if="openFaqIndex !== index" class="mt-1 text-xs text-slate-500">Apasa pentru raspuns</p>
                                </div>
                                <span class="mt-0.5 inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition" :class="openFaqIndex === index ? 'rotate-180' : ''">⌄</span>
                            </button>
                            <div v-show="openFaqIndex === index" class="border-t border-slate-200 px-4 pb-4 pt-3">
                                <p class="text-sm text-slate-600">{{ faq.answer }}</p>
                            </div>
                        </div>
                        <div v-if="filteredFaqs.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-600">
                            Nu am gasit rezultate pentru cautarea ta. Incearca alt cuvant cheie sau deschide documentatia externa.
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    gettingStartedSteps: { type: Array, default: () => [] },
    moduleGuides: { type: Array, default: () => [] },
    practicalExamples: { type: Array, default: () => [] },
    focusGuides: { type: Array, default: () => [] },
    faqs: { type: Array, default: () => [] },
});

const shortFlow = [
    { title: 'Intra in Dashboard', text: 'Vezi ce este urgent, ce este blocat si care este incarcarea zilei.' },
    { title: 'Creeaza structura', text: 'Proiect, WBS, echipe, subcontractori si utilaje.' },
    { title: 'Urmareste executia', text: 'Taskuri, defecte, documente si rapoarte de progres.' },
    { title: 'Actioneaza pe risc', text: 'Deschide linkul direct din cardul cu risc sau din calendar.' },
];

const bestPractices = [
    {
        title: 'Lucreaza din Dashboard in fiecare dimineata',
        text: 'Este centrul de comanda: vezi riscul, incarcarea pe zi si alertele care cer actiune imediata.',
    },
    {
        title: 'Pune intotdeauna proiectul si etapa corecta',
        text: 'Asta face ca filtrarea, rapoartele si calendarul sa iti arate contextul real.',
    },
    {
        title: 'Rezolva blocajele in ordinea criticitatii',
        text: 'Defectele, taskurile blocate si documentele neplatite apar primele in zona de atentie.',
    },
    {
        title: 'Verifica riscul predictive inainte sa aloci resurse',
        text: 'Daca utilajele sau subcontractorii sunt supraincarcati, ajustezi planul inainte sa intarzie santierul.',
    },
];

const openFaqIndex = ref(0);
const faqQuery = ref('');

const filteredFaqs = computed(() => {
    const query = faqQuery.value.trim().toLowerCase();
    if (query === '') {
        return props.faqs;
    }

    return props.faqs.filter((faq) => (
        String(faq.question || '').toLowerCase().includes(query)
        || String(faq.answer || '').toLowerCase().includes(query)
    ));
});

const checklistItems = ref([
    {
        id: 'setup-company',
        title: 'Configureaza datele companiei si brandingul documentelor',
        description: 'Completeaza date firma, logo, culori si semnaturi pentru PDF-uri.',
        done: false,
    },
    {
        id: 'create-project',
        title: 'Creeaza primul proiect si structura WBS',
        description: 'Adauga etape, taskuri si responsabili pentru executie controlata.',
        done: false,
    },
    {
        id: 'invite-team',
        title: 'Invita echipa si seteaza roluri/permisiuni',
        description: 'Asigura accesul corect pentru admin, manager, subcontractori si client.',
        done: false,
    },
    {
        id: 'track-costs',
        title: 'Activeaza cost tracking si rapoarte',
        description: 'Urmareste costuri reale, status financiar si exporturi manageriale.',
        done: false,
    },
]);

const checklistDoneCount = computed(() => checklistItems.value.filter((item) => item.done).length);

const miniTutorials = [
    {
        title: 'Arata-mi cum creez un proiect',
        text: 'Start rapid pentru proiect nou cu client, buget, deadline si status initial.',
        href: route('projects.create'),
        cta: 'Deschide wizard proiect',
    },
    {
        title: 'Arata-mi cum fac o oferta',
        text: 'Flux simplu de ofertare: articole, valori, export PDF si trimitere.',
        href: route('quotes.create'),
        cta: 'Creeaza oferta',
    },
    {
        title: 'Arata-mi cum configurez documentele',
        text: 'Seteaza template-uri, branding si elemente obligatorii pentru documente.',
        href: route('documents.branding.index'),
        cta: 'Configureaza documente',
    },
    {
        title: 'Arata-mi cum pregatesc santierul inainte de executie',
        text: 'Deschide un proiect, apasa "Organizare Santier" si completeaza echipe, materiale, utilaje, buget si scorul de pregatire.',
        href: route('projects.index'),
        cta: 'Deschide proiectele',
    },
];

function toggleFaq(index) {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
}
</script>