<template>
    <AppLayout title="Cost tracking">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Cost tracking</h2>
                <p class="text-sm text-gray-500 mt-1">Comparativ buget, oferte si abatere pentru fiecare proiect.</p>
            </div>
            <Link :href="route('exports.index')" class="text-sm text-gray-500 hover:text-gray-700">Vezi exporturi</Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Proiecte</div>
                <div class="text-xl font-semibold text-gray-800">{{ summary.projects_count }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Buget total</div>
                <div class="text-xl font-semibold text-gray-800">{{ formatCurrency(summary.budget_total) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Total oferte</div>
                <div class="text-xl font-semibold text-gray-800">{{ formatCurrency(summary.quotes_total) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Peste buget</div>
                <div class="text-xl font-semibold text-red-600">{{ summary.over_budget_count }}</div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h3 class="font-semibold text-sm text-gray-800">Top proiecte dupa abatere</h3>
                <span class="text-xs text-gray-500">Date preluate din dataset-ul de costuri</span>
            </div>
            <div v-if="projects.length === 0" class="text-sm text-gray-400">Nu exista proiecte pentru tracking.</div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-3">Proiect</th>
                            <th class="py-2 pr-3">Buget</th>
                            <th class="py-2 pr-3">Oferte</th>
                            <th class="py-2 pr-3">Acceptat</th>
                            <th class="py-2 pr-3">Abatere</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in sortedProjects" :key="String(row.project_id)" class="border-b last:border-0">
                            <td class="py-2 pr-3">
                                <div class="font-medium text-gray-800">{{ row.project_name }}</div>
                                <div class="text-xs text-gray-500">{{ row.quotes_count }} oferte</div>
                            </td>
                            <td class="py-2 pr-3">{{ formatCurrency(row.budget) }}</td>
                            <td class="py-2 pr-3">{{ formatCurrency(row.total_gross) }}</td>
                            <td class="py-2 pr-3">{{ formatCurrency(row.accepted_total_gross) }}</td>
                            <td class="py-2 pr-3 font-medium" :class="diffClass(row.diff_vs_budget)">{{ formatDiff(row.diff_vs_budget) }}</td>
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

const props = defineProps({
    projects: Array,
    filters: Object,
    summary: Object,
});

const sortedProjects = computed(() => {
    return [...props.projects].sort((left, right) => Math.abs(Number(right.diff_vs_budget || 0)) - Math.abs(Number(left.diff_vs_budget || 0)));
});

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(value || 0);
}

function formatDiff(value) {
    if (value === null || value === undefined || value === '') return '-';
    return formatCurrency(value);
}

function diffClass(value) {
    if (value === null || value === undefined) return 'text-gray-500';
    return Number(value) > 0 ? 'text-red-600' : 'text-green-600';
}
</script>
