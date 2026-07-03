<template>
    <AppLayout title="Verificari">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Verificari calitate</h2>
                <p class="text-sm text-gray-500 mt-1">{{ checks.total }} verificari in total</p>
            </div>
            <Link :href="route('quality-checks.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Verificare noua
            </Link>
        </div>

        <div v-if="aiInsights?.length" class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-4">
            <div class="text-sm font-semibold text-indigo-900 mb-2">Insight AI</div>
            <div class="space-y-1">
                <Link v-for="(insight, index) in aiInsights" :key="`ai-${index}`" :href="insight.url" class="block text-xs text-indigo-800 hover:underline">
                    {{ insight.project_name || '-' }} · {{ insight.phase_name }}: {{ insight.message }}
                </Link>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Tip verificare</label>
                    <select v-model="filterForm.check_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
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

        <div v-if="checks.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <div class="text-5xl mb-4">✅</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista verificari</h3>
            <p class="text-gray-400 text-sm mb-6">Adauga prima verificare pentru urmarirea conformitatii.</p>
            <Link :href="route('quality-checks.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza verificare
            </Link>
        </div>

        <div v-else class="space-y-3">
            <div v-for="check in checks.data" :key="check.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-start gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ check.title }}</h3>
                        <span :class="statusClass(check.status)" class="text-xs px-2 py-0.5 rounded-full">{{ statusLabel(check.status) }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ typeLabel(check.check_type) }}</span>
                    </div>
                    <p class="text-xs text-gray-500">
                        {{ check.project?.name }}
                        <span v-if="check.phase"> · {{ check.phase.name }}</span>
                        <span v-if="check.assignee"> · responsabil: {{ check.assignee.name }}</span>
                    </p>
                    <p v-if="check.description" class="text-sm text-gray-600 mt-2 line-clamp-2">{{ check.description }}</p>
                    <p v-if="check.planned_at" class="text-xs text-gray-500 mt-2">Planificat: {{ formatDate(check.planned_at) }}</p>
                    <p class="text-xs text-gray-500 mt-1">Tip receptie: {{ receptionTypeLabel(check.reception_type) }}</p>
                    <p v-if="Array.isArray(check.checklist) && check.checklist.length" class="text-xs text-gray-500 mt-1">
                        Checklist: {{ check.checklist.filter((item) => item?.done).length }}/{{ check.checklist.length }} bifate
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <select :value="check.status" @change="changeStatus(check, $event)" class="border border-gray-300 rounded px-2 py-1 text-xs">
                        <option value="pending">In asteptare</option>
                        <option value="in_progress">In verificare</option>
                        <option value="passed">Conform</option>
                        <option value="failed">Neconform</option>
                    </select>
                    <Link :href="route('quality-checks.pdf', check.id)" class="text-xs border border-indigo-300 rounded px-2 py-1 text-indigo-700 hover:bg-indigo-50">PDF</Link>
                    <Link :href="route('quality-checks.edit', check.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(check)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
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
    checks: Object,
    projects: Array,
    filters: Object,
    statuses: Object,
    types: Object,
    receptionTypes: Object,
    aiInsights: Array,
});

const filterForm = reactive({
    status: props.filters?.status || '',
    check_type: props.filters?.check_type || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
});

function applyFilters() {
    router.get(route('quality-checks.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.status = '';
    filterForm.check_type = '';
    filterForm.project_id = '';
    applyFilters();
}

function changeStatus(check, event) {
    router.patch(route('quality-checks.status', check.id), { status: event.target.value }, { preserveScroll: true });
}

function remove(check) {
    if (confirm(`Stergi verificarea "${check.title}"?`)) {
        router.delete(route('quality-checks.destroy', check.id));
    }
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function statusLabel(status) {
    return props.statuses?.[status] || status;
}

function typeLabel(type) {
    return props.types?.[type] || type;
}

function receptionTypeLabel(type) {
    return props.receptionTypes?.[type] || type || 'partial';
}

function statusClass(status) {
    return {
        pending: 'bg-gray-100 text-gray-700',
        in_progress: 'bg-blue-100 text-blue-700',
        passed: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
    }[status] || 'bg-gray-100 text-gray-700';
}
</script>
