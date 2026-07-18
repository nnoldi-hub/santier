<template>
    <AppLayout :title="'Editeaza material: ' + material.name">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <Link :href="route('materials.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                    <h2 class="text-xl font-semibold text-gray-800">Editeaza material</h2>
                </div>
                <Link :href="route('recipes.create', { subject_type: 'material', subject_id: material.id })" class="text-xs text-orange-500 hover:underline">
                    + Reteta pentru acest material
                </Link>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cod</label>
                        <input v-model="form.code" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nume material *</label>
                        <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categorie</label>
                        <input v-model="form.category" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">UM *</label>
                        <input v-model="form.unit" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.unit" class="text-red-500 text-xs mt-1">{{ form.errors.unit }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pret unitar (RON) *</label>
                        <input v-model="form.unit_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.unit_price" class="text-red-500 text-xs mt-1">{{ form.errors.unit_price }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Stoc curent</label>
                        <input v-model="form.stock_quantity" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.stock_quantity" class="text-red-500 text-xs mt-1">{{ form.errors.stock_quantity }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prag minim stoc</label>
                        <input v-model="form.min_stock_quantity" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.min_stock_quantity" class="text-red-500 text-xs mt-1">{{ form.errors.min_stock_quantity }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Furnizor</label>
                    <input v-model="form.supplier" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" v-model="form.active" class="rounded border-gray-300 text-orange-500" />
                        Material activ
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea v-model="form.notes" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                            {{ form.processing ? 'Se salveaza...' : 'Salveaza modificarile' }}
                        </button>
                        <Link :href="route('materials.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Anuleaza
                        </Link>
                    </div>
                    <button type="button" @click="remove" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">
                        Sterge material
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    material: Object,
});

const form = useForm({
    code: props.material.code || '',
    name: props.material.name || '',
    category: props.material.category || '',
    unit: props.material.unit || 'buc',
    unit_price: props.material.unit_price || 0,
    stock_quantity: props.material.stock_quantity,
    min_stock_quantity: props.material.min_stock_quantity,
    supplier: props.material.supplier || '',
    active: !!props.material.active,
    notes: props.material.notes || '',
});

function submit() {
    form.patch(route('materials.update', props.material.id));
}

function remove() {
    if (confirm(`Stergi materialul "${props.material.name}"?`)) {
        router.delete(route('materials.destroy', props.material.id));
    }
}
</script>
