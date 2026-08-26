<template>
    <AppLayout :title="`Activitate - ${tenant.name}`">
        <div class="max-w-5xl mx-auto space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            {{ tenant.billing_plan_label }} · {{ tenant.status === 'active' ? 'Activa' : 'Suspendata' }}
                        </div>
                        <h2 class="mt-3 text-2xl font-black text-slate-900">{{ tenant.name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Cont creat: {{ formatDate(tenant.created_at) }} · Final trial: {{ tenant.trial_ends_at ? formatDate(tenant.trial_ends_at) : '-' }}</p>
                    </div>
                    <Link :href="route('admin.tenants.index')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Inapoi la Firme & Abonamente
                    </Link>
                </div>
            </section>

            <section v-if="needsHelpSignals.length" class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Ar putea avea nevoie de ajutor</div>
                <ul class="mt-2 space-y-1 text-sm text-amber-900">
                    <li v-for="(signal, index) in needsHelpSignals" :key="index">- {{ signal }}</li>
                </ul>
            </section>
            <section v-else class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                Firma pare activa si nu are semnale ingrijoratoare in acest moment.
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Utilizatori activi</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ summary.members_count }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ summary.active_in_last_7_days }} conectati in ultimele 7 zile</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Ultima conectare</div>
                    <div class="mt-2 text-xl font-black text-slate-900">
                        <template v-if="summary.last_login_at">
                            {{ formatDateTime(summary.last_login_at) }}
                        </template>
                        <span v-else class="text-slate-400">Niciodata</span>
                    </div>
                    <div v-if="summary.last_login_days_ago !== null" class="mt-1 text-xs text-slate-500">acum {{ summary.last_login_days_ago }} zile</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Proiecte (active / total)</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ summary.active_projects }} / {{ summary.total_projects }}</div>
                    <div v-if="summary.last_project_name" class="mt-1 text-xs text-slate-500 truncate">Ultimul: {{ summary.last_project_name }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Defecte deschise</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ summary.open_defects_count }}</div>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900">Cronologie</h3>
                <div v-if="!milestones.length" class="mt-3 text-sm text-slate-500">Nu exista inca evenimente inregistrate.</div>
                <ol v-else class="mt-4 space-y-3 border-l border-slate-200 pl-4">
                    <li v-for="(item, index) in milestones" :key="index">
                        <div class="text-sm font-semibold text-slate-900">{{ item.label }}</div>
                        <div class="text-xs text-slate-500">{{ formatDateTime(item.at) }}</div>
                    </li>
                </ol>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-lg font-bold text-slate-900">Utilizatori si onboarding</h3>
                    <p class="text-sm text-slate-500">Cine e conectat la firma si daca a terminat pasii de configurare.</p>
                </div>
                <div v-if="!members.length" class="px-5 py-8 text-center text-sm text-slate-500">
                    Firma nu are niciun utilizator activ.
                </div>
                <table v-else class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                        <tr>
                            <th class="px-5 py-3 text-left">Utilizator</th>
                            <th class="px-5 py-3 text-left">Onboarding</th>
                            <th class="px-5 py-3 text-left">Ultima conectare</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="member in members" :key="member.id">
                            <td class="px-5 py-3">
                                <div class="font-semibold text-slate-900">{{ member.name }}</div>
                                <div class="text-xs text-slate-500">{{ member.email }}</div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold" :class="member.onboarding_completed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                    {{ member.onboarding_completed ? 'Finalizat' : 'In lucru' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-700">
                                <template v-if="member.last_login_at">
                                    {{ formatDateTime(member.last_login_at) }}
                                    <span class="block text-xs text-slate-400">acum {{ member.last_login_days_ago }} zile</span>
                                </template>
                                <span v-else class="text-slate-400">Niciodata</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    tenant: Object,
    summary: Object,
    needsHelpSignals: Array,
    members: Array,
    milestones: Array,
});

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('ro-RO');
}

function formatDateTime(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('ro-RO', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>
