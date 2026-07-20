<template>
    <AppLayout title="Editeaza utilaj">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Editeaza utilaj</h2>
                    <p class="text-sm text-gray-500 mt-1">Actualizeaza datele utilajului.</p>
                </div>
                <Link :href="route('equipment.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi</Link>
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Nume utilaj *</label>
                        <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tip *</label>
                        <select v-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Disponibilitate *</label>
                        <select v-model="form.availability_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in availabilityStatuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Furnizor (catalog)</label>
                        <select v-model="form.supplier_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="prefillSupplier">
                            <option value="">— Fara furnizor din catalog —</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Furnizor (nume afisat)</label>
                        <input v-model="form.supplier_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Cost/ora (RON) *</label>
                        <input v-model.number="form.cost_per_hour" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Note</label>
                        <textarea v-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="form.active" class="rounded border-gray-300 text-orange-500" />
                    Utilaj activ
                </label>

                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza modificari' }}
                    </button>
                    <Link :href="route('equipment.index')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Anuleaza</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    equipment: Object,
    types: Object,
    availabilityStatuses: Object,
    suppliers: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.equipment.name,
    type: props.equipment.type,
    supplier_id: props.equipment.supplier_id || '',
    supplier_name: props.equipment.supplier_name || '',
    cost_per_hour: Number(props.equipment.cost_per_hour || 0),
    availability_status: props.equipment.availability_status,
    notes: props.equipment.notes || '',
    active: !!props.equipment.active,
});

function prefillSupplier() {
    const supplier = props.suppliers.find((item) => item.id === Number(form.supplier_id));
    form.supplier_name = supplier?.name ?? '';
}

function submit() {
    form.put(route('equipment.update', props.equipment.id));
}
</script>
