<template>
    <AppLayout title="Invitari firme pilot">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Invitare primele firme pilot</h2>
                    <p class="text-sm text-gray-500 mt-1">Gestioneaza pipeline-ul comercial: invitat -> contactat -> demo -> trial -> rezultat.</p>
                </div>
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
                    <button @click="applyFilter" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
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
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Segment</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Utilizatori</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Personalizare</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500">Owner</th>
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
                                <div class="text-xs text-gray-500">{{ invite.contact_phone || '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ invite.segment || '-' }}</td>
                            <td class="px-4 py-3 text-gray-600">
                                <div class="flex items-center gap-2">
                                    <span>{{ invite.estimated_users ?? '-' }}</span>
                                    <span v-if="isHighValue(invite.estimated_users)" class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                        High value
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ invite.customization_scope_label || '-' }}</td>
                            <td class="px-4 py-3">
                                <select :value="invite.status" @change="changeStatus(invite, $event)" class="border border-gray-300 rounded px-2 py-1 text-xs">
                                    <option v-for="status in statusOptions" :key="status" :value="status">{{ labelStatus(status) }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ invite.owner?.name || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    invites: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    statusOptions: { type: Array, default: () => [] },
    customizationOptions: { type: Object, default: () => ({}) },
});

const statusFilter = ref(props.filters?.status || '');
const customizationFilter = ref(props.filters?.customization || '');
const sortBy = ref(props.filters?.sort || 'users_desc');

const form = useForm({
    company_name: '',
    segment: '',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    estimated_users: 10,
    customization_scope: 'branding',
    notes: '',
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
        customization: customizationFilter.value,
        sort: sortBy.value,
    }, { preserveState: true, preserveScroll: true });
}

function resetFilter() {
    statusFilter.value = '';
    customizationFilter.value = '';
    sortBy.value = 'users_desc';
    applyFilter();
}

function changeStatus(invite, event) {
    router.patch(route('pilot-invites.status', invite.id), {
        status: event.target.value,
    }, {
        preserveScroll: true,
    });
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('ro-RO');
}

function labelStatus(status) {
    return {
        invited: 'Invitat',
        contacted: 'Contactat',
        demo_scheduled: 'Demo programat',
        trial_started: 'Trial pornit',
        closed_won: 'Castigat',
        closed_lost: 'Pierdut',
    }[status] || status;
}

function isHighValue(estimatedUsers) {
    return Number(estimatedUsers || 0) >= 50;
}
</script>
