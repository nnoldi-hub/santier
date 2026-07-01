<template>
    <AppLayout title="Calendar utilaje">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Calendar utilaje</h2>
                <p class="text-sm text-gray-500 mt-1">Rezervari de utilaje pe interval, proiect si etapa.</p>
            </div>
            <Link :href="route('equipment.index')" class="text-sm text-gray-500 hover:text-gray-700">Vezi utilaje</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Rezervari</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.total_reservations }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Utilaje implicate</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.equipment_involved }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Unitati rezervate</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.units_reserved }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Cost estimat</div>
                <div class="text-xl font-semibold text-gray-800">{{ formatCurrency(summary.estimated_cost) }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">De la</label>
                    <input v-model="filterForm.start_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Pana la</label>
                    <input v-model="filterForm.end_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Utilaj</label>
                    <select v-model="filterForm.equipment_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="item in equipment" :key="item.id" :value="String(item.id)">{{ item.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Actualizeaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <div v-if="reservations.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista rezervari in intervalul ales</h3>
            <p class="text-gray-400 text-sm">Creeaza rezervari din pagina unui proiect pentru a le vedea aici.</p>
        </div>

        <div v-else class="space-y-3">
            <div v-for="reservation in reservations" :key="reservation.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ reservation.equipment?.name || 'Utilaj' }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="reservation.equipment?.availability_status === 'available' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                            {{ reservation.equipment?.availability_status || 'n/a' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mb-1">
                        {{ reservation.phase?.project?.name || 'Fara proiect' }} · {{ reservation.phase?.name || 'Fara etapa' }}
                    </div>
                    <p class="text-sm text-gray-600">
                        {{ formatDate(reservation.usage_start) }} - {{ formatDate(reservation.usage_end) }}
                        <span class="text-gray-400">·</span>
                        qty {{ reservation.quantity }}
                        <span class="text-gray-400">·</span>
                        {{ formatCurrency(Number(reservation.equipment?.cost_per_hour || 0) * Number(reservation.quantity || 0)) }}/ora
                    </p>
                    <p v-if="reservation.notes" class="text-sm text-gray-500 mt-2 line-clamp-2">{{ reservation.notes }}</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    reservations: Array,
    equipment: Array,
    filters: Object,
    summary: Object,
});

const filterForm = reactive({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    equipment_id: props.filters?.equipment_id ? String(props.filters.equipment_id) : '',
});

function applyFilters() {
    router.get(route('equipment-calendar.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.equipment_id = '';
    applyFilters();
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('ro-RO', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value));
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(value || 0);
}
</script>
