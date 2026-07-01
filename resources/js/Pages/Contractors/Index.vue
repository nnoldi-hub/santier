<template>
    <AppLayout title="Subcontractori">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Subcontractori si colaboratori</h2>
                <p class="text-sm text-gray-500 mt-1">{{ contractors.total }} inregistrari</p>
            </div>
            <Link :href="route('contractors.create')" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                + Contractor nou
            </Link>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs text-gray-600 mb-1">Cautare</label>
                    <input v-model="filterForm.q" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume, contact, email, telefon" />
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Tip</label>
                    <select v-model="filterForm.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate</option>
                        <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button @click="applyFilters" class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-800">Filtreaza</button>
                    <button @click="resetFilters" class="border border-gray-300 px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</button>
                </div>
            </div>
        </div>

        <div v-if="contractors.data.length === 0" class="bg-white rounded-xl border border-gray-200 p-16 text-center">
            <div class="text-5xl mb-4">🤝</div>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Nu exista contractori</h3>
            <p class="text-gray-400 text-sm mb-6">Adauga primul contractor pentru alocare pe etape.</p>
            <Link :href="route('contractors.create')" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 transition">
                Creeaza contractor
            </Link>
        </div>

        <div v-else class="space-y-3">
            <div v-for="contractor in contractors.data" :key="contractor.id" class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-800 text-sm">{{ contractor.name }}</h3>
                        <span :class="contractor.active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="text-xs px-2 py-0.5 rounded-full">
                            {{ contractor.active ? 'Activ' : 'Inactiv' }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500">
                        <span>{{ typeLabel(contractor.type) }}</span>
                        <span v-if="contractor.contact_name"> · {{ contractor.contact_name }}</span>
                        <span v-if="contractor.phone"> · {{ contractor.phone }}</span>
                        <span v-if="contractor.email"> · {{ contractor.email }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <Link :href="route('contractors.edit', contractor.id)" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">Editeaza</Link>
                    <button @click="remove(contractor)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    contractors: Object,
    types: Object,
    filters: Object,
});

const filterForm = reactive({
    q: props.filters?.q || '',
    type: props.filters?.type || '',
});

function applyFilters() {
    router.get(route('contractors.index'), { ...filterForm }, { preserveState: true, preserveScroll: true });
}

function resetFilters() {
    filterForm.q = '';
    filterForm.type = '';
    applyFilters();
}

function typeLabel(type) {
    return props.types?.[type] || type;
}

function remove(contractor) {
    if (confirm(`Stergi contractorul "${contractor.name}"?`)) {
        router.delete(route('contractors.destroy', contractor.id));
    }
}
</script>
