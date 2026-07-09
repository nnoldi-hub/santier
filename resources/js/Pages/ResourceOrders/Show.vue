<template>
    <AppLayout title="Detaliu document resursa">
        <div class="max-w-6xl mx-auto space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Detaliu document resursa #{{ order.id }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ entityLabel }} · {{ order.project?.name || 'Fara proiect' }}</p>
                </div>
                <Link :href="route('resource-orders.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi la registru</Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Cantitate comandata</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ formatQuantity(order.ordered_quantity, order.ordered_unit) }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Diferenta maxima</div>
                    <div class="text-2xl font-semibold" :class="discrepancySummary.has_positive_difference ? 'text-red-700' : 'text-green-700'">
                        {{ Number(discrepancySummary.max_document_difference || 0).toFixed(2) }} {{ order.ordered_unit || '' }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Prag toleranta: {{ Number(discrepancySummary.quantity_tolerance || 0).toFixed(2) }} {{ order.ordered_unit || '' }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Status</div>
                    <div class="text-sm font-semibold text-gray-800">{{ order.status_label }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ order.resource_type_label }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Valoare unitara</div>
                    <div class="text-sm font-semibold text-gray-800">{{ formatCurrency(order.unit_price) }}</div>
                    <div class="text-xs text-gray-500 mt-1" v-if="order.responsible_user">Responsabil: {{ order.responsible_user.name }}</div>
                </div>
            </div>

            <div v-if="discrepancySummary.blocked_payment" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                Plata este blocata automat pana la clarificarea discrepantelor de cantitate.
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-2 space-y-4">
                    <section class="bg-white border border-gray-200 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Reconciliere cantitati</h3>
                        <div class="space-y-2 mb-4">
                            <div
                                v-for="item in reconciliation.checks"
                                :key="item.key"
                                class="rounded-lg border p-3"
                                :class="
                                    item.is_applicable
                                        ? (item.is_blocking ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50')
                                        : 'border-gray-200 bg-gray-50'
                                "
                            >
                                <div
                                    class="text-sm font-semibold"
                                    :class="item.is_applicable ? (item.is_blocking ? 'text-red-800' : 'text-green-800') : 'text-gray-700'"
                                >
                                    {{ item.label }}
                                </div>
                                <div
                                    class="text-xs mt-1"
                                    :class="item.is_applicable ? (item.is_blocking ? 'text-red-700' : 'text-green-700') : 'text-gray-500'"
                                >
                                    <template v-if="item.is_applicable">
                                        {{ formatMaybeQuantity(item.left) }} vs {{ formatMaybeQuantity(item.right) }} · Delta {{ Number(item.delta || 0).toFixed(2) }}
                                    </template>
                                    <template v-else>
                                        N/A - lipsesc documentele necesare pentru verificare.
                                    </template>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Documente atasate</h3>
                        <div v-if="linkedDocuments.length === 0" class="text-sm text-gray-500">Nu exista documente legate de aceasta inregistrare.</div>
                        <div v-else class="space-y-3">
                            <div v-for="item in linkedDocuments" :key="item.id" class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800">{{ item.title }}</div>
                                        <div class="text-xs text-gray-500">{{ item.role_label }}<span v-if="item.document_number"> · #{{ item.document_number }}</span></div>
                                    </div>
                                    <div class="text-xs text-gray-500">{{ item.created_at }}</div>
                                </div>
                                <div class="text-xs text-gray-600 mt-2">
                                    Declarata: {{ Number(item.declared_quantity || 0).toFixed(2) }} · Livrata: {{ Number(item.delivered_quantity || 0).toFixed(2) }} · Diferenta: {{ Number(item.difference_quantity || 0).toFixed(2) }}
                                </div>
                                <div class="flex gap-2 mt-2">
                                    <a v-if="item.download_url" :href="item.download_url" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Fisier</a>
                                    <a v-if="item.pdf_url" :href="item.pdf_url" class="text-xs border border-orange-200 rounded px-2 py-1 text-orange-700 hover:bg-orange-50">PDF</a>
                                </div>
                                <div v-if="item.notes" class="text-xs text-gray-500 mt-2">{{ item.notes }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="bg-white border border-gray-200 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Timeline trasabilitate</h3>
                        <div v-if="timeline.length === 0" class="text-sm text-gray-500">Timeline-ul va fi populat pe masura ce se adauga documente si confirmari.</div>
                        <div v-else class="space-y-2">
                            <div v-for="(item, idx) in timeline" :key="`${item.timestamp}-${idx}`" class="rounded-lg border border-gray-200 px-3 py-2">
                                <div class="text-xs text-gray-400">{{ item.timestamp }}</div>
                                <div class="text-sm font-semibold text-gray-800">{{ item.label }}</div>
                                <div class="text-xs text-gray-600">{{ item.details }}</div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-4">
                    <section class="bg-white border border-gray-200 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-3">Confirmari workflow</h3>
                        <div class="space-y-3">
                            <div v-for="item in confirmations" :key="item.role" class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-sm font-semibold text-gray-800">{{ item.role_label }}</div>
                                    <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(item.status)">{{ item.status_label }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1" v-if="item.confirmed_at">{{ item.confirmed_at }}<span v-if="item.confirmed_by"> · {{ item.confirmed_by }}</span></div>
                                <textarea v-model="confirmationNotes[item.role]" rows="2" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs mt-2" placeholder="Observatii confirmare" />
                                <div class="flex gap-2 mt-2">
                                    <button @click="submitConfirmation(item.role, 'confirmed')" class="text-xs bg-green-600 text-white rounded px-2 py-1 hover:bg-green-700">Confirma</button>
                                    <button @click="submitConfirmation(item.role, 'rejected')" class="text-xs bg-red-600 text-white rounded px-2 py-1 hover:bg-red-700">Respinge</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="bg-white border border-gray-200 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-gray-800 mb-2">Date logistice</h3>
                        <div class="text-xs text-gray-600 space-y-1">
                            <div v-if="order.phase">Etapa: {{ order.phase.name }}</div>
                            <div v-if="order.supplier_name">Furnizor: {{ order.supplier_name }}</div>
                            <div v-if="order.carrier_name">Transportator: {{ order.carrier_name }}</div>
                            <div v-if="order.equipment_name">Utilaj: {{ order.equipment_name }}</div>
                            <div v-if="order.delivery_date">Data livrare: {{ order.delivery_date }}</div>
                            <div v-if="order.notes">Note: {{ order.notes }}</div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    order: Object,
    confirmations: Array,
    linkedDocuments: Array,
    deliveries: Array,
    timeline: Array,
    discrepancySummary: Object,
    reconciliation: Object,
});

const confirmationNotes = reactive(
    Object.fromEntries((props.confirmations || []).map((item) => [item.role, item.notes || '']))
);

const entityLabel = computed(() => {
    return props.order?.material?.name || props.order?.equipment?.name || props.order?.equipment_name || 'Resursa';
});

function submitConfirmation(role, status) {
    router.patch(route('resource-orders.confirmations.update', props.order.id), {
        confirmation_role: role,
        status,
        notes: confirmationNotes[role] || '',
    }, { preserveScroll: true });
}

function formatQuantity(value, unit) {
    return `${Number(value || 0).toFixed(2)} ${unit || ''}`.trim();
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(Number(value || 0));
}

function formatMaybeQuantity(value) {
    if (value === null || value === undefined) {
        return 'N/A';
    }

    return Number(value || 0).toFixed(2);
}

function statusClass(status) {
    if (status === 'confirmed') return 'bg-green-100 text-green-700';
    if (status === 'rejected') return 'bg-red-100 text-red-700';
    return 'bg-gray-100 text-gray-700';
}
</script>