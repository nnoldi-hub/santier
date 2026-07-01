<template>
    <AppLayout title="Gantt">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Planificare Gantt</h2>
                <p class="text-sm text-gray-500 mt-1">Vizualizare timeline pe etape de proiect</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="projectId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="changeProject">
                        <option value="">— Selecteaza proiect —</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Etape in proiect</div>
                    <div class="text-lg font-semibold text-gray-800">{{ phases.length }}</div>
                </div>
            </div>
        </div>

        <div v-if="!selectedProjectId" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
            Selecteaza un proiect pentru a vedea planificarea Gantt.
        </div>

        <div v-else-if="phases.length === 0" class="bg-white rounded-xl border border-gray-200 p-12 text-center text-gray-400 text-sm">
            Proiectul selectat nu are etape definite.
        </div>

        <div v-else class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="mb-4">
                <h3 class="font-semibold text-gray-800">{{ selectedProject?.name || 'Proiect' }}</h3>
                <p class="text-xs text-gray-500 mt-1">Status: {{ selectedProject?.status || 'n/a' }}</p>
            </div>

            <div class="space-y-3">
                <div v-for="phase in phases" :key="phase.id" class="border border-gray-100 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium text-gray-800">{{ phase.name }}</div>
                        <div class="text-xs text-gray-500">{{ phase.progress_pct }}%</div>
                    </div>

                    <div class="w-full h-8 bg-gray-100 rounded relative overflow-hidden">
                        <div
                            v-if="timelineMeta.totalDays > 0 && phase.start_date && phase.end_date"
                            class="absolute top-1 h-6 rounded bg-orange-400/80 border border-orange-500"
                            :style="phaseBarStyle(phase)"
                        ></div>
                        <div
                            v-else
                            class="absolute top-1 left-1 h-6 rounded bg-gray-300 border border-gray-400"
                            style="width: 30%"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                        <span>{{ phase.start_date ? fmt(phase.start_date) : 'fara start' }}</span>
                        <span>{{ phase.end_date ? fmt(phase.end_date) : 'fara end' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    projects: { type: Array, default: () => [] },
    selectedProjectId: { type: Number, default: null },
    selectedProject: { type: Object, default: null },
    phases: { type: Array, default: () => [] },
});

const projectId = ref(props.selectedProjectId ? String(props.selectedProjectId) : '');

const timelineMeta = computed(() => {
    const datedPhases = props.phases.filter((phase) => phase.start_date && phase.end_date);

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

function changeProject() {
    router.get(route('gantt.index'), { project_id: projectId.value || '' }, { preserveState: true, preserveScroll: true });
}

function phaseBarStyle(phase) {
    const start = new Date(phase.start_date);
    const end = new Date(phase.end_date);

    const leftDays = Math.max(diffDays(timelineMeta.value.minDate, start), 0);
    const duration = Math.max(diffDays(start, end), 1);

    const leftPct = (leftDays / timelineMeta.value.totalDays) * 100;
    const widthPct = (duration / timelineMeta.value.totalDays) * 100;

    return {
        left: `${leftPct}%`,
        width: `${Math.min(widthPct, 100 - leftPct)}%`,
    };
}

function diffDays(dateA, dateB) {
    const oneDayMs = 24 * 60 * 60 * 1000;
    return Math.ceil((dateB.getTime() - dateA.getTime()) / oneDayMs);
}

function fmt(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>
