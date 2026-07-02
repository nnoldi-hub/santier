<template>
    <Head :title="`${appName} - Management de santier`" />

    <div class="landing min-h-screen text-slate-800">
        <div class="noise-layer"></div>

        <header class="max-w-6xl mx-auto px-4 sm:px-6 pt-6 relative z-10">
            <div class="bg-white/75 backdrop-blur border border-slate-200 rounded-2xl px-4 sm:px-6 py-3 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-[var(--brand-orange)]/15 flex items-center justify-center text-lg">🏗️</div>
                    <div>
                        <div class="text-sm tracking-wide text-slate-500">Platforma SaaS</div>
                        <div class="font-semibold text-slate-900">{{ appName }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium hover:bg-slate-50 transition"
                    >
                        Login
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="px-4 py-2 rounded-lg bg-[var(--brand-orange)] text-white text-sm font-semibold hover:brightness-95 transition"
                    >
                        Trial {{ trialDays }} zile
                    </Link>
                </div>
            </div>
        </header>

        <main class="relative z-10">
            <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 sm:pt-16">
                <div class="grid lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/70 border border-slate-200 text-xs font-semibold text-slate-600 mb-5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Lansare pilot activa
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black leading-tight text-slate-900">
                            Management de santier
                            <span class="text-[var(--brand-orange)]">simplu, modern</span>
                            si masurabil.
                        </h1>

                        <p class="mt-5 text-lg text-slate-600 max-w-xl">
                            {{ appName }} te ajuta sa planifici proiecte, sa coordonezi echipe, sa inchizi defecte si sa livrezi rapoarte executive intr-o singura platforma.
                        </p>

                        <div class="mt-7 flex flex-wrap gap-3">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-6 py-3 rounded-xl bg-[var(--brand-blue)] text-white font-semibold shadow-lg shadow-blue-500/20 hover:-translate-y-0.5 transition"
                            >
                                Incearca gratuit {{ trialDays }} zile
                            </Link>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="px-6 py-3 rounded-xl bg-white border border-slate-300 font-semibold hover:bg-slate-50 transition"
                            >
                                Vezi demo live
                            </Link>
                        </div>

                        <div class="mt-8 grid grid-cols-3 gap-3 max-w-md">
                            <div class="bg-white/80 border border-slate-200 rounded-xl p-3">
                                <div class="text-xl font-black text-slate-900">+35%</div>
                                <div class="text-xs text-slate-500">taskuri inchise la timp</div>
                            </div>
                            <div class="bg-white/80 border border-slate-200 rounded-xl p-3">
                                <div class="text-xl font-black text-slate-900">-22%</div>
                                <div class="text-xs text-slate-500">rework pe defecte</div>
                            </div>
                            <div class="bg-white/80 border border-slate-200 rounded-xl p-3">
                                <div class="text-xl font-black text-slate-900">1 click</div>
                                <div class="text-xs text-slate-500">raport managerial</div>
                            </div>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute -inset-4 bg-gradient-to-br from-orange-300/20 via-blue-300/20 to-cyan-200/20 blur-2xl rounded-3xl"></div>
                        <div class="relative rounded-3xl border border-slate-200 bg-white/85 backdrop-blur p-5 sm:p-6 shadow-xl">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="text-xs uppercase tracking-wide text-slate-500">Demo live</div>
                                    <div class="font-semibold">Santier Control Panel</div>
                                </div>
                                <div class="text-xs px-2 py-1 rounded-md bg-emerald-100 text-emerald-700">Online</div>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-xl border border-slate-200 p-3 flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold">Renovare cladire birouri</div>
                                        <div class="text-xs text-slate-500">Deadline: 18 iulie</div>
                                    </div>
                                    <div class="text-xs px-2 py-1 rounded-md bg-amber-100 text-amber-700">In progress</div>
                                </div>
                                <div class="rounded-xl border border-slate-200 p-3">
                                    <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                                        <span>Progres etapa finisaje</span>
                                        <span>72%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full w-[72%] bg-gradient-to-r from-[var(--brand-orange)] to-[var(--brand-blue)]"></div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl border border-slate-200 p-3">
                                        <div class="text-xs text-slate-500">Defecte deschise</div>
                                        <div class="text-2xl font-black">12</div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 p-3">
                                        <div class="text-xs text-slate-500">Cost vs buget</div>
                                        <div class="text-2xl font-black">96%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Probleme reale pe care le rezolvi din prima saptamana</h2>
                <div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="item in painPoints" :key="item.title" class="bg-white border border-slate-200 rounded-2xl p-5">
                        <div class="text-2xl">{{ item.icon }}</div>
                        <div class="mt-3 font-semibold">{{ item.title }}</div>
                        <p class="mt-1 text-sm text-slate-600">{{ item.text }}</p>
                    </div>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Suita Santier intr-un singur flux</h2>
                <div class="mt-6 grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <div v-for="feature in features" :key="feature.title" class="feature-card rounded-2xl p-5 text-white">
                        <div class="text-2xl">{{ feature.icon }}</div>
                        <div class="mt-3 font-semibold text-lg">{{ feature.title }}</div>
                        <p class="mt-1 text-sm text-slate-100/90">{{ feature.text }}</p>
                    </div>
                </div>
            </section>

            <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Preturi clare pentru fiecare etapa</h2>
                <div class="mt-6 grid md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div v-for="plan in plans" :key="plan.key" class="rounded-2xl border p-5 bg-white" :class="plan.highlight ? 'border-[var(--brand-orange)] shadow-lg shadow-orange-300/20' : 'border-slate-200'">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-lg">{{ plan.name }}</h3>
                            <span class="text-xs px-2 py-1 rounded" :class="plan.highlight ? 'bg-orange-100 text-orange-700' : 'bg-slate-100 text-slate-600'">{{ plan.badge }}</span>
                        </div>
                        <div class="mt-2 text-3xl font-black text-slate-900">{{ formatPrice(plan.price) }}</div>
                        <div class="text-xs text-slate-500">{{ plan.period }}</div>
                        <ul class="mt-4 space-y-2 text-sm text-slate-600">
                            <li v-for="item in plan.items" :key="item">• {{ item }}</li>
                        </ul>
                        <div class="mt-5">
                            <a
                                v-if="plan.key === 'enterprise'"
                                href="#solicita-demo"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition"
                            >
                                {{ plan.cta_label }}
                            </a>
                            <Link
                                v-else-if="canRegister"
                                :href="route('register')"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition"
                            >
                                {{ plan.cta_label }}
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section id="solicita-demo" class="max-w-6xl mx-auto px-4 sm:px-6 mt-16 sm:mt-20 pb-20">
                <div class="grid lg:grid-cols-[1.15fr_0.85fr] gap-6 items-stretch">
                    <div class="rounded-3xl border border-slate-200 bg-gradient-to-r from-white via-orange-50 to-blue-50 p-8 sm:p-10">
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Vrei sa vezi daca se potriveste firmei tale?</h2>
                    <p class="mt-2 text-slate-600 max-w-2xl">Pornesti in 10 minute cu wizard-ul de onboarding, un proiect demo si raport exportabil pentru management.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-6 py-3 rounded-xl bg-[var(--brand-orange)] text-white font-semibold hover:brightness-95 transition"
                        >
                            Creeaza cont trial
                        </Link>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="px-6 py-3 rounded-xl border border-slate-300 bg-white font-semibold hover:bg-slate-50 transition"
                        >
                            Intra in platforma
                        </Link>
                    </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-8 sm:p-10 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs uppercase tracking-wide text-slate-500">Solicita demo</div>
                                <h3 class="mt-2 text-xl font-black text-slate-900">Trimite datele si te contactam</h3>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-[var(--brand-orange)]/10 flex items-center justify-center text-xl">✉️</div>
                        </div>

                        <form class="mt-6 space-y-3" @submit.prevent="submitDemoRequest">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Companie</label>
                                <input v-model="demoForm.company_name" type="text" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Nume companie" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Persoana de contact</label>
                                <input v-model="demoForm.contact_name" type="text" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Nume si prenume" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                                <input v-model="demoForm.contact_email" type="email" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="email@firma.ro" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Telefon</label>
                                <input v-model="demoForm.contact_phone" type="text" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="07xx xxx xxx" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Numar utilizatori estimat</label>
                                <input v-model="demoForm.estimated_users" type="number" min="1" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Ex: 25" required />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Tip personalizare dorita</label>
                                <select v-model="demoForm.customization_scope" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" required>
                                    <option value="" disabled>Selecteaza o optiune</option>
                                    <option value="branding">Branding documente</option>
                                    <option value="template">Template documente</option>
                                    <option value="approvals">Flux aprobari</option>
                                    <option value="white_label">White-label</option>
                                    <option value="custom_domain">Domeniu propriu</option>
                                    <option value="full_enterprise">Pachet enterprise complet</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Ce vrei sa vezi?</label>
                                <textarea v-model="demoForm.notes" rows="3" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Ex: 5 proiecte, 2 echipe, raportare si calendar"></textarea>
                            </div>
                            <button :disabled="demoForm.processing" class="w-full rounded-xl bg-[var(--brand-blue)] px-4 py-3 text-white font-semibold hover:brightness-95 disabled:opacity-60 transition">
                                {{ demoForm.processing ? 'Se trimite...' : 'Trimite solicitarea' }}
                            </button>
                            <p v-if="demoForm.recentlySuccessful" class="text-sm font-medium text-emerald-700">Solicitarea a fost trimisa. Revenim cu un raspuns.</p>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-slate-200 bg-white/70">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-5 flex flex-wrap gap-3 items-center justify-between text-sm text-slate-600">
                <div>{{ companyName }} SaaS - management proiecte constructii</div>
                <div class="flex items-center gap-4">
                    <span v-if="supportEmail">Suport: {{ supportEmail }}</span>
                    <span v-if="salesEmail">Vanzari: {{ salesEmail }}</span>
                    <span>Documentatie</span>
                    <span>GDPR</span>
                    <span>Termeni</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';

defineProps({
    appName: { type: String, default: 'Santier' },
    companyName: { type: String, default: 'Santier' },
    trialDays: { type: Number, default: 14 },
    supportEmail: { type: String, default: '' },
    salesEmail: { type: String, default: '' },
    plans: { type: Array, default: () => [] },
    canLogin: Boolean,
    canRegister: Boolean,
});

const painPoints = [
    { icon: '🧩', title: 'Taskuri pierdute', text: 'Ai un backlog clar pe proiect, responsabil si deadline, fara mesaje imprastiate.' },
    { icon: '⏱️', title: 'Etape intarziate', text: 'Monitorizezi progresul zilnic si intervii inainte sa intarzie santierul.' },
    { icon: '🔍', title: 'Lipsa vizibilitate', text: 'Dashboard unificat pentru status, cost, defecte si decizii operative.' },
    { icon: '🛠️', title: 'Defecte deschise', text: 'Snag list cu prioritati, asignare si due date pana la rezolvare.' },
    { icon: '📁', title: 'Oferte dispersate', text: 'Versionare devize si istoric centralizat, usor de comparat.' },
    { icon: '📤', title: 'Rapoarte lente', text: 'Export XLSX/PDF managerial cu filtre avansate in cateva secunde.' },
];

const features = [
    { icon: '📅', title: 'Gantt modern', text: 'Vizibilitate pe etape, dependente, progres si riscuri operative.' },
    { icon: '✅', title: 'Taskuri cu responsabil', text: 'Flux clar: creare, asignare, prioritate, deadline si inchidere.' },
    { icon: '👷', title: 'Echipe si resurse', text: 'Organizezi echipe, specialitati si alocari pe faze de proiect.' },
    { icon: '📦', title: 'Materiale', text: 'Catalog unificat cu coduri, furnizori si control de costuri.' },
    { icon: '📋', title: 'Oferte si devize', text: 'Calcule rapide si comparatie versiuni pentru decizii comerciale.' },
    { icon: '📊', title: 'Export enterprise', text: 'Workbook multi-sheet, PDF managerial, audit si subscriptions.' },
];

function formatPrice(price) {
    const numericPrice = Number(price || 0);

    if (numericPrice === 0) {
        return '0 lei';
    }

    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'RON',
        maximumFractionDigits: 0,
    }).format(numericPrice);
}

const demoForm = useForm({
    company_name: '',
    segment: 'renovari',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    estimated_users: '',
    customization_scope: '',
    notes: '',
});

function submitDemoRequest() {
    demoForm.post(route('demo-request.store'), {
        preserveScroll: true,
        onSuccess: () => demoForm.reset('contact_name', 'contact_email', 'contact_phone', 'estimated_users', 'customization_scope', 'notes'),
    });
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Manrope:wght@500;700;800&display=swap');

.landing {
    --brand-orange: #f57c00;
    --brand-blue: #1565c0;
    font-family: 'Space Grotesk', sans-serif;
    background:
        radial-gradient(80rem 40rem at 95% -10%, rgba(21, 101, 192, 0.18), transparent 65%),
        radial-gradient(70rem 35rem at -5% 5%, rgba(245, 124, 0, 0.2), transparent 60%),
        linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
}

.noise-layer {
    position: fixed;
    inset: 0;
    pointer-events: none;
    opacity: 0.05;
    background-image: radial-gradient(#1f2937 0.5px, transparent 0.5px);
    background-size: 5px 5px;
}

h1,
h2,
h3 {
    font-family: 'Manrope', sans-serif;
}

.feature-card {
    background: linear-gradient(140deg, #0f172a 0%, #1e3a8a 55%, #0f766e 100%);
    border: 1px solid rgba(255, 255, 255, 0.12);
}
</style>
