<template>
    <AppLayout title="Afiliati & Campanii">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-3xl border border-orange-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-700">Marketing platforma</div>
                        <h2 class="mt-2 text-3xl font-black text-slate-900">Afiliati & Campanii</h2>
                        <p class="mt-2 max-w-3xl text-sm text-slate-600">Codurile referral sunt atribuite la inregistrare si raman legate de firma recomandata.</p>
                    </div>
                    <Link :href="route('admin.index')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Dashboard Global</Link>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <article v-for="card in metricCards" :key="card.key" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ card.value }}</div>
                </article>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-lg font-bold text-slate-900">Parteneri activi si conversii</h3>
                    <p class="mt-1 text-sm text-slate-500">Linkul poate fi copiat in campanii, parteneriate sau postari publice.</p>
                </div>
                <div v-if="!partners.length" class="px-5 py-12 text-center text-sm text-slate-500">Nu exista parteneri afiliati configurati.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                            <tr>
                                <th class="px-5 py-3 text-left">Partener</th>
                                <th class="px-5 py-3 text-left">Cod</th>
                                <th class="px-5 py-3 text-left">Link referral</th>
                                <th class="px-5 py-3 text-left">Firme atribuite</th>
                                <th class="px-5 py-3 text-left">Platitoare</th>
                                <th class="px-5 py-3 text-left">Comision</th>
                                <th class="px-5 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="partner in partners" :key="partner.id">
                                <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ partner.name }}</div><div class="text-xs text-slate-500">{{ partner.email || '-' }}</div></td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-700">{{ partner.code }}</td>
                                <td class="px-5 py-4"><a :href="partner.referral_url" target="_blank" rel="noopener" class="break-all text-xs font-semibold text-orange-700 hover:underline">{{ partner.referral_url }}</a></td>
                                <td class="px-5 py-4 font-semibold text-slate-700">{{ partner.tenants_count }}</td>
                                <td class="px-5 py-4 font-semibold text-emerald-700">{{ partner.paid_tenants_count }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ partner.commission_rate }}%</td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold" :class="partner.active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">{{ partner.active ? 'Activ' : 'Inactiv' }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    partners: { type: Array, required: true },
    metrics: { type: Object, required: true },
});

const metricCards = computed(() => [
    { key: 'partners_total', label: 'Parteneri total', value: props.metrics.partners_total || 0 },
    { key: 'active_partners', label: 'Parteneri activi', value: props.metrics.active_partners || 0 },
    { key: 'referred_tenants', label: 'Firme atribuite', value: props.metrics.referred_tenants || 0 },
    { key: 'referred_paid_tenants', label: 'Firme platitoare', value: props.metrics.referred_paid_tenants || 0 },
]);
</script>
