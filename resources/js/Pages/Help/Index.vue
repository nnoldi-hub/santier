<template>
    <AppLayout title="Ajutor">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="absolute inset-0 bg-gradient-to-r from-orange-50 via-white to-blue-50"></div>
                <div class="relative p-6 sm:p-8 lg:p-10 grid lg:grid-cols-[1.4fr_0.9fr] gap-8 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">
                            Ghid pentru utilizatorii noi
                        </div>
                        <h1 class="mt-4 text-3xl sm:text-4xl font-black text-slate-900 leading-tight">
                            Afli rapid ce face aplicatia si cum o folosesti la maxim.
                        </h1>
                        <p class="mt-4 max-w-2xl text-slate-600 text-base sm:text-lg">
                            Foloseste aceasta pagina ca punct de pornire atunci cand intri prima data in platforma.
                            Gasesti pasi simpli, exemple practice si raspunsuri scurte la cele mai frecvente intrebari.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link :href="route('dashboard')" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Deschide Dashboard
                            </Link>
                            <Link :href="route('projects.create')" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">
                                Creeaza primul proiect
                            </Link>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-950 p-5 text-slate-100 shadow-xl">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Flux rapid</div>
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
                        <h2 class="mt-2 text-2xl font-black text-slate-900">Unde intra fiecare utilizator nou</h2>
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
                            <Link :href="module.route" class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
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
                    <div class="mt-5 space-y-3">
                        <div v-for="(faq, index) in faqs" :key="faq.question" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
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
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    gettingStartedSteps: { type: Array, default: () => [] },
    moduleGuides: { type: Array, default: () => [] },
    practicalExamples: { type: Array, default: () => [] },
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

function toggleFaq(index) {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
}
</script>