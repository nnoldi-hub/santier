<template>
    <AppLayout title="Rapoarte de etapa">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Rapoarte de progres pe etapa</h2>
                <p class="text-sm text-gray-500 mt-1">{{ reports.total }} rapoarte inregistrate</p>
            </div>
            <Link :href="route('stage-reports.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Raport nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Activitati sau probleme" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Etapa</label>
                    <select v-model="filterForm.stage_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="stage in stages" :key="stage.id" :value="String(stage.id)">{{ stage.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Contractor</label>
                    <select v-model="filterForm.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toti</option>
                        <option v-for="contractor in contractors" :key="contractor.id" :value="String(contractor.id)">{{ contractor.name }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="reports.data.length === 0"
            :icon="PresentationChartBarIcon"
            title="Nu exista rapoarte"
            description="Adauga primul raport de progres pentru o etapa."
        >
            <Link :href="route('stage-reports.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza raport
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="report in reports.data" :key="report.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ report.stage?.name || 'Etapa necunoscuta' }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ report.report_date }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ report.progress_pct }}%</span>
                    </div>
                    <div class="text-xs text-gray-500 mb-1">
                        <span>{{ report.stage?.project?.name || 'Fara proiect' }}</span>
                        <span v-if="report.contractor"> · {{ report.contractor.name }}</span>
                        <span v-if="report.creator"> · raportat de {{ report.creator.name }}</span>
                    </div>
                    <p v-if="report.activities" class="text-sm text-gray-700 line-clamp-2">{{ report.activities }}</p>
                    <p v-if="report.issues" class="text-sm text-red-600 mt-1 line-clamp-2">Probleme: {{ report.issues }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('stage-reports.edit', report.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(report)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
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
import { PresentationChartBarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    reports: Object,
    filters: Object,
    projects: Array,
    stages: Array,
    contractors: Array,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
    stage_id: props.filters?.stage_id ? String(props.filters.stage_id) : '',
    contractor_id: props.filters?.contractor_id ? String(props.filters.contractor_id) : '',
});

function applyFilters() {
    router.get(route('stage-reports.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.project_id = '';
    filterForm.stage_id = '';
    filterForm.contractor_id = '';
    applyFilters();
}

function remove(report) {
    if (confirm('Stergi raportul de etapa selectat?')) {
        router.delete(route('stage-reports.destroy', report.id));
    }
}
</script>
