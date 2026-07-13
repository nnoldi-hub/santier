<template>
    <AppLayout title="Trasabilitate utilaje">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Trasabilitate utilaje</h2>
                <p class="text-sm text-gray-500 mt-1">Rezervari si cost estimat, agregat pe fiecare utilaj.</p>
            </div>
            <Link :href="route('equipment.index')" class="text-sm text-gray-500 hover:text-gray-700">Vezi utilaje</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Utilaje urmarite</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.equipment_tracked }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Indisponibile / mentenanta</div>
                <div class="text-xl font-semibold text-amber-600">{{ summary.unavailable_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Cost estimat total</div>
                <div class="text-xl font-semibold text-gray-800">{{ formatCurrency(summary.total_estimated_cost) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Rezervari active azi</div>
                <div class="text-xl font-semibold text-blue-600">{{ summary.active_today_count }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume sau furnizor" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Tip</label>
                    <select v-model="filterForm.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Disponibilitate</label>
                    <select v-model="filterForm.availability_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in availabilityStatuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="equipment.data.length === 0"
            :icon="ArrowsRightLeftIcon"
            title="Nu exista utilaje cu rezervari"
            description="Trasabilitatea apare aici de indata ce rezervi un utilaj pe o etapa."
        />

        <div v-else class="space-y-3">
            <div v-for="item in equipment.data" :key="item.id" class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-gray-800 text-sm truncate">{{ item.name }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full" :class="availabilityClass(item.availability_status)">{{ item.availability_label }}</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ item.type_label }} · {{ pluralize(item.reservations_count, 'rezervare', 'rezervari') }}
                            <span v-if="item.active_reservations_count > 0"> · {{ pluralize(item.active_reservations_count, 'activa azi', 'active azi') }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs text-gray-500">Cost estimat total</div>
                        <div class="text-lg font-semibold text-gray-800">{{ formatCurrency(item.total_estimated_cost) }}</div>
                        <div class="text-xs text-gray-400">{{ item.total_reserved_days }} zile rezervate</div>
                    </div>
                </div>

                <div class="mt-3 divide-y divide-gray-100 border-t border-gray-100">
                    <div v-for="reservation in item.reservations" :key="reservation.id" class="flex items-center justify-between py-2 text-xs">
                        <span class="text-gray-600">
                            <span v-if="reservation.is_active" class="px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 mr-2">Activa</span>
                            {{ reservation.project_name || 'Fara proiect' }}
                            <span v-if="reservation.phase_name"> · {{ reservation.phase_name }}</span>
                        </span>
                        <span class="text-gray-500">{{ reservation.usage_start || '-' }} - {{ reservation.usage_end || '-' }} · {{ formatCurrency(reservation.estimated_cost) }}</span>
                    </div>
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
    equipment: Object,
    filters: Object,
    types: Object,
    availabilityStatuses: Object,
    summary: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    type: props.filters?.type || '',
    availability_status: props.filters?.availability_status || '',
});

function applyFilters() {
    router.get(route('trasabilitate-utilaje.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.type = '';
    filterForm.availability_status = '';
    applyFilters();
}

function availabilityClass(status) {
    if (status === 'available') return 'bg-emerald-100 text-emerald-700';
    if (status === 'reserved') return 'bg-blue-100 text-blue-700';
    if (status === 'maintenance') return 'bg-amber-100 text-amber-700';

    return 'bg-red-100 text-red-700';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(Number(value || 0));
}
</script>
