<template>
    <AppLayout title="Gantt">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Planificare Gantt 2.0</h2>
                <p class="text-sm text-gray-500 mt-1">Timeline pe etape, inclusiv mutare rapida prin drag and drop</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Scop</label>
                    <select v-model="scopeValue" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="changeFilters">
                        <option value="single">Proiect selectat</option>
                        <option value="all">Toate proiectele</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="projectId" :disabled="scopeValue === 'all'" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-400" @change="changeFilters">
                        <option value="">— Selecteaza proiect —</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Etape vizibile</div>
                    <div class="text-lg font-semibold text-gray-800">{{ localPhases.length }}</div>
                </div>
            </div>
        </div>

        <div v-if="scopeValue === 'single' && !selectedProjectId" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
            Selecteaza un proiect pentru a vedea planificarea Gantt.
        </div>

        <div v-else-if="localPhases.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
            Nu exista etape in filtrul curent.
        </div>

        <div v-else class="space-y-5">
            <template v-if="scopeValue === 'all'">
                <div v-for="projectGroup in groupedPhases" :key="projectGroup.project_id" class="bg-white rounded-xl border border-gray-200 p-5">
                    <div class="mb-4">
                        <h3 class="font-semibold text-gray-800">{{ projectGroup.project_name || 'Fara proiect' }}</h3>
                        <p class="text-xs text-gray-500">{{ projectGroup.phases.length }} etape</p>
                    </div>
                    <div class="space-y-3">
                        <div v-for="phase in projectGroup.phases" :key="phase.id" class="border border-gray-100 rounded-lg p-3">
                            <PhaseRow
                                :phase="phase"
                                :timeline-meta="timelineMeta"
                                :dragging-phase-id="dragState.phaseId"
                                @track-ref="setTrackRef"
                                @drag-start="startDrag"
                            />
                        </div>
                    </div>
                </div>
            </template>

            <div v-else class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="mb-4">
                    <h3 class="font-semibold text-gray-800">{{ selectedProject?.name || 'Proiect' }}</h3>
                    <p class="text-xs text-gray-500 mt-1">Status: {{ selectedProject?.status || 'n/a' }}</p>
                </div>
                <div class="space-y-3">
                    <div v-for="phase in localPhases" :key="phase.id" class="border border-gray-100 rounded-lg p-3">
                        <PhaseRow
                            :phase="phase"
                            :timeline-meta="timelineMeta"
                            :dragging-phase-id="dragState.phaseId"
                            @track-ref="setTrackRef"
                            @drag-start="startDrag"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch, h } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    projects: { type: Array, default: () => [] },
    selectedProjectId: { type: Number, default: null },
    selectedProject: { type: Object, default: null },
    phases: { type: Array, default: () => [] },
    scope: { type: String, default: 'single' },
});

const projectId = ref(props.selectedProjectId ? String(props.selectedProjectId) : '');
const scopeValue = ref(props.scope === 'all' ? 'all' : 'single');
const localPhases = ref(clonePhases(props.phases));
const trackRefs = reactive({});

const dragState = reactive({
    phaseId: null,
    startX: 0,
    initialStart: null,
    initialEnd: null,
    totalDays: 0,
    rowWidth: 1,
});

watch(
    () => props.phases,
    (newPhases) => {
        localPhases.value = clonePhases(newPhases);
    },
    { deep: true }
);

watch(
    () => props.scope,
    (newScope) => {
        scopeValue.value = newScope === 'all' ? 'all' : 'single';
    }
);

watch(
    () => props.selectedProjectId,
    (newProjectId) => {
        projectId.value = newProjectId ? String(newProjectId) : '';
    }
);

const timelineMeta = computed(() => {
    const datedPhases = localPhases.value.filter((phase) => phase.start_date && phase.end_date);

    if (datedPhases.length === 0) {
        return { minDate: null, maxDate: null, totalDays: 0 };
    }

    const starts = datedPhases.map((phase) => new Date(phase.start_date));
    const ends = datedPhases.map((phase) => new Date(phase.end_date));

    const minDate = new Date(Math.min(...starts.map((date) => date.getTime())));
    const maxDate = new Date(Math.max(...ends.map((date) => date.getTime())));
    const totalDays = Math.max(diffDays(minDate, maxDate), 1);

    return { minDate, maxDate, totalDays };
});

const groupedPhases = computed(() => {
    const groups = new Map();

    for (const phase of localPhases.value) {
        const key = phase.project_id || 0;
        if (!groups.has(key)) {
            groups.set(key, {
                project_id: key,
                project_name: phase.project_name || 'Fara proiect',
                phases: [],
            });
        }
        groups.get(key).phases.push(phase);
    }

    return Array.from(groups.values());
});

function clonePhases(phases) {
    return phases.map((phase) => ({ ...phase }));
}

function changeFilters() {
    router.get(
        route('gantt.index'),
        {
            scope: scopeValue.value,
            project_id: scopeValue.value === 'all' ? '' : projectId.value || '',
        },
        { preserveState: true, preserveScroll: true }
    );
}

function setTrackRef({ phaseId, element }) {
    if (!element) return;
    trackRefs[phaseId] = element;
}

function startDrag(phase, event) {
    if (!phase.start_date || !phase.end_date || timelineMeta.value.totalDays <= 0) {
        return;
    }

    const track = trackRefs[phase.id];
    if (!track) return;

    dragState.phaseId = phase.id;
    dragState.startX = event.clientX;
    dragState.initialStart = phase.start_date;
    dragState.initialEnd = phase.end_date;
    dragState.totalDays = timelineMeta.value.totalDays;
    dragState.rowWidth = Math.max(track.clientWidth, 1);

    window.addEventListener('pointermove', onDragMove);
    window.addEventListener('pointerup', onDragEnd);
}

function onDragMove(event) {
    if (!dragState.phaseId) return;

    const shiftDays = Math.round(((event.clientX - dragState.startX) / dragState.rowWidth) * dragState.totalDays);
    const phase = localPhases.value.find((item) => item.id === dragState.phaseId);

    if (!phase) return;

    phase.start_date = addDays(dragState.initialStart, shiftDays);
    phase.end_date = addDays(dragState.initialEnd, shiftDays);
}

function onDragEnd() {
    if (!dragState.phaseId) {
        cleanupDragListeners();
        return;
    }

    const phase = localPhases.value.find((item) => item.id === dragState.phaseId);

    if (phase && phase.project_id) {
        router.patch(
            route('phases.timeline.update', { project: phase.project_id, phase: phase.id }),
            {
                start_date: phase.start_date,
                end_date: phase.end_date,
            },
            { preserveScroll: true }
        );
    }

    dragState.phaseId = null;
    cleanupDragListeners();
}

function cleanupDragListeners() {
    window.removeEventListener('pointermove', onDragMove);
    window.removeEventListener('pointerup', onDragEnd);
}

onMounted(() => {
    cleanupDragListeners();
});

onBeforeUnmount(() => {
    cleanupDragListeners();
});

function phaseBarStyle(phase) {
    if (!phase.start_date || !phase.end_date || timelineMeta.value.totalDays <= 0) {
        return { left: '1%', width: '30%' };
    }

    const start = new Date(phase.start_date);
    const end = new Date(phase.end_date);

    const leftDays = Math.max(diffDays(timelineMeta.value.minDate, start), 0);
    const duration = Math.max(diffDays(start, end), 1);

    const leftPct = (leftDays / timelineMeta.value.totalDays) * 100;
    const widthPct = (duration / timelineMeta.value.totalDays) * 100;

    return {
        left: `${leftPct}%`,
        width: `${Math.max(Math.min(widthPct, 100 - leftPct), 3)}%`,
    };
}

function diffDays(dateA, dateB) {
    const oneDayMs = 24 * 60 * 60 * 1000;
    return Math.ceil((dateB.getTime() - dateA.getTime()) / oneDayMs);
}

function addDays(dateString, days) {
    const date = new Date(dateString);
    date.setDate(date.getDate() + days);

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function fmt(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

const PhaseRow = {
    props: {
        phase: { type: Object, required: true },
        timelineMeta: { type: Object, required: true },
        draggingPhaseId: { type: [Number, null], default: null },
    },
    emits: ['track-ref', 'drag-start'],
    setup(rowProps, { emit }) {
        return () =>
            h('div', [
                h('div', { class: 'flex items-center justify-between mb-2' }, [
                    h('div', { class: 'text-sm font-medium text-gray-800' }, rowProps.phase.name),
                    h('div', { class: 'text-xs text-gray-500' }, `${rowProps.phase.progress_pct}%`),
                ]),
                h(
                    'div',
                    {
                        ref: (el) => emit('track-ref', { phaseId: rowProps.phase.id, element: el }),
                        class: 'w-full h-8 bg-gray-100 rounded relative overflow-hidden',
                    },
                    [
                        h('div', {
                            class: [
                                'absolute top-1 h-6 rounded border cursor-grab active:cursor-grabbing transition-colors',
                                rowProps.draggingPhaseId === rowProps.phase.id
                                    ? 'bg-orange-500/70 border-orange-600'
                                    : rowProps.phase.start_date && rowProps.phase.end_date
                                        ? 'bg-orange-400/80 border-orange-500'
                                        : 'bg-gray-300 border-gray-400',
                            ],
                            style:
                                rowProps.timelineMeta.totalDays > 0 && rowProps.phase.start_date && rowProps.phase.end_date
                                    ? phaseBarStyle(rowProps.phase)
                                    : { left: '1%', width: '30%' },
                            onPointerdown: (event) => emit('drag-start', rowProps.phase, event),
                            title: 'Trage bara pentru a muta intervalul etapei',
                        }),
                    ]
                ),
                h('div', { class: 'flex items-center justify-between mt-2 text-xs text-gray-500' }, [
                    h('span', rowProps.phase.start_date ? fmt(rowProps.phase.start_date) : 'fara start'),
                    h('span', rowProps.phase.end_date ? fmt(rowProps.phase.end_date) : 'fara end'),
                ]),
            ]);
    },
};
</script>
