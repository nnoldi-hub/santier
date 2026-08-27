<template>
    <AppLayout title="Facturare & Incasari">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-3xl border border-orange-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-700">Control Stripe</div>
                        <h2 class="mt-2 text-3xl font-black text-slate-900">Facturare & Incasari</h2>
                        <p class="mt-2 max-w-3xl text-sm text-slate-600">Rezumat local al abonamentelor Cashier si al starilor care necesita verificare in Stripe.</p>
                    </div>
                    <Link :href="route('admin.index')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Dashboard Global</Link>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <article v-for="card in metricCards" :key="card.key" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</div>
                    <div class="mt-2 text-3xl font-black" :class="card.tone">{{ card.value }}</div>
                </article>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-lg font-bold text-slate-900">Abonamente sincronizate local</h3>
                    <p class="mt-1 text-sm text-slate-500">Datele sunt informative; modificarea abonamentului ramane in Stripe Customer Portal.</p>
                </div>
                <div v-if="!subscriptions.data.length" class="px-5 py-12 text-center text-sm text-slate-500">Nu exista abonamente inregistrate.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                            <tr>
                                <th class="px-5 py-3 text-left">Firma</th>
                                <th class="px-5 py-3 text-left">Plan</th>
                                <th class="px-5 py-3 text-left">Status Stripe</th>
                                <th class="px-5 py-3 text-left">Perioada</th>
                                <th class="px-5 py-3 text-left">Trial</th>
                                <th class="px-5 py-3 text-left">Sfarsit acces</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="subscription in subscriptions.data" :key="subscription.id" class="hover:bg-slate-50/70">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ subscription.tenant_name || 'Firma necunoscuta' }}</div>
                                    <div class="text-xs text-slate-500">{{ subscription.tenant_slug || '-' }}</div>
                                </td>
                                <td class="px-5 py-4 font-semibold text-slate-700">{{ subscription.plan }}</td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold" :class="statusTone(subscription.status)">{{ subscription.status }}</span></td>
                                <td class="px-5 py-4 text-slate-700">{{ subscription.interval || '-' }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ formatDate(subscription.trial_ends_at) }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ formatDate(subscription.ends_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="subscriptions.last_page > 1" class="flex items-center justify-between border-t border-slate-200 px-5 py-4 text-sm text-slate-600">
                    <span>Pagina {{ subscriptions.current_page }} din {{ subscriptions.last_page }}</span>
                    <div class="flex gap-2">
                        <Link v-if="subscriptions.prev_page_url" :href="subscriptions.prev_page_url" class="rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Inapoi</Link>
                        <Link v-if="subscriptions.next_page_url" :href="subscriptions.next_page_url" class="rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Urmatoarea</Link>
                    </div>
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
    subscriptions: { type: Object, required: true },
    metrics: { type: Object, required: true },
});

const metricCards = computed(() => [
    { key: 'total', label: 'Abonamente totale', value: props.metrics.total || 0, tone: 'text-slate-900' },
    { key: 'active', label: 'Active', value: props.metrics.active || 0, tone: 'text-emerald-700' },
    { key: 'past_due', label: 'Plati restante', value: props.metrics.past_due || 0, tone: 'text-rose-700' },
    { key: 'incomplete', label: 'Necesita finalizare', value: props.metrics.incomplete || 0, tone: 'text-amber-700' },
]);

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString('ro-RO') : '-';
}

function statusTone(status) {
    if (status === 'active' || status === 'trialing') return 'bg-emerald-100 text-emerald-700';
    if (status === 'past_due' || status === 'unpaid') return 'bg-rose-100 text-rose-700';
    return 'bg-amber-100 text-amber-700';
}
</script>
