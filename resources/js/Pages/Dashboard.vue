<template>
    <AppLayout title="Dashboard">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <StatCard icon="🏠" label="Proiecte active" :value="stats.activeProjects" color="blue" />
            <StatCard icon="👷" label="Echipe alocate" :value="stats.teams" color="green" />
            <StatCard icon="📋" label="Oferte trimise" :value="stats.quotes" color="orange" />
            <StatCard icon="🔧" label="Defecte deschise" :value="stats.defects" color="red" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Taskuri restante</div>
                <div class="text-2xl font-semibold text-gray-800">{{ stats.overdueTasks }}</div>
                <div class="text-xs text-gray-500 mt-1">deadline depasit</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Etape intarziate</div>
                <div class="text-2xl font-semibold text-gray-800">{{ stats.delayedPhases }}</div>
                <div class="text-xs text-gray-500 mt-1">in executie / pending</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Progres mediu</div>
                <div class="text-2xl font-semibold text-gray-800">{{ stats.avgProgress }}%</div>
                <div class="text-xs text-gray-500 mt-1">pe etape active</div>
            </div>
        </div>

        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Cost estimat utilaje (toate rezervarile)</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ fmtCur(stats.estimatedEquipmentCost || 0) }}</div>
                    <div class="text-xs text-gray-500 mt-1">calculat pe baza cost/ora, cantitate si interval rezervare</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Documente neplatite / partial</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ stats.documentsUnpaidCount || 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">facturi si documente cu plata incompleta</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Expunere financiara documente</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ fmtCur(stats.documentsUnpaidAmount || 0) }}</div>
                    <div class="text-xs mt-1" :class="(stats.documentsOverdueInvoices || 0) > 0 ? 'text-red-600' : 'text-gray-500'">
                        restante >30 zile: {{ stats.documentsOverdueInvoices || 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Taskuri etapa deschise</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ stats.stageTasksOpen || 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">todo + in_progress + blocked</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Calendar azi</h2>
                    <p class="text-sm text-gray-500 mt-1">Planificare operationala zilnica pe etape, taskuri, utilaje, subcontractori, documente si calitate.</p>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase tracking-wider text-gray-400">{{ todayCalendar.date }}</div>
                    <div class="text-sm font-semibold text-gray-800 mt-1">{{ todayCalendar.total_events || 0 }} evenimente</div>
                    <div class="text-xs mt-1" :class="(todayCalendar.risk_events || 0) > 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ todayCalendar.risk_events || 0 }} riscuri identificate
                    </div>
                </div>
            </div>

            <div v-if="(todayCalendar.total_events || 0) === 0" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                Nu exista evenimente operationale planificate pentru azi.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Etape programate azi</div>
                    <div v-if="todayCalendar.stages?.length" class="space-y-2">
                        <div v-for="item in todayCalendar.stages" :key="`stage-${item.id}`" class="text-xs border rounded-lg px-3 py-2" :class="item.risk ? 'border-red-200 bg-red-50 text-red-800' : 'border-gray-200 bg-gray-50 text-gray-700'">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.project_name || '-' }} · {{ item.status }}</div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara etape azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Taskuri cu deadline azi</div>
                    <div v-if="todayCalendar.tasks?.length" class="space-y-2">
                        <div v-for="item in todayCalendar.tasks" :key="`task-${item.id}`" class="text-xs border rounded-lg px-3 py-2" :class="item.risk ? 'border-red-200 bg-red-50 text-red-800' : 'border-gray-200 bg-gray-50 text-gray-700'">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.status }}</div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara taskuri azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Utilaje rezervate azi</div>
                    <div v-if="todayCalendar.equipment?.length" class="space-y-2">
                        <div v-for="item in todayCalendar.equipment" :key="`equipment-${item.id}`" class="text-xs border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-gray-700">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.time_range }}</div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara utilaje programate azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Subcontractori programati azi</div>
                    <div v-if="todayCalendar.subcontractors?.length" class="space-y-2">
                        <div v-for="item in todayCalendar.subcontractors" :key="`sub-${item.id}`" class="text-xs border rounded-lg px-3 py-2" :class="item.risk ? 'border-red-200 bg-red-50 text-red-800' : 'border-gray-200 bg-gray-50 text-gray-700'">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.window }}</div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara subcontractori programati azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Documente cu termen azi</div>
                    <div v-if="todayCalendar.documents?.length" class="space-y-2">
                        <div v-for="item in todayCalendar.documents" :key="`doc-${item.id}`" class="text-xs border rounded-lg px-3 py-2" :class="item.risk ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-gray-200 bg-gray-50 text-gray-700'">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.project_name || '-' }} · {{ fmtCur(item.amount || 0) }}</div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara documente scadente azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Verificari / calitate azi</div>
                    <div v-if="todayCalendar.quality_checks?.length" class="space-y-2">
                        <div v-for="item in todayCalendar.quality_checks" :key="`qc-${item.id}`" class="text-xs border rounded-lg px-3 py-2" :class="item.risk ? 'border-red-200 bg-red-50 text-red-800' : 'border-gray-200 bg-gray-50 text-gray-700'">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.planned_at || '-' }}</div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara verificari programate azi.</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Atentie azi</h2>
                    <p class="text-sm text-gray-500 mt-1">Elementele care cer actiune rapida din executie, financiar si calitate.</p>
                </div>
                <div v-if="attentionItems.length === 0" class="text-sm font-medium text-green-600">
                    Nicio alerta critica in acest moment.
                </div>
            </div>

            <div v-if="attentionItems.length === 0" class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                Dashboard-ul nu a identificat intarzieri, restante sau blocaje prioritare pentru azi.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <Link
                    v-for="item in attentionItems"
                    :key="item.title"
                    :href="item.href"
                    class="rounded-xl border px-4 py-4 transition hover:shadow-sm"
                    :class="item.tone"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold">{{ item.title }}</div>
                            <div class="text-2xl font-bold mt-2">{{ item.value }}</div>
                        </div>
                        <div class="text-xl">{{ item.icon }}</div>
                    </div>
                    <div class="text-sm mt-2 opacity-90">{{ item.description }}</div>
                    <div class="text-xs mt-3 font-medium opacity-80">{{ item.cta }}</div>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">📁 Proiecte recente</h2>
                    <Link :href="route('projects.index')" class="text-xs text-orange-500 hover:underline">Vezi toate →</Link>
                </div>
                <div v-if="recentProjects.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    <div class="text-3xl mb-2">🏗️</div>
                    Niciun proiect creat inca.<br />
                    <Link :href="route('projects.create')" class="text-orange-500 hover:underline mt-2 inline-block font-medium">
                        + Creeaza primul proiect
                    </Link>
                </div>
                <div v-else class="space-y-3">
                    <Link
                        v-for="p in recentProjects"
                        :key="p.id"
                        :href="route('projects.show', p.id)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ p.name }}</div>
                            <div class="text-xs text-gray-400">{{ p.client?.name ?? 'Fara client' }}</div>
                        </div>
                        <StatusBadge :status="p.status" />
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">✅ Taskuri pentru azi</h2>
                    <Link :href="route('tasks.index')" class="text-xs text-orange-500 hover:underline">Vezi toate →</Link>
                </div>
                <div v-if="todayTasks.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    <div class="text-3xl mb-2">✅</div>
                    Niciun task pentru azi.
                    <Link :href="route('tasks.create')" class="text-orange-500 hover:underline mt-2 inline-block font-medium">
                        + Creeaza task
                    </Link>
                </div>
                <div v-else class="space-y-3">
                    <Link
                        v-for="task in todayTasks"
                        :key="task.id"
                        :href="route('tasks.edit', task.id)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ task.title }}</div>
                            <div class="text-xs text-gray-400">{{ task.project?.name ?? 'Fara proiect' }}</div>
                        </div>
                        <StatusBadge :status="task.status === 'in_progress' ? 'active' : task.status === 'done' ? 'completed' : task.status === 'cancelled' ? 'cancelled' : 'draft'" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">⏱️ Etape intarziate</h2>
                <div v-if="delayedPhases.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Nicio etapa intarziata.
                </div>
                <div v-else class="space-y-3">
                    <div v-for="phase in delayedPhases" :key="phase.id" class="p-3 rounded-lg border border-gray-100">
                        <div class="text-sm font-medium text-gray-800">{{ phase.name }}</div>
                        <div class="text-xs text-gray-500">{{ phase.project?.name ?? 'Fara proiect' }} · termen {{ fmt(phase.end_date) }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-gray-800">🔧 Defecte deschise</h2>
                    <Link :href="route('defects.index')" class="text-xs text-orange-500 hover:underline">Vezi toate →</Link>
                </div>
                <div v-if="openDefects.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Nu exista defecte deschise.
                </div>
                <div v-else class="space-y-3">
                    <Link
                        v-for="defect in openDefects"
                        :key="defect.id"
                        :href="route('defects.edit', defect.id)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition border border-gray-100"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ defect.title }}</div>
                            <div class="text-xs text-gray-500">{{ defect.project?.name ?? 'Fara proiect' }}</div>
                        </div>
                        <StatusBadge :status="defect.status === 'in_progress' ? 'active' : 'draft'" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">📌 Plan vs Real pe etape</h2>
            <div v-if="stagePlanVsReal.length === 0" class="text-center py-8 text-gray-400 text-sm">
                Nu exista date de comparatie pentru etape.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-3">Proiect</th>
                            <th class="py-2 pr-3">Etapa</th>
                            <th class="py-2 pr-3">Plan %</th>
                            <th class="py-2 pr-3">Real %</th>
                            <th class="py-2 pr-3">Delta</th>
                            <th class="py-2 pr-3">Cost documentat</th>
                            <th class="py-2 pr-3">Ultim raport</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in stagePlanVsReal" :key="item.id" class="border-b last:border-0">
                            <td class="py-2 pr-3">{{ item.project_name || '-' }}</td>
                            <td class="py-2 pr-3">{{ item.stage_name }}</td>
                            <td class="py-2 pr-3">{{ item.planned_progress }}%</td>
                            <td class="py-2 pr-3">{{ item.actual_progress }}%</td>
                            <td class="py-2 pr-3" :class="item.progress_delta >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ item.progress_delta >= 0 ? '+' : '' }}{{ item.progress_delta }}%
                            </td>
                            <td class="py-2 pr-3 font-medium">{{ fmtCur(item.documented_cost) }}</td>
                            <td class="py-2 pr-3 text-gray-500">{{ item.latest_report_date || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    stats:          { type: Object, default: () => ({ activeProjects: 0, teams: 0, quotes: 0, defects: 0, overdueTasks: 0, delayedPhases: 0, avgProgress: 0, estimatedEquipmentCost: 0, documentsUnpaidCount: 0, documentsUnpaidAmount: 0, documentsOverdueInvoices: 0, stageTasksOpen: 0 }) },
    recentProjects: { type: Array,  default: () => [] },
    todayTasks:     { type: Array,  default: () => [] },
    todayCalendar:  { type: Object, default: () => ({ date: '', total_events: 0, risk_events: 0, stages: [], tasks: [], equipment: [], subcontractors: [], documents: [], quality_checks: [] }) },
    delayedPhases:  { type: Array,  default: () => [] },
    openDefects:    { type: Array,  default: () => [] },
    stagePlanVsReal:{ type: Array,  default: () => [] },
});

const attentionItems = computed(() => {
    const items = [];

    if ((props.stats.overdueTasks || 0) > 0) {
        items.push({
            title: 'Taskuri depasite',
            value: props.stats.overdueTasks,
            description: 'Exista taskuri generale care au depasit termenul si trebuie replanificate.',
            cta: 'Deschide taskurile restante',
            href: route('tasks.index', { status: 'todo,in_progress' }),
            icon: '⏰',
            tone: 'border-amber-200 bg-amber-50 text-amber-900',
        });
    }

    if ((props.stats.delayedPhases || 0) > 0) {
        items.push({
            title: 'Etape intarziate',
            value: props.stats.delayedPhases,
            description: props.delayedPhases[0]?.name
                ? `Prima etapa cu risc: ${props.delayedPhases[0].name}.`
                : 'Exista etape in executie sau pending care au depasit termenul.',
            cta: 'Verifica WBS si replanificarea',
            href: route('wbs.index', { status: 'delayed' }),
            icon: '📌',
            tone: 'border-red-200 bg-red-50 text-red-900',
        });
    }

    if ((props.stats.documentsOverdueInvoices || 0) > 0) {
        items.push({
            title: 'Restante financiare',
            value: props.stats.documentsOverdueInvoices,
            description: 'Exista facturi sau documente neachitate de peste 30 de zile.',
            cta: 'Deschide documentele financiare',
            href: route('documents.index', { payment_status: 'unpaid' }),
            icon: '💸',
            tone: 'border-rose-200 bg-rose-50 text-rose-900',
        });
    }

    if ((props.stats.stageTasksOpen || 0) > 0) {
        items.push({
            title: 'Taskuri de etapa deschise',
            value: props.stats.stageTasksOpen,
            description: 'Etapele active au taskuri operationale care cer urmarire zilnica.',
            cta: 'Deschide taskurile pe etapa',
            href: route('stage-tasks.index', { status: 'todo,in_progress,blocked' }),
            icon: '🧱',
            tone: 'border-blue-200 bg-blue-50 text-blue-900',
        });
    }

    return items.slice(0, 4);
});

function fmt(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtCur(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(value || 0);
}
</script>