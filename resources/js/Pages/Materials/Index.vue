<template>
    <AppLayout title="Materiale">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Materiale</h2>
                <p class="text-sm text-gray-500 mt-1">{{ pluralize(materials.total, 'material in catalog', 'materiale in catalog') }}</p>
            </div>
            <Link :href="route('materials.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Material nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume, cod, furnizor" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Categorie</label>
                    <select v-model="filterForm.category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="materials.data.length === 0"
            :icon="CubeIcon"
            title="Nu exista materiale"
            description="Adauga primele materiale in catalog."
        >
            <Link :href="route('materials.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza material
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="material in materials.data" :key="material.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ material.name }}</h3>
                        <span :class="material.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="text-xs px-2 py-0.5 rounded-full">
                            {{ material.active ? 'Activ' : 'Inactiv' }}
                        </span>
                        <span v-if="isLowStock(material)" class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                            Stoc scazut
                        </span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <span v-if="material.code">Cod: {{ material.code }} · </span>
                        <span>{{ material.category || 'Fara categorie' }}</span>
                        <span> · {{ material.unit }}</span>
                        <span v-if="material.supplier"> · {{ material.supplier }}</span>
                        <span v-if="material.stock_quantity !== null && material.stock_quantity !== undefined"> · stoc {{ Number(material.stock_quantity).toFixed(2) }} / min {{ Number(material.min_stock_quantity || 0).toFixed(2) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="text-sm font-medium text-gray-700">{{ formatCurrency(material.unit_price) }}</div>
                    <Link :href="route('materials.edit', material.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(material)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
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
import { CubeIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

const props = defineProps({
    materials: Object,
    categories: Array,
    filters: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    category: props.filters?.category || '',
});

function applyFilters() {
    router.get(route('materials.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.category = '';
    applyFilters();
}

function remove(material) {
    if (confirm(`Stergi materialul "${material.name}"?`)) {
        router.delete(route('materials.destroy', material.id));
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(value);
}

function isLowStock(material) {
    if (material.stock_quantity === null || material.stock_quantity === undefined) return false;
    if (material.min_stock_quantity === null || material.min_stock_quantity === undefined) return false;

    return Number(material.stock_quantity) <= Number(material.min_stock_quantity);
}
</script>
