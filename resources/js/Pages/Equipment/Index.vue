<template>
    <AppLayout title="Utilaje">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Utilaje</h2>
                <p class="text-sm text-gray-500 mt-1">{{ equipment.total }} utilaje in catalog</p>
            </div>
            <Link :href="route('equipment.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Utilaj nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume utilaj sau furnizor" />
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
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Mini-calendar utilaje {{ calendarWindowLabel }}</h3>
                <Link :href="route('equipment-calendar.index')" class="text-xs text-orange-500 hover:underline">Vezi calendar complet →</Link>
            </div>

            <div class="mb-3">
                <select v-model="calendarWindow" @change="applyCalendarWindow" class="text-xs border border-gray-300 rounded-lg px-2 py-1 text-gray-600">
                    <option value="today">Azi</option>
                    <option value="7d">7 zile</option>
                    <option value="30d">30 zile</option>
                </select>
            </div>

            <div v-if="todayCalendar.length === 0" class="text-xs text-gray-400">Nu exista rezervari active in intervalul selectat.</div>
            <div v-else class="space-y-2">
                <div v-for="item in todayCalendar" :key="`today-eq-${item.id}`" class="text-xs border border-gray-200 rounded-lg p-2">
                    <div class="font-medium text-gray-700">{{ item.equipment_name }} · qty {{ item.quantity }}</div>
                    <div class="text-gray-500">{{ item.project_name || 'Fara proiect' }} · {{ item.stage_name || 'Fara etapa' }}</div>
                    <div class="text-gray-400">{{ item.window }}</div>
                </div>
            </div>
        </div>

        <div v-if="equipment.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista utilaje</h3>
            <p class="text-gray-400 text-sm mb-6">Adauga primul utilaj pentru rezervari pe etape.</p>
            <Link :href="route('equipment.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza utilaj
            </Link>
        </div>

        <div v-else class="space-y-3">
            <div v-for="item in equipment.data" :key="item.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ item.name }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ typeLabel(item.type) }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="item.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                            {{ item.active ? 'Activ' : 'Inactiv' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <span>{{ availabilityLabel(item.availability_status) }}</span>
                        <span v-if="item.supplier_name"> · {{ item.supplier_name }}</span>
                        <span> · {{ fmtCur(item.cost_per_hour) }}/ora</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('equipment.edit', item.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(item)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    equipment: Object,
    types: Object,
    availabilityStatuses: Object,
    filters: Object,
    todayCalendar: { type: Array, default: () => [] },
});

const filterForm = reactive({
    q: props.filters?.q || '',
    type: props.filters?.type || '',
    availability_status: props.filters?.availability_status || '',
});

const calendarWindow = ref(props.filters?.calendar_window || 'today');

const calendarWindowLabel = computed(() => {
    if (calendarWindow.value === '7d') return '7 zile';
    if (calendarWindow.value === '30d') return '30 zile';
    return 'azi';
});

function applyFilters() {
    router.get(route('equipment.index'), { ...filterForm, calendar_window: calendarWindow.value }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.type = '';
    filterForm.availability_status = '';
    applyFilters();
}

function applyCalendarWindow() {
    router.get(route('equipment.index'), { ...filterForm, calendar_window: calendarWindow.value }, { preserveState: true, preserveScroll: true, only: ['todayCalendar', 'filters'] });
}

function typeLabel(type) {
    return props.types?.[type] || type;
}

function availabilityLabel(status) {
    return props.availabilityStatuses?.[status] || status;
}

function fmtCur(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(value || 0);
}

function remove(item) {
    if (confirm(`Stergi utilajul "${item.name}"?`)) {
        router.delete(route('equipment.destroy', item.id));
    }
}
</script>
