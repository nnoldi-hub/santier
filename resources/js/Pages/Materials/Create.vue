<template>
    <AppLayout title="Material nou">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('materials.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Material nou</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cod</label>
                        <input v-model="form.code" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: MAT-001" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nume material *</label>
                        <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: Glet finisaj" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categorie</label>
                        <input v-model="form.category" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: Finisaje" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">UM *</label>
                        <input v-model="form.unit" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="buc, kg, mp" />
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

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Furnizor (catalog)</label>
                        <select v-model="form.supplier_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="prefillSupplier">
                            <option value="">— Fara furnizor din catalog —</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Furnizor (nume afisat)</label>
                        <input v-model="form.supplier" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: Dedeman" />
                    </div>
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

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                        {{ form.processing ? 'Se salveaza...' : 'Creeaza material' }}
                    </button>
                    <Link :href="route('materials.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        Anuleaza
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    defaults: { type: Object, default: () => ({ stock_quantity: null, min_stock_quantity: null }) },
    suppliers: { type: Array, default: () => [] },
});

const form = useForm({
    code: '',
    name: '',
    category: '',
    unit: 'buc',
    unit_price: 0,
    stock_quantity: props.defaults?.stock_quantity,
    min_stock_quantity: props.defaults?.min_stock_quantity,
    supplier_id: '',
    supplier: '',
    active: true,
    notes: '',
});

function prefillSupplier() {
    const supplier = props.suppliers.find((item) => item.id === Number(form.supplier_id));
    form.supplier = supplier?.name ?? '';
}

function submit() {
    form.post(route('materials.store'));
}
</script>
