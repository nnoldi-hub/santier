<template>
    <AppLayout title="Invitari firme pilot">
        <div class="max-w-6xl mx-auto space-y-6">
            <section class="rounded-3xl border border-orange-200 bg-white p-6 shadow-sm">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                        <Icon :icon="RocketLaunchIcon" size="h-3.5 w-3.5" />
                        Pipeline comercial
                    </div>
                    <h2 class="mt-3 text-3xl font-black text-slate-900">Invitare primele firme pilot</h2>
                    <p class="mt-2 max-w-3xl text-sm text-slate-600">
                        Gestioneaza pipeline-ul comercial: invitat -> contactat -> demo -> trial -> rezultat.
                    </p>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <article v-for="card in kpiCards" :key="card.key" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ card.value }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ card.note }}</div>
                </article>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h3 class="font-semibold text-gray-800 mb-3">Adauga firma pilot</h3>
                <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="createInvite">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Companie *</label>
                        <input v-model="form.company_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Segment</label>
                        <input v-model="form.segment" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="renovari / mentenanta" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Contact</label>
                        <input v-model="form.contact_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Email contact *</label>
                        <input v-model="form.contact_email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Telefon</label>
                        <input v-model="form.contact_phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Utilizatori estimati *</label>
                        <input v-model="form.estimated_users" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Personalizare dorita *</label>
                        <select v-model="form.customization_scope" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="branding">Branding documente</option>
                            <option value="template">Template documente</option>
                            <option value="approvals">Flux aprobari</option>
                            <option value="white_label">White-label</option>
                            <option value="custom_domain">Domeniu propriu</option>
                            <option value="full_enterprise">Pachet enterprise complet</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Responsabil comercial</label>
                        <select v-model="form.owner_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Alege responsabil</option>
                            <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }} · {{ owner.email }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Urmarire</label>
                        <input v-model="form.follow_up_at" type="datetime-local" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600 mb-1">Urmator pas</label>
                        <input v-model="form.next_step" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex: programeaza demo sau trimite oferta" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600 mb-1">Note</label>
                        <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div class="md:col-span-3">
                        <button :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                            {{ form.processing ? 'Se salveaza...' : 'Adauga invitatie' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="flex items-end gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Filtru status</label>
                        <select v-model="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Toate</option>
                            <option v-for="status in statusOptions" :key="status" :value="status">{{ labelStatus(status) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Filtru etapa comerciala</label>
                        <select v-model="commercialStageFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Toate</option>
                            <option v-for="(label, key) in stageOptions" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Filtru personalizare</label>
                        <select v-model="customizationFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Toate</option>
                            <option v-for="(label, key) in customizationOptions" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Sortare</label>
                        <select v-model="sortBy" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="users_desc">Utilizatori (desc)</option>
                            <option value="latest">Cele mai noi</option>
                        </select>
                    </div>
                    <button @click="applyFilter" class="bg-[#1A237E] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#141b5c]">Filtreaza</button>
                    <button @click="resetFilter" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>

            <div v-if="invites.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-10 text-center text-sm text-gray-500">
                Nu exista invitatii inca.
            </div>

            <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Companie</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Contact</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Profil lead</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Stadiu comercial</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Urmarire</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Task comercial</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Jurnal actiuni</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Responsabil</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="invite in displayedInvites" :key="invite.id">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ invite.company_name }}</div>
                                <div class="text-xs text-gray-500">Invitat: {{ formatDate(invite.invited_at) }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div>{{ invite.contact_name || '-' }}</div>
                                <div class="text-xs text-gray-500">{{ invite.contact_email }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="font-medium text-gray-700">{{ invite.segment || '-' }}</div>
                                <div class="mt-1 text-xs text-gray-500">{{ invite.contact_phone || '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="flex items-center gap-2">
                                    <span>{{ invite.estimated_users ?? '-' }}</span>
                                    <span v-if="isHighValue(invite.estimated_users)" class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                        High value
                                    </span>
                                </div>
                                <div class="mt-1 text-xs">
                                    <span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 font-semibold text-sky-700">
                                        {{ labelStage(inviteDrafts[invite.id]?.commercial_stage || invite.commercial_stage) }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">{{ invite.customization_scope_label || '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="space-y-2">
                                    <select v-model="inviteDrafts[invite.id].status" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                        <option v-for="status in statusOptions" :key="status" :value="status">{{ labelStatus(status) }}</option>
                                    </select>
                                    <select v-model="inviteDrafts[invite.id].commercial_stage" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                        <option v-for="(label, key) in stageOptions" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <input v-model="inviteDrafts[invite.id].demo_scheduled_at" type="datetime-local" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="space-y-2">
                                    <input v-model="inviteDrafts[invite.id].follow_up_at" type="datetime-local" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" />
                                    <input v-model="inviteDrafts[invite.id].next_step" type="text" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" placeholder="Urmator pas" />
                                    <div class="text-[11px] text-gray-400">Ultim contact: {{ formatDateTime(invite.last_contacted_at) }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div v-if="invite.commercial_task" class="space-y-1">
                                    <div class="text-xs font-semibold text-gray-800">{{ invite.commercial_task.title }}</div>
                                    <div class="text-[11px] text-gray-500">Scadenta: {{ formatDateTime(invite.commercial_task.due_at) }}</div>
                                    <div class="text-[11px]">
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700">
                                            {{ labelTaskPriority(invite.commercial_task.priority) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-else class="text-xs text-gray-400">Nu exista task deschis.</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div v-if="invite.commercial_actions?.length" class="text-xs text-gray-700 mb-1">
                                    <span class="font-semibold">{{ labelActionType(invite.commercial_actions[0].action_type) }}</span>
                                    <span class="text-gray-400"> · {{ formatDateTime(invite.commercial_actions[0].created_at) }}</span>
                                    <span v-if="invite.commercial_actions[0].actor_name" class="text-gray-400"> · {{ invite.commercial_actions[0].actor_name }}</span>
                                </div>
                                <div v-else class="text-xs text-gray-400 mb-1">Nicio actiune inregistrata.</div>

                                <button
                                    v-if="expandedActionInviteId !== invite.id"
                                    type="button"
                                    @click="expandActionForm(invite.id)"
                                    class="text-[11px] text-blue-600 hover:underline"
                                >
                                    + Adauga actiune
                                </button>

                                <div v-else class="space-y-1 mt-1">
                                    <select v-model="actionForm.action_type" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                        <option v-for="(label, key) in actionTypes" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <textarea v-model="actionForm.notes" rows="2" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" placeholder="Detalii (optional)"></textarea>
                                    <div class="flex gap-1">
                                        <button type="button" :disabled="actionForm.processing" @click="submitAction(invite)" class="bg-emerald-600 text-white px-2 py-1 rounded text-[11px] hover:bg-emerald-700 disabled:opacity-60">
                                            {{ actionForm.processing ? 'Se salveaza...' : 'Salveaza' }}
                                        </button>
                                        <button type="button" @click="collapseActionForm" class="border border-gray-300 px-2 py-1 rounded text-[11px] text-gray-600">Renunta</button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="space-y-2">
                                    <select v-model="inviteDrafts[invite.id].owner_id" class="w-full border border-gray-300 rounded px-2 py-1 text-xs">
                                        <option value="">Fara responsabil</option>
                                        <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
                                    </select>
                                    <textarea v-model="inviteDrafts[invite.id].notes" rows="2" class="w-full border border-gray-300 rounded px-2 py-1 text-xs" placeholder="Note comerciale"></textarea>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button :disabled="updatingInviteId === invite.id" @click="saveInvite(invite)" class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-2 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-60">
                                    {{ updatingInviteId === invite.id ? 'Se salveaza...' : 'Salveaza' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Icon from '@/Components/Icon.vue';
import { RocketLaunchIcon } from '@heroicons/vue/24/outline';
import { labelCommercialActionType, labelCommercialStage, labelCommercialStatus } from '@/Support/commercialLabels';

const props = defineProps({
    invites: { type: Object, required: true },
    owners: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    stageOptions: { type: Object, default: () => ({}) },
    customizationOptions: { type: Object, default: () => ({}) },
    actionTypes: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters?.status || '');
const commercialStageFilter = ref(props.filters?.commercial_stage || '');
const customizationFilter = ref(props.filters?.customization || '');
const sortBy = ref(props.filters?.sort || 'users_desc');
const updatingInviteId = ref(null);
const expandedActionInviteId = ref(null);

const actionForm = useForm({
    action_type: 'apel',
    notes: '',
});

const form = useForm({
    company_name: '',
    segment: '',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    owner_id: '',
    estimated_users: 10,
    customization_scope: 'branding',
    follow_up_at: '',
    next_step: '',
    notes: '',
});

const inviteDrafts = ref(buildInviteDrafts(props.invites?.data || []));

watch(() => props.invites?.data, (value) => {
    inviteDrafts.value = buildInviteDrafts(value || []);
}, { deep: true });

const kpiCards = computed(() => {
    const rows = Array.isArray(props.invites?.data) ? props.invites.data : [];

    return [
        { key: 'total', label: 'Total invitatii', value: props.invites?.total ?? rows.length, note: 'In tot pipeline-ul' },
        { key: 'high_value', label: 'High value', value: rows.filter((invite) => isHighValue(invite.estimated_users)).length, note: 'Pe pagina curenta' },
        { key: 'demo', label: 'Demo programate', value: rows.filter((invite) => invite.status === 'demo_scheduled').length, note: 'Pe pagina curenta' },
        { key: 'won', label: 'Castigate', value: rows.filter((invite) => invite.status === 'closed_won').length, note: 'Pe pagina curenta' },
    ];
});

const displayedInvites = computed(() => {
    const source = Array.isArray(props.invites?.data) ? [...props.invites.data] : [];

    if (sortBy.value === 'latest') {
        return source;
    }

    return source.sort((a, b) => Number(b?.estimated_users || 0) - Number(a?.estimated_users || 0));
});

function createInvite() {
    form.post(route('pilot-invites.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function applyFilter() {
    router.get(route('pilot-invites.index'), {
        status: statusFilter.value,
        commercial_stage: commercialStageFilter.value,
        customization: customizationFilter.value,
        sort: sortBy.value,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilter() {
    statusFilter.value = '';
    commercialStageFilter.value = '';
    customizationFilter.value = '';
    sortBy.value = 'users_desc';
    applyFilter();
}

function saveInvite(invite) {
    const draft = inviteDrafts.value[invite.id];
    updatingInviteId.value = invite.id;

    router.patch(route('pilot-invites.status', invite.id), {
        status: draft.status,
        commercial_stage: draft.commercial_stage || null,
        owner_id: draft.owner_id || null,
        demo_scheduled_at: draft.demo_scheduled_at || null,
        follow_up_at: draft.follow_up_at || null,
        next_step: draft.next_step || null,
        notes: draft.notes || null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            updatingInviteId.value = null;
        },
    });
}

function expandActionForm(inviteId) {
    actionForm.clearErrors();
    actionForm.action_type = 'apel';
    actionForm.notes = '';
    expandedActionInviteId.value = inviteId;
}

function collapseActionForm() {
    expandedActionInviteId.value = null;
}

function submitAction(invite) {
    actionForm.post(route('pilot-invites.actions.store', invite.id), {
        preserveScroll: true,
        onSuccess: () => {
            expandedActionInviteId.value = null;
        },
    });
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('ro-RO');
}

function formatDateTime(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('ro-RO');
}

const labelStatus = labelCommercialStatus;
const labelStage = (stage) => props.stageOptions?.[stage] || labelCommercialStage(stage);
const labelActionType = (type) => props.actionTypes?.[type] || labelCommercialActionType(type);

function isHighValue(estimatedUsers) {
    return Number(estimatedUsers || 0) >= 50;
}

function labelTaskPriority(priority) {
    if (priority === 'high') return 'Prioritate mare';
    if (priority === 'medium') return 'Prioritate medie';
    return 'Prioritate redusa';
}

function buildInviteDrafts(invites) {
    return Object.fromEntries((invites || []).map((invite) => [invite.id, {
        status: invite.status,
        commercial_stage: invite.commercial_stage || inferStageFromStatus(invite.status),
        owner_id: invite.owner?.id ?? '',
        demo_scheduled_at: formatDateInput(invite.demo_scheduled_at),
        follow_up_at: formatDateInput(invite.follow_up_at),
        next_step: invite.next_step || '',
        notes: invite.notes || '',
    }]));
}

function inferStageFromStatus(status) {
    const map = {
        invited: 'prospecting',
        contacted: 'contacted',
        demo_scheduled: 'demo',
        trial_started: 'trial',
        closed_won: 'won',
        closed_lost: 'lost',
    };

    return map[status] || 'prospecting';
}

function formatDateInput(value) {
    if (!value) {
        return '';
    }

    return new Date(value).toISOString().slice(0, 16);
}
</script>
