<template>
    <AppLayout title="Progres etape">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Progres etape</h2>
                <p class="text-sm text-gray-500 mt-1">Monitorizare concentrata a progresului pe fiecare etapa.</p>
            </div>
            <Link :href="route('wbs.index')" class="text-sm text-gray-500 hover:text-gray-700">Deschide WBS</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Etape</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.phases_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Progres mediu</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.average_progress }}%</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Finalizate</div>
                <div class="text-xl font-semibold text-green-600">{{ summary.completed_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">In lucru</div>
                <div class="text-xl font-semibold text-blue-600">{{ summary.in_progress_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Neincepute</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.not_started_count }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm text-gray-800">Prioritate acum</h3>
                <span class="text-xs text-gray-500">Focalizare pe etapele cu risc operational</span>
            </div>

            <div v-if="priorityPhase" class="rounded-xl border p-3 mb-3" :class="attentionToneClass(priorityPhase.tone)">
                <div class="text-xs uppercase tracking-wider opacity-80 mb-1">Etapa prioritara</div>
                <div class="font-semibold text-sm">{{ priorityPhase.phase.name }}</div>
                <div class="text-xs mt-1 opacity-90">
                    {{ priorityPhase.phase.project?.name || 'Fara proiect' }}
                    <span v-if="priorityPhase.phase.contractor"> · {{ priorityPhase.phase.contractor.name }}</span>
                </div>
                <div class="text-sm mt-2">{{ priorityPhase.reason }}</div>
            </div>

            <div v-if="attentionItems.length === 0" class="text-sm text-gray-500">
                Nu exista alerte active pe filtrele curente. Progresul este stabil.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div
                    v-for="item in attentionItems"
                    :key="item.key"
                    class="rounded-xl border p-3"
                    :class="attentionToneClass(item.tone)"
                >
                    <div class="text-xs uppercase tracking-wider opacity-80 mb-1">Atentie</div>
                    <div class="font-semibold text-sm mb-1">{{ item.title }}</div>
                    <div class="text-lg font-semibold">{{ item.value }}</div>
                    <div class="text-xs mt-1 opacity-90">{{ item.helper }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Etapa sau proiect" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option value="pending">Planificat</option>
                        <option value="in_progress">In lucru</option>
                        <option value="blocked">Blocat</option>
                        <option value="completed">Finalizat</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Contractor</label>
                    <select v-model="filterForm.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toti</option>
                        <option v-for="contractor in contractors" :key="contractor.id" :value="String(contractor.id)">{{ contractor.name }}</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="phases.data.length === 0"
            title="Nu exista etape pentru urmarire"
            description="Adauga etape din WBS pentru a vedea progresul in timp real."
        />

        <div v-else class="space-y-3">
            <div v-for="phase in phases.data" :key="phase.id" class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-gray-800 text-sm truncate">{{ phase.name }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(phase.status)">{{ statusLabel(phase.status) }}</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ phase.project?.name || 'Fara proiect' }}
                            <span v-if="phase.contractor"> · {{ phase.contractor.name }}</span>
                            <span v-if="phase.parent"> · sub-etapa: {{ phase.parent.name }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs text-gray-500">Progres</div>
                        <div class="text-xl font-semibold text-gray-800">{{ phase.progress_pct }}%</div>
                    </div>
                </div>
                <div class="mt-3 bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-orange-500 h-2 rounded-full" :style="{ width: `${phase.progress_pct}%` }"></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    phases: Object,
    filters: Object,
    projects: Array,
    contractors: Array,
    summary: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
    status: props.filters?.status || '',
    contractor_id: props.filters?.contractor_id ? String(props.filters.contractor_id) : '',
});

const blockedCountCurrentPage = computed(() =>
    (props.phases?.data || []).filter((phase) => phase.status === 'blocked').length
);

const stagnantCountCurrentPage = computed(() =>
    (props.phases?.data || []).filter(
        (phase) => phase.status === 'in_progress' && Number(phase.progress_pct || 0) <= 25
    ).length
);

const priorityPhase = computed(() => {
    const rows = props.phases?.data || [];
    if (rows.length === 0) {
        return null;
    }

    const blocked = rows.find((phase) => phase.status === 'blocked');
    if (blocked) {
        return {
            phase: blocked,
            reason: 'Etapa blocata necesita deblocare rapida.',
            tone: 'critical',
        };
    }

    const lowProgress = rows
        .filter((phase) => phase.status === 'in_progress')
        .sort((a, b) => Number(a.progress_pct || 0) - Number(b.progress_pct || 0))[0];

    if (lowProgress) {
        return {
            phase: lowProgress,
            reason: 'Etapa in lucru cu progres redus, risc de intarziere.',
            tone: 'warning',
        };
    }

    const pending = rows.find((phase) => phase.status === 'pending');
    if (pending) {
        return {
            phase: pending,
            reason: 'Etapa planificata fara executie; confirma data de start.',
            tone: 'info',
        };
    }

    return {
        phase: rows[0],
        reason: 'Flux stabil; mentine ritmul pentru inchiderea etapelor deschise.',
        tone: 'info',
    };
});

const attentionItems = computed(() => {
    const items = [];

    if (blockedCountCurrentPage.value > 0) {
        items.push({
            key: 'blocked',
            title: 'Etape blocate',
            value: blockedCountCurrentPage.value,
            helper: 'Prioritizeaza rezolvarea dependintelor critice.',
            tone: 'critical',
        });
    }

    if (stagnantCountCurrentPage.value > 0) {
        items.push({
            key: 'stagnant',
            title: 'Etape stagnante (<= 25%)',
            value: stagnantCountCurrentPage.value,
            helper: 'Realoca echipa sau clarifica blocajele operationale.',
            tone: 'warning',
        });
    }

    if ((props.summary?.not_started_count || 0) > 0) {
        items.push({
            key: 'not-started',
            title: 'Etape neincepute',
            value: props.summary.not_started_count,
            helper: 'Verifica planificarea si securizeaza resursele de start.',
            tone: 'info',
        });
    }

    return items.slice(0, 3);
});

function attentionToneClass(tone) {
    if (tone === 'critical') return 'border-red-300 bg-red-50 text-red-700';
    if (tone === 'warning') return 'border-amber-300 bg-amber-50 text-amber-700';

    return 'border-sky-300 bg-sky-50 text-sky-700';
}

function applyFilters() {
    router.get(route('stage-progress.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.project_id = '';
    filterForm.status = '';
    filterForm.contractor_id = '';
    applyFilters();
}

function statusLabel(status) {
    return {
        pending: 'Planificat',
        in_progress: 'In lucru',
        blocked: 'Blocat',
        completed: 'Finalizat',
    }[status] || status;
}

function statusClass(status) {
    return {
        pending: 'bg-gray-100 text-gray-700',
        in_progress: 'bg-blue-100 text-blue-700',
        blocked: 'bg-red-100 text-red-700',
        completed: 'bg-green-100 text-green-700',
    }[status] || 'bg-gray-100 text-gray-700';
}
</script>
