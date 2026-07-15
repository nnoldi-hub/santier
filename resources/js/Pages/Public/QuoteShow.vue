<template>
    <Head :title="quote.title" />
    <div class="min-h-screen bg-slate-50 py-8 px-4">
        <div class="max-w-3xl mx-auto space-y-5">
            <div class="rounded-2xl border-t-4 bg-white p-6 shadow-sm" :style="{ borderColor: brandColor }">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <img v-if="branding.document_logo_url" :src="branding.document_logo_url" :alt="branding.company_name" class="h-12 w-auto object-contain mb-3" />
                        <h1 class="text-xl font-bold text-slate-900">{{ quote.title }}</h1>
                        <div class="mt-1 text-sm text-slate-500">Oferta #{{ quote.id }} · Versiunea {{ quote.version }}</div>
                    </div>
                    <div class="text-right text-sm text-slate-600">
                        <div class="font-semibold text-slate-900">{{ branding.company_name }}</div>
                        <div v-if="branding.company_address">{{ branding.company_address }}</div>
                        <div v-if="branding.company_phone">Tel: {{ branding.company_phone }}</div>
                        <div v-if="branding.support_email">{{ branding.support_email }}</div>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">Status: {{ quote.status?.toUpperCase() }}</span>
                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">Pachet: {{ packageTier }}</span>
                </div>

                <div v-if="isExpired" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    Oferta a expirat pe {{ formatDate(quote.valid_until) }}. Contacteaza-ne pentru o oferta actualizata.
                </div>
            </div>

            <div class="rounded-2xl border bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Sumar costuri</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500 uppercase">Total de plata</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ formatMoney(quote.total_gross) }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500 uppercase">Timeline estimat</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ timelineDays > 0 ? timelineDays + ' zile' : 'Nespecificat' }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500 uppercase">Valabila pana la</div>
                        <div class="mt-1 text-lg font-bold text-slate-900">{{ quote.valid_until ? formatDate(quote.valid_until) : 'Nespecificat' }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs text-slate-500 uppercase">Emisa de</div>
                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ documentIssuer || quote.creator?.name || '-' }}</div>
                    </div>
                </div>
            </div>

            <div v-if="stageRows.length" class="rounded-2xl border bg-white p-6 shadow-sm overflow-x-auto">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Etape oferta</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase text-slate-500 border-b">
                            <th class="py-2 pr-2">Etapa</th>
                            <th class="py-2 pr-2 text-right">Total</th>
                            <th class="py-2 pr-2 text-right">Zile</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in stageRows" :key="row.name" class="border-b last:border-0">
                            <td class="py-2 pr-2">{{ row.name }}</td>
                            <td class="py-2 pr-2 text-right">{{ formatMoney(row.total) }}</td>
                            <td class="py-2 pr-2 text-right">{{ formatNumber(row.days) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="notes" class="rounded-2xl border bg-white p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-2">Note & conditii</h2>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ notes }}</p>
            </div>

            <div class="rounded-2xl border bg-white p-6 shadow-sm overflow-x-auto">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Total ofertare</h2>
                <table class="w-full text-sm">
                    <tbody>
                        <tr class="border-b"><td class="py-2">Subtotal materiale</td><td class="py-2 text-right">{{ formatMoney(materialsTotal) }}</td></tr>
                        <tr class="border-b"><td class="py-2">Subtotal manopera</td><td class="py-2 text-right">{{ formatMoney(laborTotal) }}</td></tr>
                        <tr class="border-b"><td class="py-2">Subtotal utilaje</td><td class="py-2 text-right">{{ formatMoney(equipmentTotal) }}</td></tr>
                        <tr class="border-b"><td class="py-2">Total net</td><td class="py-2 text-right">{{ formatMoney(quote.total_net) }}</td></tr>
                        <tr class="border-b"><td class="py-2">TVA ({{ formatNumber(quote.tva_pct) }}%)</td><td class="py-2 text-right">{{ formatMoney(quote.total_tva) }}</td></tr>
                        <tr class="font-bold"><td class="py-2">Total de plata</td><td class="py-2 text-right">{{ formatMoney(quote.total_gross) }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!branding.white_label" class="text-center text-xs text-slate-400 pt-2">
                modulia.ro · © 2026 Modulia
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    quote: { type: Object, required: true },
    branding: { type: Object, required: true },
    stageRows: { type: Array, default: () => [] },
    timelineDays: { type: Number, default: 0 },
    packageTier: { type: String, default: '' },
    documentIssuer: { type: String, default: '' },
    materialsTotal: { type: Number, default: 0 },
    laborTotal: { type: Number, default: 0 },
    equipmentTotal: { type: Number, default: 0 },
    notes: { type: String, default: null },
});

const brandColor = computed(() => props.branding?.document_brand_color || '#f97316');

const isExpired = computed(() => {
    if (!props.quote.valid_until) {
        return false;
    }
    return new Date(props.quote.valid_until) < new Date(new Date().toDateString());
});

function formatMoney(value) {
    return new Intl.NumberFormat('ro-RO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value || 0)) + ' RON';
}

function formatNumber(value) {
    return new Intl.NumberFormat('ro-RO', { maximumFractionDigits: 2 }).format(Number(value || 0));
}

function formatDate(value) {
    if (!value) {
        return '-';
    }
    return new Intl.DateTimeFormat('ro-RO').format(new Date(value));
}
</script>
