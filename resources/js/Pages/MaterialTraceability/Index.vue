<template>
    <AppLayout title="Trasabilitate materiale">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Trasabilitate materiale</h2>
                <p class="text-sm text-gray-500 mt-1">Comandat, livrat, consumat si facturat, agregat pe fiecare material.</p>
            </div>
            <Link :href="route('resource-orders.index')" class="text-sm text-gray-500 hover:text-gray-700">Vezi comenzi de resurse</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Materiale urmarite</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.materials_tracked }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Cu diferente / blocate</div>
                <div class="text-xl font-semibold text-amber-600">{{ summary.with_discrepancies }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Valoare comandata</div>
                <div class="text-xl font-semibold text-gray-800">{{ formatCurrency(summary.total_ordered_value) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Facturi neplatite</div>
                <div class="text-xl font-semibold text-red-600">{{ formatCurrency(summary.unpaid_invoices_total) }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume sau cod material" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in statusOptions" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="materials.data.length === 0"
            :icon="ArrowsRightLeftIcon"
            title="Nu exista materiale cu comenzi de resurse"
            description="Trasabilitatea apare aici de indata ce inregistrezi comenzi de resurse pentru materiale."
        />

        <div v-else class="space-y-3">
            <div v-for="material in materials.data" :key="material.id" class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-gray-800 text-sm truncate">{{ material.name }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full" :class="toneClass(material.status)">{{ statusOptions[material.status] || material.status }}</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            <span v-if="material.code">{{ material.code }} · </span>
                            {{ pluralize(material.orders_count, 'comanda', 'comenzi') }}
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs text-gray-500">Valoare comandata</div>
                        <div class="text-lg font-semibold text-gray-800">{{ formatCurrency(material.total_ordered_value) }}</div>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <div class="text-xs text-gray-500">Comandat</div>
                        <div class="text-sm font-semibold text-gray-800">{{ material.total_ordered }} {{ material.unit }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <div class="text-xs text-gray-500">Livrat</div>
                        <div class="text-sm font-semibold text-gray-800">{{ material.total_delivered !== null ? `${material.total_delivered} ${material.unit}` : '-' }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 px-3 py-2">
                        <div class="text-xs text-gray-500">Consumat</div>
                        <div class="text-sm font-semibold text-gray-800">{{ material.total_consumed !== null ? `${material.total_consumed} ${material.unit}` : '-' }}</div>
                    </div>
                </div>

                <div class="mt-3 rounded-lg border border-gray-100 px-3 py-2 flex items-center justify-between text-xs text-gray-600">
                    <span>Facturi materiale: {{ pluralize(material.invoices.count, 'factura', 'facturi') }} · total {{ formatCurrency(material.invoices.total) }}</span>
                    <span v-if="material.invoices.unpaid_total > 0" class="font-semibold text-red-600">Neplatit: {{ formatCurrency(material.invoices.unpaid_total) }}</span>
                </div>

                <div class="mt-3 divide-y divide-gray-100 border-t border-gray-100">
                    <Link
                        v-for="order in material.orders"
                        :key="order.id"
                        :href="order.show_url"
                        class="flex items-center justify-between py-2 text-xs hover:bg-gray-50 -mx-1 px-1 rounded"
                    >
                        <span class="text-gray-600">
                            <span class="px-1.5 py-0.5 rounded-full mr-2" :class="toneClass(order.tone)">{{ order.status_label }}</span>
                            {{ order.project_name || 'Fara proiect' }}
                        </span>
                        <span class="text-gray-500">{{ order.ordered_quantity }} {{ material.unit }} · {{ formatCurrency(order.ordered_value) }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ArrowsRightLeftIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

const props = defineProps({
    materials: Object,
    filters: Object,
    statusOptions: Object,
    summary: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    status: props.filters?.status || '',
});

function applyFilters() {
    router.get(route('trasabilitate-materiale.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.status = '';
    applyFilters();
}

function toneClass(tone) {
    if (tone === 'blocked') return 'bg-red-100 text-red-700';
    if (tone === 'warning') return 'bg-amber-100 text-amber-700';

    return 'bg-emerald-100 text-emerald-700';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(Number(value || 0));
}
</script>
