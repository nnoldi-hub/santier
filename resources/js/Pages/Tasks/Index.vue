<template>
    <AppLayout title="Taskuri">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Taskuri</h2>
                <p class="text-sm text-gray-500 mt-1">{{ tasks.total }} taskuri in total</p>
            </div>
            <Link :href="route('tasks.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Task nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option value="todo">To do</option>
                        <option value="in_progress">In progres</option>
                        <option value="done">Finalizat</option>
                        <option value="cancelled">Anulat</option>
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

        <div v-if="tasks.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <div class="text-5xl mb-4">✅</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista taskuri</h3>
            <p class="text-gray-400 text-sm mb-6">Creeaza primul task pentru un proiect.</p>
            <Link :href="route('tasks.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza task
            </Link>
        </div>

        <div v-else class="space-y-3">
            <div v-for="task in tasks.data" :key="task.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ task.title }}</h3>
                        <span :class="statusClass(task.status)" class="text-xs px-2 py-0.5 rounded-full">{{ statusLabel(task.status) }}</span>
                        <span :class="priorityClass(task.priority)" class="text-xs px-2 py-0.5 rounded-full">{{ priorityLabel(task.priority) }}</span>
                    </div>
                    <p class="text-xs text-gray-500">
                        {{ task.project?.name }}
                        <span v-if="task.phase"> · {{ task.phase.name }}</span>
                        <span v-if="task.assignee"> · responsabil: {{ task.assignee.name }}</span>
                    </p>
                    <p v-if="task.description" class="text-sm text-gray-600 mt-2 line-clamp-2">{{ task.description }}</p>
                    <p v-if="task.deadline" class="text-xs text-gray-500 mt-2">Deadline: {{ formatDate(task.deadline) }}</p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <select :value="task.status" @change="changeStatus(task, $event)" class="border border-gray-300 rounded px-2 py-1 text-xs">
                        <option value="todo">To do</option>
                        <option value="in_progress">In progres</option>
                        <option value="done">Finalizat</option>
                        <option value="cancelled">Anulat</option>
                    </select>
                    <Link :href="route('tasks.edit', task.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
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
    projects: Array,
    filters: Object,
});

const filterForm = reactive({
    status: props.filters?.status || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
});

function applyFilters() {
    router.get(route('tasks.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.status = '';
    filterForm.project_id = '';
    applyFilters();
}

function changeStatus(task, event) {
    router.patch(route('tasks.status', task.id), { status: event.target.value }, { preserveScroll: true });
}

function remove(task) {
    if (confirm(`Stergi taskul "${task.title}"?`)) {
        router.delete(route('tasks.destroy', task.id));
    }
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function statusLabel(status) {
    return {
        todo: 'To do',
        in_progress: 'In progres',
        done: 'Finalizat',
        cancelled: 'Anulat',
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
        todo: 'bg-gray-100 text-gray-700',
        in_progress: 'bg-blue-100 text-blue-700',
        done: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
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
