<template>
    <div class="space-y-5">
        <div v-if="briefing.blockers.length" class="rounded-xl border border-red-200 bg-red-50 p-4">
            <h3 class="font-semibold text-red-800 mb-2">Blocaje ({{ briefing.blockers.length }})</h3>
            <ul class="space-y-1 text-sm text-red-800">
                <li v-for="(blocker, index) in briefing.blockers" :key="index">• {{ blocker }}</li>
            </ul>
        </div>

        <div v-if="briefing.recommendations.length" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <h3 class="font-semibold text-amber-800 mb-2">Recomandari</h3>
            <ul class="space-y-1 text-sm text-amber-800">
                <li v-for="(rec, index) in briefing.recommendations" :key="index">• {{ rec.message }}</li>
            </ul>
        </div>

        <div v-if="briefing.timeline?.length" class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Cronologie</h3>
            <div class="space-y-2">
                <div v-for="(entry, index) in briefing.timeline" :key="index" class="flex items-center gap-3 text-sm">
                    <span class="w-20 shrink-0 text-xs font-medium text-gray-500">{{ entry.all_day ? 'Toata ziua' : entry.time }}</span>
                    <span :class="entry.blocked ? 'text-red-700 font-medium' : 'text-gray-700'">{{ entry.label }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Echipe programate azi ({{ briefing.teams.length }})</h3>
            <p v-if="!briefing.teams.length" class="text-sm text-gray-400">Nicio echipa programata azi.</p>
            <div v-else class="space-y-2">
                <div v-for="team in briefing.teams" :key="'team-' + team.id" class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-800">{{ team.team_name }}</span>
                        <span class="text-gray-500"> - {{ team.phase_name || 'fara etapa' }}</span>
                        <div class="text-xs text-gray-500">{{ team.workers_assigned }}/{{ team.workers_needed }} muncitori</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="badgeClass(team.confirmation_status)">{{ badgeLabel(team.confirmation_status) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Subcontractori programati azi ({{ briefing.subcontractors.length }})</h3>
            <p v-if="!briefing.subcontractors.length" class="text-sm text-gray-400">Niciun subcontractor programat azi.</p>
            <div v-else class="space-y-2">
                <div v-for="sub in briefing.subcontractors" :key="'sub-' + sub.id" class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-800">{{ sub.contractor_name }}</span>
                        <span class="text-gray-500"> - {{ sub.phase_name }}</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="badgeClass(sub.confirmation_status)">{{ badgeLabel(sub.confirmation_status) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Materiale cu livrare azi ({{ briefing.materials.length }})</h3>
            <p v-if="!briefing.materials.length" class="text-sm text-gray-400">Nicio livrare de material programata azi.</p>
            <div v-else class="space-y-2">
                <div v-for="material in briefing.materials" :key="'mat-' + material.id" class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-800">{{ material.material_name }}</span>
                        <span class="text-gray-500" v-if="material.ordered_quantity"> - {{ material.ordered_quantity }} {{ material.ordered_unit }}</span>
                        <div class="text-xs text-gray-500" v-if="material.supplier_name">{{ material.supplier_name }}</div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="badgeClass(material.confirmation_status)">{{ badgeLabel(material.confirmation_status) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Utilaje rezervate azi ({{ briefing.equipment.length }})</h3>
            <p v-if="!briefing.equipment.length" class="text-sm text-gray-400">Niciun utilaj rezervat azi.</p>
            <div v-else class="space-y-2">
                <div v-for="item in briefing.equipment" :key="'eq-' + item.id" class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-800">{{ item.equipment_name }}</span>
                        <span class="text-gray-500"> - {{ item.phase_name || 'fara etapa' }}</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="badgeClass(item.confirmation_status)">{{ badgeLabel(item.confirmation_status) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Documente cu scadenta azi ({{ briefing.documents.length }})</h3>
            <p v-if="!briefing.documents.length" class="text-sm text-gray-400">Niciun document cu scadenta azi.</p>
            <div v-else class="space-y-2">
                <div v-for="doc in briefing.documents" :key="'doc-' + doc.id" class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-800">{{ doc.title }}</span>
                        <span class="text-gray-500"> - {{ doc.item_type_label }}</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="badgeClass(doc.status)">{{ badgeLabel(doc.status) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Task-uri critice azi ({{ briefing.tasks.length }})</h3>
            <p v-if="!briefing.tasks.length" class="text-sm text-gray-400">Niciun task critic azi.</p>
            <div v-else class="space-y-2">
                <div v-for="task in briefing.tasks" :key="task.source + '-' + task.id" class="flex items-center justify-between border border-gray-100 rounded-lg px-3 py-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-800">{{ task.title }}</span>
                        <span class="text-gray-500" v-if="task.phase_name"> - {{ task.phase_name }}</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="badgeClass(task.status)">{{ badgeLabel(task.status) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    briefing: { type: Object, required: true },
});

const badgeLabels = {
    confirmat: 'Confirmat',
    planificat: 'Planificat',
    risc: 'Risc',
    neconfirmat: 'Neconfirmat',
    blocked: 'Blocat',
    valid: 'Valid',
    expiring_soon: 'Expira curand',
    expired: 'Expirat',
    missing: 'Lipsa',
    todo: 'De facut',
    in_progress: 'In progres',
    done: 'Finalizat',
};

const badgeColors = {
    confirmat: 'bg-green-100 text-green-700',
    planificat: 'bg-gray-100 text-gray-600',
    risc: 'bg-red-100 text-red-600',
    neconfirmat: 'bg-amber-100 text-amber-700',
    blocked: 'bg-red-100 text-red-600',
    valid: 'bg-green-100 text-green-700',
    expiring_soon: 'bg-amber-100 text-amber-700',
    expired: 'bg-red-100 text-red-600',
    missing: 'bg-red-100 text-red-600',
    todo: 'bg-gray-100 text-gray-600',
    in_progress: 'bg-amber-100 text-amber-700',
    done: 'bg-green-100 text-green-700',
};

function badgeLabel(status) {
    return badgeLabels[status] ?? status;
}

function badgeClass(status) {
    return badgeColors[status] ?? 'bg-gray-100 text-gray-600';
}
</script>
