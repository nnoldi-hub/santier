<template>
    <AppLayout title="Audit IAM">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Filtre audit</h2>
                <p class="text-sm text-gray-500 mt-1">Cauta rapid actiuni IAM dupa actor, actiune, resursa si perioada.</p>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="preset in quickActionPresets"
                        :key="preset.key"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs font-medium"
                        :class="isPresetActive(preset) ? 'border-sky-300 bg-sky-50 text-sky-700' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50'"
                        @click="applyPreset(preset)"
                    >
                        {{ preset.label }}
                    </button>
                </div>

                <form class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3" @submit.prevent="applyFilters">
                    <input v-model="form.actor" type="text" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Actor (nume/email)" />

                    <select v-model="form.action" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate actiunile</option>
                        <option v-for="action in actionOptions" :key="action" :value="action">{{ action }}</option>
                    </select>

                    <select v-model="form.resource_type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate resursele</option>
                        <option v-for="resourceType in resourceTypeOptions" :key="resourceType" :value="resourceType">{{ resourceType }}</option>
                    </select>

                    <input v-model="form.from" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <input v-model="form.to" type="date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" />

                    <select v-if="isSuperadmin" v-model="form.tenant_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toti tenantii</option>
                        <option v-for="tenant in tenantOptions" :key="tenant.id" :value="String(tenant.id)">{{ tenant.name }}</option>
                    </select>

                    <div class="xl:col-span-6 flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm font-medium hover:bg-slate-800">Aplica filtre</button>
                        <button type="button" class="rounded-lg border border-sky-300 bg-sky-50 px-4 py-2 text-sm text-sky-700 hover:bg-sky-100" @click="exportCsv">Export CSV</button>
                        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50" @click="resetFilters">Reseteaza</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Evenimente IAM</h3>
                    <span class="text-xs text-gray-500">{{ logs.total }} inregistrari</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-600 uppercase tracking-wide">
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">Actor</th>
                                <th class="px-4 py-3">Actiune</th>
                                <th class="px-4 py-3">Resursa</th>
                                <th class="px-4 py-3">Detalii</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id" class="border-t border-gray-100 align-top">
                                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ log.actor?.name || 'Sistem' }}</div>
                                    <div class="text-xs text-gray-500">{{ log.actor?.email || '-' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ log.action }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    <div>{{ log.resource_type || '-' }}</div>
                                    <div class="text-gray-500">ID: {{ log.resource_id || '-' }}</div>
                                    <div v-if="isSuperadmin" class="text-gray-500">Tenant: {{ log.tenant_id || '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    <div class="space-y-1">
                                        <div class="text-gray-500">IP: {{ log.ip_address || '-' }}</div>
                                        <div v-for="item in metadataPreview(log.metadata)" :key="`${log.id}-${item.key}`">
                                            <span class="font-medium">{{ item.key }}:</span> {{ item.value }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Nu exista inregistrari pentru filtrele selectate.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-200 flex items-center justify-between text-sm">
                    <div class="text-gray-500">
                        Pagina {{ logs.current_page }} din {{ logs.last_page }}
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-50"
                            :disabled="!logs.prev_page_url"
                            @click="goTo(logs.prev_page_url)"
                        >
                            Inapoi
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-50"
                            :disabled="!logs.next_page_url"
                            @click="goTo(logs.next_page_url)"
                        >
                            Inainte
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    logs: { type: Object, required: true },
    filters: { type: Object, required: true },
    actionOptions: { type: Array, default: () => [] },
    resourceTypeOptions: { type: Array, default: () => [] },
    tenantOptions: { type: Array, default: () => [] },
    isSuperadmin: { type: Boolean, default: false },
});

const form = useForm({
    action: props.filters.action || '',
    actor: props.filters.actor || '',
    resource_type: props.filters.resource_type || '',
    tenant_id: props.filters.tenant_id ? String(props.filters.tenant_id) : '',
    from: props.filters.from || '',
    to: props.filters.to || '',
});

const quickActionPresets = [
    { key: 'all', label: 'Toate', action: '', resource_type: '' },
    { key: 'resource-order', label: 'Resurse (resource_order.*)', action: 'resource_order.', resource_type: 'resource_order' },
    { key: 'iam', label: 'IAM (iam.*)', action: 'iam.', resource_type: '' },
];

function applyFilters() {
    form.get(route('account.audit.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function exportCsv() {
    const params = {
        action: form.action || undefined,
        actor: form.actor || undefined,
        resource_type: form.resource_type || undefined,
        tenant_id: form.tenant_id || undefined,
        from: form.from || undefined,
        to: form.to || undefined,
    };

    window.location.href = route('account.audit.export', params);
}

function resetFilters() {
    form.action = '';
    form.actor = '';
    form.resource_type = '';
    form.tenant_id = '';
    form.from = '';
    form.to = '';

    applyFilters();
}

function applyPreset(preset) {
    form.action = preset.action;
    form.resource_type = preset.resource_type;
    applyFilters();
}

function isPresetActive(preset) {
    return (form.action || '') === (preset.action || '')
        && (form.resource_type || '') === (preset.resource_type || '');
}

function goTo(url) {
    if (!url) return;

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
}

function formatDate(value) {
    if (!value) return '-';

    const parsed = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return parsed.toLocaleString('ro-RO');
}

function metadataPreview(metadata) {
    if (!metadata || typeof metadata !== 'object') {
        return [];
    }

    return Object.entries(metadata)
        .slice(0, 4)
        .map(([key, value]) => ({
            key,
            value: typeof value === 'string' ? value : JSON.stringify(value),
        }));
}
</script>
