<template>
    <AppLayout title="Facturi materiale">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Facturi materiale</h2>
                <p class="text-sm text-gray-500 mt-1">{{ pluralize(invoices.total, 'factura inregistrata', 'facturi inregistrate') }}</p>
            </div>
            <Link :href="route('material-invoices.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Factura materiale
            </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Total facturi</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.total_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Expunere neplatita</div>
                <div class="text-xl font-semibold text-red-600">{{ formatCurrency(summary.unpaid_exposure) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Total platit</div>
                <div class="text-xl font-semibold text-green-600">{{ formatCurrency(summary.paid_total) }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm text-gray-800">Prioritate acum</h3>
                <span class="text-xs text-gray-500">Actiuni recomandate pentru control financiar</span>
            </div>

            <div v-if="priorityInvoice" class="rounded-xl border p-3 mb-3" :class="attentionToneClass(priorityInvoice.tone)">
                <div class="text-xs uppercase tracking-wider opacity-80 mb-1">Factura prioritara</div>
                <div class="font-semibold text-sm">{{ priorityInvoice.title }}</div>
                <div class="text-xs mt-1 opacity-90">
                    {{ priorityInvoice.project }}
                    <span v-if="priorityInvoice.supplier"> · {{ priorityInvoice.supplier }}</span>
                </div>
                <div class="text-sm mt-2">{{ priorityInvoice.reason }}</div>
            </div>

            <div v-if="attentionItems.length === 0" class="text-sm text-gray-500">
                Nu exista alerte active pe filtrele curente. Fluxul de plati este stabil.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div
                    v-for="item in attentionItems"
                    :key="item.key"
                    class="rounded-xl border p-3"
                    :class="attentionToneClass(item.tone)"
                >
                    <div class="text-xs uppercase tracking-wider opacity-80 mb-1">Atentie</div>
                    <div class="font-semibold text-sm mb-1">{{ item.title }}</div>
                    <div class="text-lg font-semibold">{{ item.value }}</div>
                    <div class="text-xs mt-1 opacity-90">{{ item.helper }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nr factura, furnizor, note" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status plata</label>
                    <select v-model="filterForm.payment_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in paymentStatuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="invoices.data.length === 0"
            :icon="ReceiptPercentIcon"
            title="Nu exista facturi materiale"
            description="Adauga prima factura pentru urmarirea costurilor de materiale."
        >
            <Link :href="route('material-invoices.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza factura
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="invoice in invoices.data" :key="invoice.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ invoice.invoice_no || ('Factura #' + invoice.id) }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(invoice.payment_status)">{{ paymentStatusLabel(invoice.payment_status) }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mb-1">
                        <span>{{ invoice.project?.name || 'Fara proiect' }}</span>
                        <span v-if="invoice.phase"> · {{ invoice.phase.name }}</span>
                        <span v-if="invoice.material"> · {{ invoice.material.name }}</span>
                        <span v-if="invoice.supplier_name"> · {{ invoice.supplier_name }}</span>
                    </div>
                    <p class="text-xs text-gray-500">Emisa: {{ invoice.issue_date }}<span v-if="invoice.due_date"> · Scadenta: {{ invoice.due_date }}</span></p>
                    <p v-if="invoice.notes" class="text-sm text-gray-600 mt-2 line-clamp-2">{{ invoice.notes }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-sm font-medium text-gray-700">{{ formatCurrency(invoice.amount_total) }}</div>
                    <Link :href="route('material-invoices.edit', invoice.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(invoice)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ReceiptPercentIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

const props = defineProps({
    invoices: Object,
    filters: Object,
    projects: Array,
    paymentStatuses: Object,
    summary: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    payment_status: props.filters?.payment_status || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
});

const overdueUnpaidCount = computed(() =>
    (props.invoices?.data || []).filter((invoice) => isOverdueUnpaid(invoice)).length
);

const unpaidOrPartialCount = computed(() =>
    (props.invoices?.data || []).filter((invoice) => ['unpaid', 'partial'].includes(invoice.payment_status)).length
);

const highestExposureInvoice = computed(() => {
    const rows = (props.invoices?.data || []).filter((invoice) => ['unpaid', 'partial'].includes(invoice.payment_status));
    if (rows.length === 0) {
        return null;
    }

    return rows.reduce((maxRow, row) => {
        const maxValue = Number(maxRow?.amount_total || 0);
        const currentValue = Number(row?.amount_total || 0);

        return currentValue > maxValue ? row : maxRow;
    }, rows[0]);
});

const priorityInvoice = computed(() => {
    const rows = props.invoices?.data || [];
    if (rows.length === 0) {
        return null;
    }

    const overdue = rows.find((invoice) => isOverdueUnpaid(invoice));
    if (overdue) {
        return {
            title: overdue.invoice_no || `Factura #${overdue.id}`,
            project: overdue.project?.name || 'Fara proiect',
            supplier: overdue.supplier_name || '',
            reason: 'Factura este restanta dupa scadenta si necesita escaladare rapida.',
            tone: 'critical',
        };
    }

    if (highestExposureInvoice.value) {
        return {
            title: highestExposureInvoice.value.invoice_no || `Factura #${highestExposureInvoice.value.id}`,
            project: highestExposureInvoice.value.project?.name || 'Fara proiect',
            supplier: highestExposureInvoice.value.supplier_name || '',
            reason: 'Valoare mare neincasata; confirma planul de plata si termenul.',
            tone: 'warning',
        };
    }

    const pending = rows.find((invoice) => invoice.payment_status === 'unpaid');
    if (pending) {
        return {
            title: pending.invoice_no || `Factura #${pending.id}`,
            project: pending.project?.name || 'Fara proiect',
            supplier: pending.supplier_name || '',
            reason: 'Factura este deschisa; urmareste confirmarea platii.',
            tone: 'info',
        };
    }

    return null;
});

const attentionItems = computed(() => {
    const items = [];

    if (overdueUnpaidCount.value > 0) {
        items.push({
            key: 'overdue-unpaid',
            title: 'Restante dupa scadenta',
            value: overdueUnpaidCount.value,
            helper: 'Contacteaza urgent partile implicate pentru deblocarea platii.',
            tone: 'critical',
        });
    }

    if (unpaidOrPartialCount.value > 0) {
        items.push({
            key: 'open-invoices',
            title: 'Facturi deschise (unpaid + partial)',
            value: unpaidOrPartialCount.value,
            helper: 'Prioritizeaza incasarea facturilor cu impact mare in cash-flow.',
            tone: 'warning',
        });
    }

    if (highestExposureInvoice.value) {
        items.push({
            key: 'highest-exposure',
            title: 'Expunere maxima factura',
            value: formatCurrency(highestExposureInvoice.value.amount_total),
            helper: 'Revizuieste statusul pentru cea mai mare valoare deschisa.',
            tone: 'info',
        });
    }

    return items.slice(0, 3);
});

function isOverdueUnpaid(invoice) {
    if (!invoice?.due_date || invoice?.payment_status !== 'unpaid') {
        return false;
    }

    const dueDate = new Date(invoice.due_date);
    if (Number.isNaN(dueDate.getTime())) {
        return false;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return dueDate < today;
}

function attentionToneClass(tone) {
    if (tone === 'critical') return 'border-red-300 bg-red-50 text-red-700';
    if (tone === 'warning') return 'border-amber-300 bg-amber-50 text-amber-700';

    return 'border-sky-300 bg-sky-50 text-sky-700';
}

function applyFilters() {
    router.get(route('material-invoices.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.payment_status = '';
    filterForm.project_id = '';
    applyFilters();
}

function remove(invoice) {
    if (confirm(`Stergi factura ${invoice.invoice_no || ('#' + invoice.id)}?`)) {
        router.delete(route('material-invoices.destroy', invoice.id));
    }
}

function paymentStatusLabel(status) {
    return props.paymentStatuses?.[status] || status;
}

function statusClass(status) {
    if (status === 'paid') return 'bg-green-100 text-green-700';
    if (status === 'partial') return 'bg-yellow-100 text-yellow-700';
    if (status === 'cancelled') return 'bg-gray-100 text-gray-700';

    return 'bg-red-100 text-red-700';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(value || 0);
}
</script>
