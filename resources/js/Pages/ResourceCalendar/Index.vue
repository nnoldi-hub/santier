<template>
    <AppLayout title="Calendar resurse">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Calendar resurse</h2>
                <p class="text-sm text-gray-500 mt-1">Vizualizare unificata pentru echipe si utilaje in acelasi interval.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Evenimente</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.total_events }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Echipe</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.team_events }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Utilaje</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.equipment_events }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">De la</label>
                    <input v-model="filterForm.start_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Pana la</label>
                    <input v-model="filterForm.end_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Echipa</label>
                    <select v-model="filterForm.team_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="team in teams" :key="team.id" :value="String(team.id)">{{ team.name }}</option>
                    </select>
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

        <EmptyState
            v-if="events.length === 0"
            title="Nu exista evenimente in intervalul ales"
            description="Extinde perioada sau elimina filtrele pentru a vedea planificarea."
        />

        <div v-else class="space-y-3">
            <div v-for="event in events" :key="event.id" class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs px-2 py-0.5 rounded-full" :class="event.type === 'team' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                                {{ event.type === 'team' ? 'Echipa' : 'Utilaj' }}
                            </span>
                            <h3 class="font-semibold text-gray-800 text-sm">{{ event.resource }}</h3>
                        </div>
                        <div class="text-xs text-gray-500 mb-1">{{ event.project_name || 'Fara proiect' }} · {{ event.phase_name || 'Fara etapa' }}</div>
                        <p class="text-sm text-gray-600">
                            {{ formatDate(event.start_date) }} - {{ formatDate(event.end_date) }}
                            <span class="text-gray-400">·</span>
                            <template v-if="event.type === 'team'">
                                necesar {{ event.meta?.workers_needed || 0 }} / alocati {{ event.meta?.workers_assigned || 0 }}
                            </template>
                            <template v-else>
                                qty {{ event.meta?.quantity || 0 }}
                            </template>
                        </p>
                        <p v-if="event.meta?.notes" class="text-sm text-gray-500 mt-2 line-clamp-2">{{ event.meta.notes }}</p>
                    </div>
                    <div class="text-xs px-2 py-1 rounded" :class="event.resource_statusClass">{{ event.resource_statusLabel }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    events: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
    equipment: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, default: () => ({}) },
});

const filterForm = reactive({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    team_id: props.filters?.team_id ? String(props.filters.team_id) : '',
    equipment_id: props.filters?.equipment_id ? String(props.filters.equipment_id) : '',
});

const events = computed(() =>
    props.events.map((event) => {
        if (event.type === 'team') {
            return {
                ...event,
                resource_statusLabel: event.resource_status === 'active' ? 'activ' : 'inactiv',
                resource_statusClass: event.resource_status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700',
            };
        }

        return {
            ...event,
            resource_statusLabel: event.resource_status || 'n/a',
            resource_statusClass:
                event.resource_status === 'available'
                    ? 'bg-green-100 text-green-700'
                    : event.resource_status === 'in_use'
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-gray-100 text-gray-700',
        };
    })
);

function applyFilters() {
    router.get(route('resource-calendar.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.team_id = '';
    filterForm.equipment_id = '';
    applyFilters();
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('ro-RO', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value));
}
</script>
