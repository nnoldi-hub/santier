<template>
    <AppLayout title="Calendar echipe">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Calendar echipe</h2>
                <p class="text-sm text-gray-500 mt-1">Urmatoarele alocari pe etape, grupate ca agenda operationala.</p>
            </div>
            <Link :href="route('teams.index')" class="text-sm text-gray-500 hover:text-gray-700">Vezi echipe</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Alocari</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.total_assignments }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Echipe implicate</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.teams_involved }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Necesar total</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.workers_needed }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Alocati total</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.workers_assigned }}</div>
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
                    <label class="block text-xs text-gray-600 mb-1">Echipa</label>
                    <select v-model="filterForm.team_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="team in teams" :key="team.id" :value="String(team.id)">{{ team.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Actualizeaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <div v-if="assignments.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista alocari in intervalul ales</h3>
            <p class="text-gray-400 text-sm">Creeaza alocari din pagina unui proiect pentru a le vedea aici.</p>
        </div>

        <div v-else class="space-y-3">
            <div v-for="assignment in assignments" :key="assignment.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ assignment.team?.name || 'Echipa' }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="assignment.team?.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                            {{ assignment.team?.active ? 'activa' : 'inactiva' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mb-1">
                        {{ assignment.phase?.project?.name || 'Fara proiect' }} · {{ assignment.phase?.name || 'Fara etapa' }}
                    </div>
                    <p class="text-sm text-gray-600">
                        {{ formatDate(assignment.start_date) }} - {{ formatDate(assignment.end_date) }}
                        <span class="text-gray-400">·</span>
                        necesar {{ assignment.workers_needed }}
                        <span class="text-gray-400">·</span>
                        alocati {{ assignment.workers_assigned }}
                    </p>
                    <p v-if="assignment.notes" class="text-sm text-gray-500 mt-2 line-clamp-2">{{ assignment.notes }}</p>
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
    assignments: Array,
    teams: Array,
    filters: Object,
    summary: Object,
});

const filterForm = reactive({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    team_id: props.filters?.team_id ? String(props.filters.team_id) : '',
});

function applyFilters() {
    router.get(route('team-calendar.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.team_id = '';
    applyFilters();
}

function formatDate(value) {
    if (!value) return '-';
    return new Intl.DateTimeFormat('ro-RO', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value));
}
</script>
