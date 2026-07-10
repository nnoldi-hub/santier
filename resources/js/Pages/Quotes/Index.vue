<template>
    <AppLayout title="Oferte / Devize">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Oferte / Devize</h2>
                <p class="text-sm text-gray-500 mt-1">{{ quotes.total }} documente in total</p>
            </div>
            <Link :href="route('quotes.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Oferta noua
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Status</label>
                    <select v-model="filterForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option value="draft">Ciorna</option>
                        <option value="sent">Trimisa</option>
                        <option value="accepted">Acceptata</option>
                        <option value="rejected">Respinsa</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                    <select v-model="filterForm.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate proiectele</option>
                        <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="quotes.data.length === 0"
            :icon="ClipboardDocumentCheckIcon"
            title="Nu exista oferte"
            description="Creeaza prima oferta pentru un proiect activ."
        >
            <Link :href="route('quotes.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza oferta
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="quote in quotes.data" :key="quote.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ quote.title }}</h3>
                        <span :class="statusClass(quote.status)" class="text-xs px-2 py-0.5 rounded-full">{{ statusLabel(quote.status) }}</span>
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ quote.project?.name || 'Fara proiect' }} · v{{ quote.version }}
                        <span v-if="quote.valid_until"> · valid pana la {{ formatDate(quote.valid_until) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-sm font-medium text-gray-700">{{ formatCurrency(quote.total_gross) }}</div>
                    <a :href="route('quotes.pdf', quote.id)" class="text-xs border border-indigo-200 text-indigo-700 rounded px-2 py-1 hover:bg-indigo-50">PDF</a>
                    <button v-if="quote.status !== 'accepted'" @click="acceptQuote(quote)" class="text-xs border border-emerald-200 text-emerald-700 rounded px-2 py-1 hover:bg-emerald-50">Accepta</button>
                    <Link :href="route('quotes.edit', quote.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(quote)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    quotes: Object,
    projects: Array,
    filters: Object,
});

const filterForm = reactive({
    status: props.filters?.status || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
});

function applyFilters() {
    router.get(route('quotes.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.status = '';
    filterForm.project_id = '';
    applyFilters();
}

function remove(quote) {
    if (confirm(`Stergi oferta "${quote.title}"?`)) {
        router.delete(route('quotes.destroy', quote.id));
    }
}

function acceptQuote(quote) {
    if (confirm(`Marchezi oferta "${quote.title}" ca acceptata?`)) {
        router.patch(route('quotes.accept', quote.id));
    }
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(value || 0);
}

function statusLabel(status) {
    return {
        draft: 'Ciorna',
        sent: 'Trimisa',
        accepted: 'Acceptata',
        rejected: 'Respinsa',
    }[status] || status;
}

function statusClass(status) {
    return {
        draft: 'bg-gray-100 text-gray-700',
        sent: 'bg-blue-100 text-blue-700',
        accepted: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    }[status] || 'bg-gray-100 text-gray-700';
}
</script>
