<template>
    <AppLayout title="Furnizori materiale">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Furnizori materiale</h2>
                <p class="text-sm text-gray-500 mt-1">{{ pluralize(suppliers.total, 'furnizor in catalog', 'furnizori in catalog') }}</p>
            </div>
            <Link :href="route('suppliers.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Furnizor nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume, contact, telefon, email" />
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <EmptyState
            v-if="suppliers.data.length === 0"
            :icon="BuildingStorefrontIcon"
            title="Nu exista furnizori"
            description="Adauga primii furnizori de materiale in catalog."
        >
            <Link :href="route('suppliers.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza furnizor
            </Link>
        </EmptyState>

        <div v-else class="space-y-3">
            <div v-for="supplier in suppliers.data" :key="supplier.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ supplier.name }}</h3>
                        <span :class="supplier.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="text-xs px-2 py-0.5 rounded-full">
                            {{ supplier.active ? 'Activ' : 'Inactiv' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <span v-if="supplier.contact_name">{{ supplier.contact_name }}</span>
                        <span v-if="supplier.phone"> · {{ supplier.phone }}</span>
                        <span v-if="supplier.email"> · {{ supplier.email }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('suppliers.edit', supplier.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(supplier)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
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
import { BuildingStorefrontIcon } from '@heroicons/vue/24/outline';
import { pluralize } from '@/utils/pluralize';

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
});

function applyFilters() {
    router.get(route('suppliers.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    applyFilters();
}

function remove(supplier) {
    if (confirm(`Stergi furnizorul "${supplier.name}"?`)) {
        router.delete(route('suppliers.destroy', supplier.id));
    }
}
</script>
