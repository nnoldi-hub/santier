<template>
    <AppLayout title="Taskuri pe etapa">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Taskuri operationale pe etapa</h2>
                <p class="text-sm text-gray-500 mt-1">{{ tasks.total }} taskuri inregistrate</p>
            </div>
            <Link :href="route('stage-tasks.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Task etapa
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in statusLabels" :key="key" :value="key">{{ label }}</option>
                    </select>
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
                <div class="flex items-end gap-2 md:col-span-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <div v-if="tasks.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista taskuri pe etapa</h3>
            <p class="text-gray-400 text-sm mb-6">Adauga primul task operational pentru o etapa.</p>
            <Link :href="route('stage-tasks.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza task
            </Link>
        </div>

        <div v-else class="space-y-3">
            <div v-for="task in tasks.data" :key="task.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ task.title }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(task.status)">{{ statusLabels[task.status] || task.status }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mb-1">
                        <span>{{ task.stage?.project_name || 'Fara proiect' }}</span>
                        <span> · {{ task.stage?.name || 'Fara etapa' }}</span>
                        <span v-if="task.assignee_name"> · {{ assigneeTypes[task.assignee_type] || task.assignee_type }}: {{ task.assignee_name }}</span>
                        <span v-if="task.deadline"> · deadline {{ task.deadline }}</span>
                    </div>
                    <p v-if="task.description" class="text-sm text-gray-700 line-clamp-2">{{ task.description }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('stage-tasks.edit', task.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(task)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
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
    tasks: Object,
    filters: Object,
    projects: Array,
    stages: Array,
    statusLabels: Object,
    assigneeTypes: Object,
});

const filterForm = reactive({
    status: props.filters?.status || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
    stage_id: props.filters?.stage_id ? String(props.filters.stage_id) : '',
});

function applyFilters() {
    router.get(route('stage-tasks.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.status = '';
    filterForm.project_id = '';
    filterForm.stage_id = '';
    applyFilters();
}

function remove(task) {
    if (confirm('Stergi taskul de etapa selectat?')) {
        router.delete(route('stage-tasks.destroy', task.id));
    }
}

function statusClass(status) {
    if (status === 'done') return 'bg-green-100 text-green-700';
    if (status === 'in_progress') return 'bg-blue-100 text-blue-700';
    if (status === 'blocked') return 'bg-red-100 text-red-700';

    return 'bg-gray-100 text-gray-700';
}
</script>
