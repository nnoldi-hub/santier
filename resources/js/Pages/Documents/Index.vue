<template>
    <AppLayout title="Documente">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Documente financiare</h2>
                <p class="text-sm text-gray-500 mt-1">{{ documents.total }} documente inregistrate</p>
            </div>
            <Link :href="route('documents.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Document nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-8 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Titlu, note" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Tip</label>
                    <select v-model="filterForm.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Plata</label>
                    <select v-model="filterForm.payment_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in paymentStatuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Etapa</label>
                    <select v-model="filterForm.stage_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="stage in stages" :key="stage.id" :value="String(stage.id)">{{ stage.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Contractor</label>
                    <select v-model="filterForm.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toti</option>
                        <option v-for="contractor in contractors" :key="contractor.id" :value="String(contractor.id)">{{ contractor.name }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <h3 class="font-semibold text-sm text-gray-800 mb-3">Sumar costuri pe etapa</h3>
            <div v-if="summaryByStage.length === 0" class="text-sm text-gray-400">Nu exista date pentru sumar.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-3">Etapa</th>
                            <th class="py-2 pr-3">Nr documente</th>
                            <th class="py-2 pr-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in summaryByStage" :key="String(row.stage_id ?? 'none') + row.stage_name" class="border-b last:border-0">
                            <td class="py-2 pr-3">{{ row.stage_name }}</td>
                            <td class="py-2 pr-3">{{ row.documents_count }}</td>
                            <td class="py-2 pr-3 font-medium">{{ formatCurrency(row.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm text-gray-800">Prioritate acum</h3>
                <span class="text-xs text-gray-500">Actiuni recomandate pe baza datelor curente</span>
            </div>
            <div v-if="attentionItems.length === 0" class="text-sm text-gray-500">
                Nu exista alerte active. Situatia financiara este stabila pe filtrele selectate.
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div
                    v-for="item in attentionItems"
                    :key="item.key"
                    class="rounded-xl border p-3"
                    :class="attentionToneClass(item.tone)"
                >
                    <div class="text-xs uppercase tracking-wider opacity-80 mb-1">Atentie</div>
                    <div class="font-semibold text-sm mb-1">{{ item.title }}</div>
                    <div class="text-lg font-semibold">{{ item.value }}</div>
                    <div class="text-xs mt-1 opacity-90">{{ item.helper }}</div>
                </div>
            </div>
        </div>

        <div v-if="financialInsights.overdue_invoices_count > 0" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            ⚠️ Ai {{ financialInsights.overdue_invoices_count }} facturi restante (emise de peste 30 zile si neplatite).
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Documente platite</div>
                <div class="text-xl font-semibold text-gray-800">{{ financialInsights.paid_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Plata partiala</div>
                <div class="text-xl font-semibold text-gray-800">{{ financialInsights.partial_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Neplatite</div>
                <div class="text-xl font-semibold text-gray-800">{{ financialInsights.unpaid_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4 md:col-span-2">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Expunere financiara (unpaid + partial)</div>
                <div class="text-xl font-semibold text-gray-800">{{ formatCurrency(financialInsights.total_unpaid_amount) }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <h3 class="font-semibold text-sm text-gray-800 mb-3">Totaluri pe contractor</h3>
            <div v-if="totalsByContractor.length === 0" class="text-sm text-gray-400">Nu exista date pentru contractori.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-3">Contractor</th>
                            <th class="py-2 pr-3">Nr documente</th>
                            <th class="py-2 pr-3">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in totalsByContractor" :key="String(row.contractor_id ?? 'none') + row.contractor_name" class="border-b last:border-0">
                            <td class="py-2 pr-3">{{ row.contractor_name }}</td>
                            <td class="py-2 pr-3">{{ row.documents_count }}</td>
                            <td class="py-2 pr-3 font-medium">{{ formatCurrency(row.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <EmptyState
            v-if="documents.data.length === 0"
            :icon="DocumentTextIcon"
            title="Nu exista documente"
            description="Adauga primul document financiar pentru proiecte."
        >
            <Link :href="route('documents.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza document
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="document in documents.data" :key="document.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ document.title }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ typeLabel(document.type) }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(document.payment_status)">{{ paymentStatusLabel(document.payment_status) }}</span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <span>{{ document.project?.name || 'Fara proiect' }}</span>
                        <span v-if="document.stage"> · {{ document.stage.name }}</span>
                        <span v-if="document.contractor"> · {{ document.contractor.name }}</span>
                        <span> · {{ document.issued_at }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-sm font-medium text-gray-700">{{ formatCurrency(document.amount) }}</div>
                    <a v-if="document.file_path" :href="route('documents.download', document.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Fisier</a>
                    <a :href="route('documents.pdf', document.id)" class="text-xs border border-orange-200 rounded px-2 py-1 text-orange-700 hover:bg-orange-50">PDF</a>
                    <Link :href="route('documents.edit', document.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(document)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    documents: Object,
    filters: Object,
    projects: Array,
    stages: Array,
    contractors: Array,
    types: Object,
    paymentStatuses: Object,
    summaryByStage: Array,
    financialInsights: Object,
    totalsByContractor: Array,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    type: props.filters?.type || '',
    payment_status: props.filters?.payment_status || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
    stage_id: props.filters?.stage_id ? String(props.filters.stage_id) : '',
    contractor_id: props.filters?.contractor_id ? String(props.filters.contractor_id) : '',
});

const priorityContractor = computed(() => {
    if (!Array.isArray(props.totalsByContractor) || props.totalsByContractor.length === 0) {
        return null;
    }

    return props.totalsByContractor[0];
});

const highestStageExposure = computed(() => {
    if (!Array.isArray(props.summaryByStage) || props.summaryByStage.length === 0) {
        return null;
    }

    return props.summaryByStage.reduce((currentMax, row) => {
        const currentValue = Number(currentMax?.total_amount || 0);
        const rowValue = Number(row?.total_amount || 0);

        return rowValue > currentValue ? row : currentMax;
    }, props.summaryByStage[0]);
});

const attentionItems = computed(() => {
    const items = [];

    if ((props.financialInsights?.overdue_invoices_count || 0) > 0) {
        items.push({
            key: 'overdue',
            tone: 'critical',
            title: 'Facturi restante peste 30 zile',
            value: props.financialInsights.overdue_invoices_count,
            helper: 'Prioritizeaza incasarile pentru a reduce blocajele de cash-flow.',
        });
    }

    if ((props.financialInsights?.unpaid_count || 0) > 0) {
        items.push({
            key: 'unpaid',
            tone: 'warning',
            title: 'Documente complet neplatite',
            value: props.financialInsights.unpaid_count,
            helper: 'Verifica termenele de plata si escaladeaza cazurile critice.',
        });
    }

    if (priorityContractor.value && Number(priorityContractor.value.total_amount || 0) > 0) {
        items.push({
            key: 'contractor',
            tone: 'info',
            title: `Expunere maxima contractor: ${priorityContractor.value.contractor_name}`,
            value: formatCurrency(priorityContractor.value.total_amount),
            helper: 'Revizuieste situatia de plata pentru acest contractor.',
        });
    }

    if (highestStageExposure.value && Number(highestStageExposure.value.total_amount || 0) > 0) {
        items.push({
            key: 'stage',
            tone: 'info',
            title: `Etapa cu expunere maxima: ${highestStageExposure.value.stage_name}`,
            value: formatCurrency(highestStageExposure.value.total_amount),
            helper: 'Analizeaza documentele din aceasta etapa inainte de noi comenzi.',
        });
    }

    return items.slice(0, 3);
});

function attentionToneClass(tone) {
    if (tone === 'critical') return 'border-red-300 bg-red-50 text-red-700';
    if (tone === 'warning') return 'border-amber-300 bg-amber-50 text-amber-700';

    return 'border-sky-300 bg-sky-50 text-sky-700';
}

function applyFilters() {
    router.get(route('documents.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.type = '';
    filterForm.payment_status = '';
    filterForm.project_id = '';
    filterForm.stage_id = '';
    filterForm.contractor_id = '';
    applyFilters();
}

function remove(document) {
    if (confirm(`Stergi documentul "${document.title}"?`)) {
        router.delete(route('documents.destroy', document.id));
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(value || 0);
}

function typeLabel(type) {
    return props.types?.[type] || type;
}

function paymentStatusLabel(status) {
    return props.paymentStatuses?.[status] || status;
}

function statusClass(status) {
    if (status === 'paid') return 'bg-green-100 text-green-700';
    if (status === 'partial') return 'bg-yellow-100 text-yellow-700';
    if (status === 'cancelled') return 'bg-gray-100 text-gray-700';

    return 'bg-red-100 text-red-700';
}
</script>
