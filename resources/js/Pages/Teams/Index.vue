<template>
    <AppLayout title="Echipe">
        <div class="flex items-center justify-between mb-6 gap-3 flex-col md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Echipe</h2>
                <p class="text-sm text-gray-500 mt-1">{{ pluralize(teams.total, 'echipa in total', 'echipe in total') }}</p>
            </div>
            <Link :href="route('teams.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Echipa noua
            </Link>
        </div>

        <section class="mb-6 rounded-2xl border border-gray-200 bg-white p-4">
            <div class="flex items-start justify-between gap-3 flex-col md:flex-row md:items-center">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Filtre echipe</h3>
                    <p class="text-xs text-gray-500 mt-1">Cauta dupa nume, specialitate sau lider si restrange dupa status.</p>
                </div>
                <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-xs hover:bg-gray-50" @click="resetFilters">
                    Reseteaza
                </button>
            </div>

            <form class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="applyFilters">
                <input v-model="filterForm.search" type="text" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cautare rapida" />
                <input v-model="filterForm.specialty" type="text" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Specialitate" />
                <select v-model="filterForm.status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="all">Toate statusurile</option>
                    <option value="active">Doar active</option>
                    <option value="inactive">Doar inactive</option>
                </select>

                <div class="md:col-span-3 flex items-center gap-2">
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm font-medium hover:bg-slate-800">
                        Aplica filtre
                    </button>
                    <span class="text-xs text-gray-500">Rezultate curente: {{ teams.data.length }}</span>
                </div>
            </form>
        </section>

        <EmptyState
            v-if="teams.data.length === 0"
            :icon="UsersIcon"
            title="Nu exista echipe"
            description="Creeaza prima echipa pentru alocari pe etape."
        >
            <Link :href="route('teams.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza echipa
            </Link>
        </EmptyState>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <Link
                v-for="team in teams.data"
                :key="team.id"
                :href="route('teams.show', team.id)"
                class="bg-white rounded-xl border border-gray-200 p-5 hover:border-orange-300 hover:shadow-md transition-all"
            >
                <div class="flex items-start justify-between mb-3">
                    <h3 class="font-semibold text-gray-800 text-sm leading-tight">{{ team.name }}</h3>
                    <span :class="team.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="text-xs px-2 py-0.5 rounded-full">
                        {{ team.active ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>
                <p v-if="team.specialty" class="text-xs text-gray-500 mb-2">Specialitate: {{ team.specialty }}</p>
                <p class="text-xs text-gray-500">Lider: {{ team.leader?.name || 'Nesetat' }}</p>
                <div class="flex items-center justify-between text-xs text-gray-400 pt-3 border-t border-gray-100 mt-3">
                    <span>Membri: {{ team.members_count }}</span>
                    <span>Alocari: {{ team.assignments?.length || 0 }}</span>
                </div>
            </Link>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { UsersIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

const props = defineProps({
    teams: Object,
    filters: { type: Object, default: () => ({}) },
});

const filterForm = reactive({
    search: props.filters.search || '',
    specialty: props.filters.specialty || '',
    status: props.filters.status || 'all',
});

function applyFilters() {
    router.get(route('teams.index'), {
        search: filterForm.search,
        specialty: filterForm.specialty,
        status: filterForm.status,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    filterForm.search = '';
    filterForm.specialty = '';
    filterForm.status = 'all';
    applyFilters();
}
</script>
