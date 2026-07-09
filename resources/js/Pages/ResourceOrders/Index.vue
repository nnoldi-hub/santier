<template>
    <AppLayout title="Documente resurse">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Documente resurse</h2>
                <p class="text-sm text-gray-500 mt-1">{{ orders.total }} comenzi / livrari urmarite in registru</p>
            </div>
            <Link :href="route('resource-orders.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Document resursa nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Material, utilaj, furnizor, transportator" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Tip resursa</label>
                    <select v-model="filterForm.resource_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in resourceTypes" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
            </div>
        </div>

        <div v-if="orders.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <div class="text-5xl mb-4">🧾</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista documente resurse</h3>
            <p class="text-gray-400 text-sm mb-6">Inregistreaza prima comanda sau livrare pentru trasabilitate.</p>
            <Link :href="route('resource-orders.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza registrare
            </Link>
        </div>

        <div v-else class="space-y-3">
            <div v-for="order in orders.data" :key="order.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ entityLabel(order) }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ order.resource_type_label }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(order.status)">{{ order.status_label }}</span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <span>{{ order.project?.name || 'Fara proiect' }}</span>
                        <span v-if="order.phase"> · {{ order.phase.name }}</span>
                        <span v-if="order.supplier_name"> · furnizor {{ order.supplier_name }}</span>
                        <span v-if="order.carrier_name"> · transport {{ order.carrier_name }}</span>
                        <span v-if="order.equipment_name"> · utilaj {{ order.equipment_name }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        <span>{{ formatQuantity(order.ordered_quantity, order.ordered_unit) }}</span>
                        <span v-if="order.delivery_date"> · livrare {{ order.delivery_date }}</span>
                        <span v-if="order.responsible_user"> · responsabil {{ order.responsible_user.name }}</span>
                        <span> · doc. atasate {{ order.document_links_count || 0 }}</span>
                    </div>
                    <div v-if="order.notes" class="text-xs text-gray-600 mt-2">{{ order.notes }}</div>
                </div>
                <div class="shrink-0 text-sm font-medium text-gray-700">{{ formatCurrency(order.unit_price) }}</div>
                <Link :href="order.show_url" class="shrink-0 text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Detalii</Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    orders: Object,
    filters: Object,
    resourceTypes: Object,
    statuses: Object,
    projects: Array,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    resource_type: props.filters?.resource_type || '',
    status: props.filters?.status || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
});

function applyFilters() {
    router.get(route('resource-orders.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.resource_type = '';
    filterForm.status = '';
    filterForm.project_id = '';
    applyFilters();
}

function entityLabel(order) {
    return order.material?.name || order.equipment?.name || order.equipment_name || 'Resursa fara nume';
}

function formatQuantity(value, unit) {
    return `${Number(value || 0).toFixed(2)} ${unit || ''}`.trim();
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(Number(value || 0));
}

function statusClass(status) {
    if (status === 'approved') return 'bg-green-100 text-green-700';
    if (status === 'blocked_payment') return 'bg-red-100 text-red-700';
    if (status === 'rejected') return 'bg-red-100 text-red-700';
    if (status === 'financial_review' || status === 'verified') return 'bg-amber-100 text-amber-700';
    return 'bg-gray-100 text-gray-700';
}
</script>