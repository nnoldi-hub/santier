<template>
    <AppLayout title="Echipe">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Echipe</h2>
                <p class="text-sm text-gray-500 mt-1">{{ teams.total }} echipe in total</p>
            </div>
            <Link :href="route('teams.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Echipa noua
            </Link>
        </div>

        <div v-if="teams.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <div class="text-5xl mb-4">👷</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista echipe</h3>
            <p class="text-gray-400 text-sm mb-6">Creeaza prima echipa pentru alocari pe etape.</p>
            <Link :href="route('teams.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza echipa
            </Link>
        </div>

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
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    teams: Object,
});
</script>
