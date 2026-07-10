<template>
    <AppLayout title="Proiecte">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Proiecte</h2>
                <p class="text-sm text-gray-500 mt-1">{{ projects.total }} proiecte în total</p>
            </div>
            <Link :href="route('projects.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Proiect nou
            </Link>
        </div>

        <EmptyState
            v-if="projects.data.length === 0"
            :icon="BuildingOffice2Icon"
            title="Niciun proiect"
            description="Creeaza primul proiect pentru a incepe."
        >
            <Link :href="route('projects.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza proiect
            </Link>
        </EmptyState>

        <!-- Projects grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <Link
                v-for="project in projects.data"
                :key="project.id"
                :href="route('projects.show', project.id)"
                class="bg-white rounded-xl border border-gray-200 p-5 hover:border-orange-300 hover:shadow-md transition-all"
            >
                <div class="flex items-start justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 text-sm leading-tight">{{ project.name }}</h3>
                    <StatusBadge :status="project.status" />
                </div>
                <p v-if="project.client" class="text-xs text-gray-400 mb-3">
                    👥 {{ project.client.name }}
                </p>
                <p v-if="project.address" class="text-xs text-gray-400 mb-3 truncate">
                    📍 {{ project.address }}
                </p>
                <div class="flex items-center justify-between text-xs text-gray-400 pt-3 border-t border-gray-100">
                    <span v-if="project.start_date">📅 {{ formatDate(project.start_date) }}</span>
                    <span v-if="project.total_budget" class="font-medium text-gray-600">
                        {{ formatCurrency(project.total_budget) }}
                    </span>
                </div>
            </Link>
        </div>

        <!-- Flash message -->
        <div v-if="$page.props.flash?.success" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg text-sm">
            {{ $page.props.flash.success }}
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline';

defineProps({
    projects: Object,
});

const statusLabels = {
    draft: 'Ciorna', active: 'Activ', paused: 'Pauza', completed: 'Finalizat', cancelled: 'Anulat',
};

function formatDate(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(value);
}
</script>
