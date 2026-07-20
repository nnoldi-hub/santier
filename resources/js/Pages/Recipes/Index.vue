<template>
    <AppLayout title="Retete">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Retete</h2>
                <p class="text-sm text-gray-500 mt-1">{{ pluralize(recipes.length, 'reteta de consum', 'retete de consum') }}</p>
            </div>
            <Link :href="route('recipes.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Reteta noua
            </Link>
        </div>

        <EmptyState
            v-if="recipes.length === 0"
            :icon="BeakerIcon"
            title="Nu exista retete"
            description="Defineste consumul de materiale pentru o operatie de lucru (ex: zugravit) sau pentru un material compus (ex: beton)."
        >
            <Link :href="route('recipes.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza reteta
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="recipe in recipes" :key="recipe.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ recipe.name }}</h3>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="recipe.subject_type === 'material' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'">
                            {{ recipe.subject_label }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ recipe.subject_name }} · {{ pluralize(recipe.items_count, 'material', 'materiale') }} · unitate {{ recipe.unit }}
                        <template v-if="recipe.labor_items_count > 0"> · {{ pluralize(recipe.labor_items_count, 'manopera', 'manopera') }}</template>
                        <template v-if="recipe.equipment_items_count > 0"> · {{ pluralize(recipe.equipment_items_count, 'utilaj', 'utilaje') }}</template>
                        <template v-if="recipe.wbs_stages_count > 0"> · {{ pluralize(recipe.wbs_stages_count, 'etapa proprie', 'etape proprii') }}</template>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('recipes.edit', recipe.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(recipe)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { BeakerIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

defineProps({
    recipes: { type: Array, default: () => [] },
});

function remove(recipe) {
    if (confirm(`Stergi reteta "${recipe.name}"?`)) {
        router.delete(route('recipes.destroy', recipe.id));
    }
}
</script>
