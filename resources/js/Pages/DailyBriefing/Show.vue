<template>
    <AppLayout :title="'Memento zilnic - ' + project.name">
        <div class="max-w-5xl mx-auto space-y-6">
            <div>
                <Link :href="route('projects.show', project.id)" class="text-gray-400 hover:text-gray-600 text-sm">← {{ project.name }}</Link>
                <h2 class="mt-1 text-2xl font-black text-slate-900">Memento Zilnic</h2>
                <p class="mt-1 text-sm text-gray-500">{{ formatDate(briefing.date) }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                    :class="activeTab === tab.key ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div v-if="activeTab === 'astazi'" class="space-y-5">
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

            <div v-else class="bg-white border border-gray-200 rounded-xl p-5 space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-800">Trimitere automata</h3>
                        <p class="text-xs text-gray-500">Trimite mementoul zilnic prin email si notificare in-app la ora setata.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-medium"
                        :class="form.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'"
                        @click="form.enabled = !form.enabled"
                    >
                        {{ form.enabled ? 'Activat' : 'Dezactivat' }}
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ora trimiterii</label>
                    <input v-model="form.send_time" type="time" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nivel de detaliu</label>
                    <div class="flex gap-2">
                        <button
                            v-for="(label, key) in detailLevels"
                            :key="key"
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs font-medium"
                            :class="form.detail_level === key ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-300 text-gray-600'"
                            @click="form.detail_level = key"
                        >
                            {{ label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Canale</label>
                    <div class="flex flex-wrap gap-3 text-sm text-gray-700">
                        <label class="flex items-center gap-1.5">
                            <input v-model="form.channels.email" type="checkbox" />
                            Email
                        </label>
                        <label class="flex items-center gap-1.5">
                            <input v-model="form.channels.in_app" type="checkbox" />
                            Notificare in Modulia
                        </label>
                        <label class="flex items-center gap-1.5 text-gray-400">
                            <input type="checkbox" disabled />
                            WhatsApp (in curand)
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Destinatari</label>
                    <div class="max-h-56 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100">
                        <label v-for="user in tenantUsers" :key="user.id" class="flex items-center gap-2 px-3 py-2 text-sm">
                            <input type="checkbox" :value="user.id" v-model="form.recipient_user_ids" />
                            <span>{{ user.name }} <span class="text-gray-400">({{ user.email }})</span></span>
                        </label>
                        <p v-if="!tenantUsers.length" class="text-sm text-gray-400 px-3 py-2">Niciun utilizator disponibil.</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="saveSettings"
                >
                    Salveaza setarile
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    project: { type: Object, required: true },
    briefing: { type: Object, required: true },
    settings: { type: Object, required: true },
    detailLevels: { type: Object, default: () => ({}) },
    tenantUsers: { type: Array, default: () => [] },
});

const tabs = [
    { key: 'astazi', label: 'Astazi' },
    { key: 'setari', label: 'Setari' },
];

const activeTab = ref('astazi');

const form = useForm({
    enabled: props.settings.enabled,
    send_time: props.settings.send_time,
    recipient_user_ids: props.settings.recipient_user_ids,
    detail_level: props.settings.detail_level,
    channels: { ...props.settings.channels },
});

function saveSettings() {
    form.patch(route('daily-briefing.settings.update', props.project.id), { preserveScroll: true });
}

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

function formatDate(value) {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString('ro-RO', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
</script>
