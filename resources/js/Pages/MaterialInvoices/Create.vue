<template>
    <AppLayout title="Factura materiale noua">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Adauga factura materiale</h2>
                    <p class="text-sm text-gray-500 mt-1">Urmareste costurile cu materialele pe proiect si etapa.</p>
                </div>
                <Link :href="route('material-invoices.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi</Link>
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Proiect *</label>
                        <select v-model="form.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecteaza</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                        </select>
                        <p v-if="form.errors.project_id" class="text-xs text-red-600 mt-1">{{ form.errors.project_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Etapa</label>
                        <select v-model="form.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Fara etapa</option>
                            <option v-for="phase in availablePhases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                        </select>
                        <p v-if="form.errors.phase_id" class="text-xs text-red-600 mt-1">{{ form.errors.phase_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Material</label>
                        <select v-model="form.material_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Neselectat</option>
                            <option v-for="material in materials" :key="material.id" :value="material.id">{{ material.name }}</option>
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
                        <label class="block text-xs text-gray-600 mb-1">Numar factura</label>
                        <input v-model="form.invoice_no" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Status plata *</label>
                        <select v-model="form.payment_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in paymentStatuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data emitere *</label>
                        <input v-model="form.issue_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data scadenta</label>
                        <input v-model="form.due_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Suma neta *</label>
                        <input v-model.number="form.amount_net" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">TVA *</label>
                        <input v-model.number="form.amount_vat" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Total *</label>
                        <input v-model.number="form.amount_total" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Note</label>
                        <textarea v-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza factura' }}
                    </button>
                    <Link :href="route('material-invoices.index')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Anuleaza</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    projects: Array,
    selectedProjectId: Number,
    phasesByProject: Object,
    materials: Array,
    paymentStatuses: Object,
    suppliers: { type: Array, default: () => [] },
});

const form = useForm({
    project_id: props.selectedProjectId || '',
    phase_id: '',
    material_id: '',
    supplier_id: '',
    supplier_name: '',
    invoice_no: '',
    issue_date: '',
    due_date: '',
    amount_net: 0,
    amount_vat: 0,
    amount_total: 0,
    payment_status: 'unpaid',
    notes: '',
});

const availablePhases = computed(() => {
    if (!form.project_id) return [];
    return props.phasesByProject?.[form.project_id] || props.phasesByProject?.[String(form.project_id)] || [];
});

function prefillSupplier() {
    const supplier = props.suppliers.find((item) => item.id === Number(form.supplier_id));
    form.supplier_name = supplier?.name ?? '';
}

function submit() {
    form.post(route('material-invoices.store'));
}
</script>
