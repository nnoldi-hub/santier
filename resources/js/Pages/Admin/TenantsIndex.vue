<template>
    <AppLayout title="Firme & Abonamente">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-3xl border border-sky-200 bg-gradient-to-r from-sky-50 via-white to-orange-50 p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                            <span>🏢</span>
                            Backoffice platforma
                        </div>
                        <h2 class="mt-3 text-3xl font-black text-slate-900">Firme & Abonamente</h2>
                        <p class="mt-2 max-w-3xl text-sm text-slate-600">
                            Panou global pentru firmele din platforma: plan activ, status comercial, utilizatori si semnale de crestere.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('admin.index')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Inapoi la Administrare
                        </Link>
                        <Link :href="route('pilot-invites.index')" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">
                            Vezi pipeline firme pilot
                        </Link>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" v-for="card in metricCards" :key="card.key">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ card.value }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ card.note }}</div>
                </article>
            </div>

            <section class="grid grid-cols-1 xl:grid-cols-[1.2fr_0.8fr] gap-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Filtre</div>
                            <h3 class="mt-1 text-lg font-bold text-slate-900">Cauta firma si filtreaza dupa plan sau status</h3>
                        </div>
                        <button type="button" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50" @click="resetFilters">
                            Reseteaza
                        </button>
                    </div>

                    <form class="mt-5 grid grid-cols-1 md:grid-cols-4 gap-3" @submit.prevent="applyFilters">
                        <input v-model="filterForm.search" type="text" class="rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Cauta dupa nume sau slug" />
                        <select v-model="filterForm.plan" class="rounded-xl border-slate-300 px-3 py-2 text-sm">
                            <option value="">Toate planurile</option>
                            <option v-for="option in planOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <select v-model="filterForm.status" class="rounded-xl border-slate-300 px-3 py-2 text-sm">
                            <option value="">Toate statusurile</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <button type="submit" class="rounded-xl bg-[var(--brand-blue)] px-4 py-2 text-sm font-semibold text-white hover:brightness-95 transition">
                            Aplica filtre
                        </button>
                    </form>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Pipeline comercial</div>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Lead -> demo -> trial -> paid</h3>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs text-slate-500">Firme pilot</div>
                            <div class="mt-1 text-2xl font-black text-slate-900">{{ pipeline.pilot_invites_total }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs text-slate-500">Demo programat</div>
                            <div class="mt-1 text-2xl font-black text-slate-900">{{ pipeline.demo_scheduled }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs text-slate-500">Trial pornit</div>
                            <div class="mt-1 text-2xl font-black text-slate-900">{{ pipeline.trial_started }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs text-slate-500">Castigate</div>
                            <div class="mt-1 text-2xl font-black text-slate-900">{{ pipeline.closed_won }}</div>
                        </div>
                    </div>
                    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <div class="text-xs text-emerald-700">Conversie pilot -> paid</div>
                        <div class="mt-1 text-2xl font-black text-emerald-900">{{ pipeline.pilot_to_paid_rate }}%</div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Firme active in platforma</h3>
                        <p class="text-sm text-slate-500">Baza pentru controlul abonamentelor si dezvoltarea dashboard-ului comercial.</p>
                    </div>
                    <div class="text-sm text-slate-500">{{ tenants.total }} firme</div>
                </div>

                <div v-if="tenants.data.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    Nu exista firme care sa corespunda filtrelor actuale.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                            <tr>
                                <th class="px-5 py-3 text-left">Firma</th>
                                <th class="px-5 py-3 text-left">Plan</th>
                                <th class="px-5 py-3 text-left">Status platforma</th>
                                <th class="px-5 py-3 text-left">Status comercial</th>
                                <th class="px-5 py-3 text-left">Utilizatori</th>
                                <th class="px-5 py-3 text-left">Trial end</th>
                                <th class="px-5 py-3 text-left">MRR estimat</th>
                                <th class="px-5 py-3 text-left">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-slate-50/80">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ tenant.name }}</div>
                                    <div class="text-xs text-slate-500">{{ tenant.slug }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <select
                                        v-if="isEditing(tenant.id)"
                                        v-model="editForm.billing_plan"
                                        class="rounded-lg border-slate-300 px-2 py-1 text-xs"
                                    >
                                        <option v-for="option in planOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                    </select>
                                    <span v-else class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold" :class="planTone(tenant.billing_plan)">
                                        {{ tenant.billing_plan_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <select
                                        v-if="isEditing(tenant.id)"
                                        v-model="editForm.status"
                                        class="rounded-lg border-slate-300 px-2 py-1 text-xs"
                                    >
                                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                    </select>
                                    <span v-else class="inline-flex rounded-full px-2 py-1 text-[11px] font-semibold" :class="tenant.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                        {{ tenant.status === 'active' ? 'Activa' : 'Suspendata' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-700">{{ tenant.commercial_status }}</td>
                                <td class="px-5 py-4 text-slate-700">{{ tenant.active_memberships_count }} / {{ tenant.total_memberships_count }}</td>
                                <td class="px-5 py-4 text-slate-700">
                                    <input
                                        v-if="isEditing(tenant.id)"
                                        v-model="editForm.billing_trial_ends_at"
                                        type="date"
                                        class="rounded-lg border-slate-300 px-2 py-1 text-xs"
                                    />
                                    <span v-else>{{ formatDate(tenant.trial_ends_at) }}</span>
                                    <p v-if="isEditing(tenant.id) && (localValidationMessage || editForm.errors.billing_trial_ends_at)" class="mt-1 text-[11px] font-medium text-rose-600">
                                        {{ localValidationMessage || editForm.errors.billing_trial_ends_at }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 font-semibold text-slate-900">{{ formatMoney(tenant.estimated_mrr) }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span v-if="isEditing(tenant.id) && editHasChanges" class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-amber-700">
                                            Modificari nesalvate
                                        </span>
                                        <button
                                            v-if="!isEditing(tenant.id)"
                                            type="button"
                                            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            @click="startEdit(tenant)"
                                        >
                                            Editeaza
                                        </button>
                                        <template v-else>
                                            <button
                                                type="button"
                                                class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800 disabled:opacity-60"
                                                :disabled="editForm.processing || !editHasChanges || Boolean(localValidationMessage)"
                                                @click="saveEdit(tenant.id)"
                                            >
                                                Salveaza
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                @click="cancelEdit"
                                            >
                                                Renunta
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="tenants.last_page > 1" class="flex items-center justify-between gap-3 border-t border-slate-200 px-5 py-4">
                    <div class="text-sm text-slate-500">Pagina {{ tenants.current_page }} din {{ tenants.last_page }}</div>
                    <div class="flex gap-2">
                        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 disabled:opacity-50" :disabled="!tenants.prev_page_url" @click="goToPage(tenants.prev_page_url)">
                            Anterior
                        </button>
                        <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 disabled:opacity-50" :disabled="!tenants.next_page_url" @click="goToPage(tenants.next_page_url)">
                            Urmator
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tenants: { type: Object, required: true },
    metrics: { type: Object, required: true },
    pipeline: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    planOptions: { type: Array, default: () => [] },
    statusOptions: { type: Array, default: () => [] },
});

const filterForm = reactive({
    search: props.filters.search || '',
    plan: props.filters.plan || '',
    status: props.filters.status || '',
});

const editingTenantId = ref(null);
const originalEditState = ref({
    billing_plan: '',
    status: '',
    billing_trial_ends_at: '',
});

const editForm = useForm({
    billing_plan: '',
    status: 'active',
    billing_trial_ends_at: '',
});

const metricCards = computed(() => [
    { key: 'tenants_total', label: 'Firme totale', value: props.metrics.tenants_total || 0, note: 'Toate firmele create in platforma' },
    { key: 'tenants_paid', label: 'Firme platitoare', value: props.metrics.tenants_paid || 0, note: 'Starter, Pro sau Enterprise' },
    { key: 'tenants_trial', label: 'Firme in trial', value: props.metrics.tenants_trial || 0, note: 'Trial activ neconvertit' },
    { key: 'monthly_mrr_estimate', label: 'MRR estimat', value: formatMoney(props.metrics.monthly_mrr_estimate || 0), note: 'Estimare bazata pe planurile active' },
]);

const editHasChanges = computed(() => {
    return editForm.billing_plan !== originalEditState.value.billing_plan
        || editForm.status !== originalEditState.value.status
        || (editForm.billing_trial_ends_at || '') !== originalEditState.value.billing_trial_ends_at;
});

const requiresTrialDate = computed(() => {
    if (editForm.status !== 'active') {
        return false;
    }

    return !['starter', 'pro', 'enterprise'].includes(editForm.billing_plan);
});

const localValidationMessage = computed(() => {
    if (requiresTrialDate.value && !editForm.billing_trial_ends_at) {
        return 'Pentru planuri neplatite active, data de final trial este obligatorie.';
    }

    return '';
});

function applyFilters() {
    router.get(route('admin.tenants.index'), {
        search: filterForm.search,
        plan: filterForm.plan,
        status: filterForm.status,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

function resetFilters() {
    filterForm.search = '';
    filterForm.plan = '';
    filterForm.status = '';
    applyFilters();
}

function goToPage(url) {
    if (!url) {
        return;
    }

    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
}

function isEditing(tenantId) {
    return editingTenantId.value === tenantId;
}

function startEdit(tenant) {
    editingTenantId.value = tenant.id;
    editForm.billing_plan = tenant.billing_plan || 'free';
    editForm.status = tenant.status || 'active';
    editForm.billing_trial_ends_at = tenant.trial_ends_at || '';
    originalEditState.value = {
        billing_plan: editForm.billing_plan,
        status: editForm.status,
        billing_trial_ends_at: editForm.billing_trial_ends_at,
    };
    editForm.clearErrors();
}

function cancelEdit() {
    editingTenantId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveEdit(tenantId) {
    if (localValidationMessage.value) {
        editForm.setError('billing_trial_ends_at', localValidationMessage.value);

        return;
    }

    editForm.patch(route('admin.tenants.commercial.update', tenantId), {
        preserveScroll: true,
        onSuccess: () => {
            cancelEdit();
        },
    });
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('ro-RO');
}

function formatMoney(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function planTone(plan) {
    if (plan === 'enterprise') {
        return 'bg-slate-900 text-white';
    }

    if (plan === 'pro') {
        return 'bg-orange-100 text-orange-700';
    }

    if (plan === 'starter') {
        return 'bg-emerald-100 text-emerald-700';
    }

    return 'bg-slate-100 text-slate-600';
}
</script>