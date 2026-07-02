<template>
    <AppLayout title="Roluri si permisiuni">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Rol custom nou</h2>
                <p class="text-sm text-gray-500 mt-1">Creeaza roluri specifice firmei si alege exact permisiunile necesare.</p>

                <div class="mt-3 inline-flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs">
                    <label class="inline-flex items-center gap-2 text-gray-700">
                        <input v-model="hideTechnicalKeys" type="checkbox" class="rounded border-gray-300 text-orange-500" />
                        <span>Ascunde cheile tehnice</span>
                    </label>
                    <span class="text-gray-500">{{ hideTechnicalKeys ? 'Afisezi doar denumiri usor de inteles' : 'Afisezi si cheile tehnice' }}</span>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="createRole">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Nume rol</label>
                        <input v-model="createForm.name" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="ex: estimator_junior" required />
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500">Preset rapid</label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="preset in presets"
                                :key="preset.key"
                                type="button"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                @click="applyPresetToCreate(preset.key)"
                            >
                                {{ preset.label }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500">Permisiuni</label>
                        <input
                            v-model="createPermissionSearch"
                            type="text"
                            class="mt-2 w-full rounded-lg border-gray-300 text-sm"
                            placeholder="Cauta dupa modul sau actiune (ex: proiecte, editare, export)"
                        />
                        <div class="mt-2 max-h-72 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-3">
                            <div v-for="group in createPermissionGroups" :key="group.moduleKey" class="rounded-lg border border-gray-100 bg-gray-50/70 p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">{{ group.moduleLabel }}</div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="rounded border border-emerald-300 px-2 py-1 text-[11px] font-medium text-emerald-700 hover:bg-emerald-50"
                                            @click="selectModule('create', group.moduleKey)"
                                        >
                                            Selecteaza tot
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded border border-amber-300 px-2 py-1 text-[11px] font-medium text-amber-700 hover:bg-amber-50"
                                            @click="deselectModule('create', group.moduleKey)"
                                        >
                                            Deselecteaza
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
                                    <label v-for="permission in group.items" :key="permission.id" class="flex items-start gap-2 text-xs text-gray-700">
                                        <input type="checkbox" :value="permission.name" v-model="createForm.permissions" class="mt-0.5 rounded border-gray-300 text-orange-500" />
                                        <span>
                                            <span class="font-medium">{{ permission.label || permission.name }}</span>
                                            <span v-if="!hideTechnicalKeys" class="block text-[11px] text-gray-500">{{ permission.name }}</span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div v-if="createPermissionGroups.length === 0" class="text-xs text-gray-500">
                                Nu exista permisiuni care sa corespunda cautarii.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm font-medium hover:bg-slate-800 disabled:opacity-60" :disabled="createForm.processing">
                        Creeaza rol
                    </button>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Roluri disponibile</h3>
                    <span class="text-xs text-gray-500">{{ roles.length }} roluri</span>
                </div>

                <div class="divide-y divide-gray-100">
                    <div v-for="role in roles" :key="role.id" class="p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="font-semibold text-gray-900">{{ role.label || role.name }}</div>
                                <div v-if="!hideTechnicalKeys" class="text-[11px] text-gray-500">{{ role.name }}</div>
                                <div class="text-xs" :class="role.is_global ? 'text-sky-600' : 'text-emerald-600'">
                                    {{ role.is_global ? 'Rol global' : 'Rol custom tenant' }}
                                </div>
                            </div>
                            <div v-if="!role.is_global" class="flex items-center gap-2">
                                <button type="button" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50" @click="loadRole(role)">Editeaza</button>
                                <button type="button" class="rounded-lg border border-red-300 text-red-700 px-3 py-1.5 text-xs hover:bg-red-50" @click="deleteRole(role)">Sterge</button>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-600 break-all">
                            {{ role.permissions?.map((item) => item.label || item.name).join(', ') || 'Fara permisiuni setate' }}
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="editForm.id" class="rounded-2xl border border-orange-200 bg-orange-50 p-5">
                <h3 class="font-semibold text-gray-900">Editeaza rol: {{ editForm.name }}</h3>
                <form class="mt-4 space-y-4" @submit.prevent="saveRole">
                    <div>
                        <label class="text-xs font-semibold text-gray-500">Nume rol</label>
                        <input v-model="editForm.name" type="text" class="mt-1 w-full rounded-lg border-gray-300 text-sm" required />
                    </div>

                    <div>
                        <input
                            v-model="editPermissionSearch"
                            type="text"
                            class="w-full rounded-lg border-gray-300 text-sm"
                            placeholder="Cauta permisiuni in rolul editat"
                        />
                    </div>

                    <div class="max-h-72 overflow-y-auto border border-orange-200 bg-white rounded-lg p-3 space-y-3">
                        <div v-for="group in editPermissionGroups" :key="group.moduleKey" class="rounded-lg border border-orange-100 bg-orange-50/30 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">{{ group.moduleLabel }}</div>
                                <div class="flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="rounded border border-emerald-300 px-2 py-1 text-[11px] font-medium text-emerald-700 hover:bg-emerald-50"
                                        @click="selectModule('edit', group.moduleKey)"
                                    >
                                        Selecteaza tot
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded border border-amber-300 px-2 py-1 text-[11px] font-medium text-amber-700 hover:bg-amber-50"
                                        @click="deselectModule('edit', group.moduleKey)"
                                    >
                                        Deselecteaza
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
                                <label v-for="permission in group.items" :key="permission.id" class="flex items-start gap-2 text-xs text-gray-700">
                                    <input type="checkbox" :value="permission.name" v-model="editForm.permissions" class="mt-0.5 rounded border-gray-300 text-orange-500" />
                                    <span>
                                        <span class="font-medium">{{ permission.label || permission.name }}</span>
                                        <span v-if="!hideTechnicalKeys" class="block text-[11px] text-gray-500">{{ permission.name }}</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div v-if="editPermissionGroups.length === 0" class="text-xs text-gray-500">
                            Nu exista permisiuni care sa corespunda cautarii.
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-orange-500 px-4 py-2 text-white text-sm font-medium hover:bg-orange-600" :disabled="editForm.processing">
                            Salveaza modificari
                        </button>
                        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-white" @click="resetEdit">
                            Renunta
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissions: { type: Array, default: () => [] },
});

const createForm = useForm({
    name: '',
    permissions: [],
});

const createPermissionSearch = ref('');
const editPermissionSearch = ref('');
const hideTechnicalKeys = ref(true);

const editForm = useForm({
    id: null,
    name: '',
    permissions: [],
});

const moduleLabels = {
    quotes: 'Oferte / Devize',
    projects: 'Proiecte',
    tasks: 'Taskuri',
    calendar: 'Calendar',
    documents: 'Documente',
    finance: 'Financiar',
    ai_tools: 'Instrumente AI',
    company_settings: 'Setari firma',
    users: 'Utilizatori',
    reports: 'Rapoarte',
    contractors: 'Subcontractori',
    equipment: 'Utilaje',
    roles: 'Roluri',
    tenants: 'Tenants',
};

const presets = [
    { key: 'data_entry', label: 'Preset: Operator date' },
    { key: 'quote_specialist', label: 'Preset: Specialist oferte' },
    { key: 'site_manager', label: 'Preset: Manager santier' },
    { key: 'finance', label: 'Preset: Financiar' },
    { key: 'auditor', label: 'Preset: Auditor' },
];

const presetDefinitions = {
    data_entry: [
        'projects.view',
        'tasks.view', 'tasks.create', 'tasks.edit',
        'documents.view', 'documents.create', 'documents.edit',
        'equipment.view', 'equipment.create', 'equipment.edit',
    ],
    quote_specialist: [
        'quotes.view', 'quotes.create', 'quotes.edit', 'quotes.export',
        'projects.view',
        'finance.view_limited',
    ],
    site_manager: [
        'projects.view', 'projects.create', 'projects.edit',
        'tasks.view', 'tasks.create', 'tasks.edit',
        'calendar.view', 'calendar.create', 'calendar.edit',
        'quotes.view',
        'reports.view', 'reports.create', 'reports.edit',
    ],
    finance: [
        'finance.view', 'finance.create', 'finance.edit', 'finance.export', 'finance.approve',
        'reports.view', 'reports.export',
        'projects.view',
        'quotes.view',
        'documents.view',
    ],
    auditor: [
        'quotes.view',
        'projects.view',
        'tasks.view',
        'calendar.view',
        'documents.view',
        'finance.view',
        'reports.view',
        'contractors.view',
        'equipment.view',
    ],
};

const createPermissionGroups = computed(() => groupPermissions(filterPermissions(createPermissionSearch.value)));
const editPermissionGroups = computed(() => groupPermissions(filterPermissions(editPermissionSearch.value)));

function filterPermissions(search) {
    const term = String(search || '').trim().toLowerCase();
    if (term === '') {
        return props.permissions;
    }

    return props.permissions.filter((permission) => {
        const name = String(permission.name || '').toLowerCase();
        const label = String(permission.label || '').toLowerCase();
        const module = moduleLabelFor(permission).toLowerCase();

        return name.includes(term) || label.includes(term) || module.includes(term);
    });
}

function groupPermissions(permissionList) {
    const grouped = new Map();

    permissionList.forEach((permission) => {
        const moduleKey = String(permission.name || '').split('.')[0] || 'general';
        if (!grouped.has(moduleKey)) {
            grouped.set(moduleKey, {
                moduleKey,
                moduleLabel: moduleLabelFor(permission),
                items: [],
            });
        }

        grouped.get(moduleKey).items.push(permission);
    });

    return Array.from(grouped.values()).sort((a, b) => a.moduleLabel.localeCompare(b.moduleLabel, 'ro'));
}

function moduleLabelFor(permission) {
    const moduleKey = String(permission.name || '').split('.')[0] || 'general';

    return moduleLabels[moduleKey] || moduleKey.replaceAll('_', ' ');
}

function permissionNamesForModule(moduleKey) {
    return props.permissions
        .filter((permission) => String(permission.name || '').startsWith(`${moduleKey}.`))
        .map((permission) => permission.name);
}

function selectModule(target, moduleKey) {
    const values = permissionNamesForModule(moduleKey);
    if (target === 'create') {
        createForm.permissions = Array.from(new Set([...createForm.permissions, ...values]));
        return;
    }

    editForm.permissions = Array.from(new Set([...editForm.permissions, ...values]));
}

function deselectModule(target, moduleKey) {
    const values = new Set(permissionNamesForModule(moduleKey));
    if (target === 'create') {
        createForm.permissions = createForm.permissions.filter((permission) => !values.has(permission));
        return;
    }

    editForm.permissions = editForm.permissions.filter((permission) => !values.has(permission));
}

function applyPresetToCreate(presetKey) {
    const values = presetDefinitions[presetKey] || [];
    const validPermissions = new Set(props.permissions.map((permission) => permission.name));

    createForm.name = presetKey;
    createForm.permissions = values.filter((permission) => validPermissions.has(permission));
}

function createRole() {
    createForm.post(route('account.roles.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createPermissionSearch.value = '';
        },
    });
}

function loadRole(role) {
    editForm.id = role.id;
    editForm.name = role.name;
    editForm.permissions = (role.permissions || []).map((item) => item.name || item);
    editPermissionSearch.value = '';
}

function saveRole() {
    if (!editForm.id) return;

    editForm.patch(route('account.roles.update', editForm.id), {
        preserveScroll: true,
        onSuccess: resetEdit,
    });
}

function deleteRole(role) {
    if (!confirm(`Stergi rolul ${role.name}?`)) return;

    router.delete(route('account.roles.destroy', role.id), {
        preserveScroll: true,
    });
}

function resetEdit() {
    editForm.reset();
    editForm.id = null;
    editPermissionSearch.value = '';
}
</script>
