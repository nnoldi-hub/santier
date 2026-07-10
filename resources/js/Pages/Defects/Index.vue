<template>
    <AppLayout title="Defecte (Snag)">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Defecte (Snag)</h2>
                <p class="text-sm text-gray-500 mt-1">{{ pluralize(defects.total, 'defect in total', 'defecte in total') }}</p>
            </div>
            <Link :href="route('defects.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Defect nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option value="open">Deschis</option>
                        <option value="in_progress">In progres</option>
                        <option value="resolved">Rezolvat</option>
                        <option value="rejected">Respins</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Prioritate</label>
                    <select v-model="filterForm.priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate proiectele</option>
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
            v-if="defects.data.length === 0"
            :icon="WrenchScrewdriverIcon"
            title="Nu exista defecte"
            description="Adauga primul defect pentru urmarirea remedierilor."
        >
            <Link :href="route('defects.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza defect
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="defect in defects.data" :key="defect.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ defect.title }}</h3>
                        <span :class="statusClass(defect.status)" class="text-xs px-2 py-0.5 rounded-full">{{ statusLabel(defect.status) }}</span>
                        <span :class="priorityClass(defect.priority)" class="text-xs px-2 py-0.5 rounded-full">{{ priorityLabel(defect.priority) }}</span>
                    </div>
                    <p class="text-xs text-gray-500">
                        {{ defect.project?.name }}
                        <span v-if="defect.phase"> · {{ defect.phase.name }}</span>
                        <span v-if="defect.assignee"> · responsabil: {{ defect.assignee.name }}</span>
                    </p>
                    <p v-if="defect.location" class="text-xs text-gray-500 mt-1">Locatie: {{ defect.location }}</p>
                    <p v-if="defect.description" class="text-sm text-gray-600 mt-2 line-clamp-2">{{ defect.description }}</p>
                    <p v-if="defect.due_date" class="text-xs text-gray-500 mt-2">Deadline remediere: {{ formatDate(defect.due_date) }}</p>
                    <img v-if="defect.photo_url" :src="defect.photo_url" alt="Foto defect" class="mt-2 rounded-lg border border-gray-200 max-h-32 object-cover" />
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <select :value="defect.status" @change="changeStatus(defect, $event)" class="border border-gray-300 rounded px-2 py-1 text-xs">
                        <option value="open">Deschis</option>
                        <option value="in_progress">In progres</option>
                        <option value="resolved">Rezolvat</option>
                        <option value="rejected">Respins</option>
                    </select>
                    <Link :href="route('defects.edit', defect.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(defect)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
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
import { WrenchScrewdriverIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

const props = defineProps({
    defects: Object,
    projects: Array,
    filters: Object,
});

const filterForm = reactive({
    status: props.filters?.status || '',
    priority: props.filters?.priority || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
});

function applyFilters() {
    router.get(route('defects.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.status = '';
    filterForm.priority = '';
    filterForm.project_id = '';
    applyFilters();
}

function changeStatus(defect, event) {
    router.patch(route('defects.status', defect.id), { status: event.target.value }, { preserveScroll: true });
}

function remove(defect) {
    if (confirm(`Stergi defectul "${defect.title}"?`)) {
        router.delete(route('defects.destroy', defect.id));
    }
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function statusLabel(status) {
    return {
        open: 'Deschis',
        in_progress: 'In progres',
        resolved: 'Rezolvat',
        rejected: 'Respins',
    }[status] || status;
}

function priorityLabel(priority) {
    return {
        low: 'Low',
        medium: 'Medium',
        high: 'High',
    }[priority] || priority;
}

function statusClass(status) {
    return {
        open: 'bg-red-100 text-red-700',
        in_progress: 'bg-blue-100 text-blue-700',
        resolved: 'bg-green-100 text-green-700',
        rejected: 'bg-gray-100 text-gray-700',
    }[status] || 'bg-gray-100 text-gray-700';
}

function priorityClass(priority) {
    return {
        low: 'bg-gray-100 text-gray-700',
        medium: 'bg-orange-100 text-orange-700',
        high: 'bg-red-100 text-red-700',
    }[priority] || 'bg-gray-100 text-gray-700';
}
</script>
