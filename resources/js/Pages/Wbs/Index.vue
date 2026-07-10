<template>
    <AppLayout title="Etape de lucru (WBS)">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Etape de lucru (WBS)</h2>
                <p class="text-sm text-gray-500 mt-1">{{ pluralize(phases.total, 'etapa inregistrata', 'etape inregistrate') }}</p>
            </div>
            <Link :href="route('projects.index')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                Vezi proiecte
            </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Etape in lucru</div>
                <div class="text-2xl font-semibold text-gray-800">{{ wbsSummary.inProgress }}</div>
                <div class="text-xs text-gray-500 mt-1">cer monitorizare activa</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Etape blocate</div>
                <div class="text-2xl font-semibold text-gray-800">{{ wbsSummary.blocked }}</div>
                <div class="text-xs text-gray-500 mt-1">au nevoie de deblocare sau decizie</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Fara contractor</div>
                <div class="text-2xl font-semibold text-gray-800">{{ wbsSummary.unassigned }}</div>
                <div class="text-xs text-gray-500 mt-1">etape fara responsabil clar</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Etape intarziate</div>
                <div class="text-2xl font-semibold text-gray-800">{{ wbsSummary.overdue }}</div>
                <div class="text-xs text-gray-500 mt-1">termen depasit fata de data curenta</div>
            </div>
        </div>

        <div v-if="priorityPhase" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-amber-700">Etapa prioritara acum</div>
                    <div class="text-lg font-semibold text-gray-900 mt-1">{{ priorityPhase.name }}</div>
                    <div class="text-sm text-gray-700 mt-1">
                        {{ priorityReason(priorityPhase) }}
                        <span class="text-gray-500"> · {{ priorityPhase.project?.name || 'Fara proiect' }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span :class="statusClass(priorityPhase.status)" class="text-xs px-2 py-1 rounded-full">{{ statusLabel(priorityPhase.status) }}</span>
                    <span class="text-sm font-medium text-gray-700">{{ priorityPhase.progress_pct }}%</span>
                    <Link :href="route('projects.show', priorityPhase.project_id)" class="border border-amber-300 bg-white px-3 py-2 rounded-lg text-sm text-amber-800 hover:bg-amber-100 transition">
                        Deschide proiect
                    </Link>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Etapa, proiect, contractor" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option value="pending">Planificat</option>
                        <option value="in_progress">In lucru</option>
                        <option value="blocked">Intarziat/Blocat</option>
                        <option value="completed">Finalizat</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate proiectele</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Contractor</label>
                    <select v-model="filterForm.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toti contractorii</option>
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
            v-if="phases.data.length === 0"
            :icon="PuzzlePieceIcon"
            title="Nu exista etape WBS"
            description="Adauga etape din pagina proiectului pentru planificare operationala."
        >
            <Link :href="route('projects.index')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Mergi la proiecte
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div
                v-for="phase in phases.data"
                :key="phase.id"
                class="bg-white rounded-xl border border-gray-200 p-4"
                :class="phase.parent_id ? 'ml-6 border-l-4 border-l-orange-200' : ''"
            >
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-semibold text-gray-800 text-sm truncate">{{ phase.name }}</h3>
                            <span :class="statusClass(phase.status)" class="text-xs px-2 py-0.5 rounded-full">{{ statusLabel(phase.status) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ typeLabel(phase.type) }}</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            Proiect: {{ phase.project?.name || 'N/A' }}
                            <span v-if="phase.contractor"> · Contractor: {{ phase.contractor.name }}</span>
                            <span v-else> · Contractor: Nealocat</span>
                            <span v-if="phase.parent"> · Sub-etapa din: {{ phase.parent.name }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            <span v-if="phase.start_date">Start: {{ formatDate(phase.start_date) }}</span>
                            <span v-if="phase.end_date"> · End: {{ formatDate(phase.end_date) }}</span>
                        </div>
                    </div>

                    <div class="w-36 shrink-0">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span>Progres</span>
                            <span>{{ phase.progress_pct }}%</span>
                        </div>
                        <div class="bg-gray-200 rounded-full h-1.5">
                            <div class="bg-orange-500 h-1.5 rounded-full transition-all" :style="{ width: `${phase.progress_pct}%` }" />
                        </div>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Status</label>
                        <select v-model="getDraft(phase).status" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                            <option value="pending">Planificat</option>
                            <option value="in_progress">In lucru</option>
                            <option value="blocked">Blocat</option>
                            <option value="completed">Finalizat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Progres %</label>
                        <input v-model.number="getDraft(phase).progress_pct" type="number" min="0" max="100" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Contractor</label>
                        <select v-model="getDraft(phase).contractor_id" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                            <option value="">Nealocat</option>
                            <option v-for="contractor in contractors" :key="contractor.id" :value="String(contractor.id)">{{ contractor.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Parinte (WBS)</label>
                        <select v-model="getDraft(phase).parent_id" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                            <option value="">Fara parinte</option>
                            <option v-for="option in parentOptions(phase)" :key="option.id" :value="String(option.id)">{{ option.name }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button @click="savePhase(phase)" class="text-xs border border-orange-200 bg-orange-50 text-orange-700 rounded px-2 py-1 hover:bg-orange-100">Salveaza</button>
                        <Link :href="route('projects.show', phase.project_id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Deschide proiect</Link>
                    </div>
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
import { PuzzlePieceIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

const props = defineProps({
    phases: Object,
    filters: Object,
    projects: Array,
    contractors: Array,
    typeLabels: Object,
    phaseOptionsByProject: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    status: props.filters?.status || '',
    project_id: props.filters?.project_id || '',
    contractor_id: props.filters?.contractor_id || '',
});

const editDrafts = reactive({});

const today = new Date();

const visiblePhases = computed(() => props.phases?.data || []);

const wbsSummary = computed(() => {
    const phases = visiblePhases.value;

    return {
        inProgress: phases.filter((phase) => phase.status === 'in_progress').length,
        blocked: phases.filter((phase) => phase.status === 'blocked').length,
        unassigned: phases.filter((phase) => !phase.contractor_id).length,
        overdue: phases.filter((phase) => isOverdue(phase)).length,
    };
});

const priorityPhase = computed(() => {
    const phases = visiblePhases.value;

    return phases.find((phase) => phase.status === 'blocked')
        || phases.find((phase) => isOverdue(phase) && phase.status !== 'completed')
        || phases.find((phase) => phase.status === 'in_progress' && !phase.contractor_id)
        || phases.find((phase) => phase.status === 'in_progress')
        || phases[0]
        || null;
});

function getDraft(phase) {
    if (!editDrafts[phase.id]) {
        editDrafts[phase.id] = {
            status: phase.status,
            progress_pct: phase.progress_pct,
            contractor_id: phase.contractor_id ? String(phase.contractor_id) : '',
            parent_id: phase.parent_id ? String(phase.parent_id) : '',
        };
    }

    return editDrafts[phase.id];
}

function parentOptions(phase) {
    const options = props.phaseOptionsByProject?.[phase.project_id] || [];
    return options.filter((option) => option.id !== phase.id);
}

function savePhase(phase) {
    const draft = getDraft(phase);

    router.patch(route('wbs.phases.update', phase.id), {
        status: draft.status,
        progress_pct: Number(draft.progress_pct),
        contractor_id: draft.contractor_id ? Number(draft.contractor_id) : null,
        parent_id: draft.parent_id ? Number(draft.parent_id) : null,
    }, {
        preserveScroll: true,
    });
}

function applyFilters() {
    router.get(route('wbs.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.status = '';
    filterForm.project_id = '';
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

function isOverdue(phase) {
    if (!phase?.end_date || phase?.status === 'completed') {
        return false;
    }

    return new Date(phase.end_date) < today;
}

function priorityReason(phase) {
    if (phase.status === 'blocked') {
        return 'Etapa este blocata si cere interventie imediata.';
    }

    if (isOverdue(phase)) {
        return 'Etapa a depasit termenul planificat si trebuie replanificata.';
    }

    if (phase.status === 'in_progress' && !phase.contractor_id) {
        return 'Etapa este in lucru, dar nu are contractor alocat.';
    }

    if (phase.status === 'in_progress') {
        return 'Etapa este in executie si merita urmarita prioritar.';
    }

    return 'Etapa este urmatorul punct relevant din structura WBS.';
}

function typeLabel(type) {
    return props.typeLabels?.[type] || type;
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>
