<template>
    <AppLayout title="Document resursa nou">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Inregistreaza document resursa</h2>
                    <p class="text-sm text-gray-500 mt-1">Pornim registrul de trasabilitate din comanda, livrare si responsabil.</p>
                </div>
                <Link :href="route('resource-orders.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi</Link>
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
                        <label class="block text-xs text-gray-600 mb-1">Tip resursa *</label>
                        <select v-model="form.resource_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in resourceTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.resource_type" class="text-xs text-red-600 mt-1">{{ form.errors.resource_type }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Status *</label>
                        <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                        <p v-if="form.errors.status" class="text-xs text-red-600 mt-1">{{ form.errors.status }}</p>
                    </div>

                    <div v-if="form.resource_type === 'material'">
                        <label class="block text-xs text-gray-600 mb-1">Material *</label>
                        <select v-model="form.material_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecteaza</option>
                            <option v-for="material in materials" :key="material.id" :value="material.id">{{ material.name }}</option>
                        </select>
                        <p v-if="form.errors.material_id" class="text-xs text-red-600 mt-1">{{ form.errors.material_id }}</p>
                    </div>
                    <div v-else>
                        <label class="block text-xs text-gray-600 mb-1">Utilaj *</label>
                        <select v-model="form.equipment_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecteaza</option>
                            <option v-for="item in equipment" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                        <p v-if="form.errors.equipment_id" class="text-xs text-red-600 mt-1">{{ form.errors.equipment_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Responsabil</label>
                        <select v-model="form.responsible_user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Neselectat</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Furnizor</label>
                        <input v-model="form.supplier_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Transportator</label>
                        <input v-model="form.carrier_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Utilaj / pompa</label>
                        <input v-model="form.equipment_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data livrare</label>
                        <input v-model="form.delivery_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Cantitate comandata *</label>
                        <input v-model.number="form.ordered_quantity" type="number" step="0.01" min="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.ordered_quantity" class="text-xs text-red-600 mt-1">{{ form.errors.ordered_quantity }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Unitate *</label>
                        <input v-model="form.ordered_unit" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="mc, buc, ore" />
                        <p v-if="form.errors.ordered_unit" class="text-xs text-red-600 mt-1">{{ form.errors.ordered_unit }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Pret unitar</label>
                        <input v-model.number="form.unit_price" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Note</label>
                        <textarea v-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">Documente atasate</h3>
                            <p class="text-xs text-gray-500 mt-1">Adauga avizul de livrare, documentul transportatorului, avizul pompei sau alte dovezi din santier.</p>
                        </div>
                        <button type="button" @click="addDocument" class="border border-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-50">
                            + Adauga document
                        </button>
                    </div>

                    <div v-if="form.documents.length === 0" class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500">
                        Nu ai atasat inca documente. Poti salva comanda acum sau poti adauga direct avizele/povezile necesare.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="(documentRow, index) in form.documents" :key="index" class="rounded-xl border border-gray-200 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-800">Document {{ index + 1 }}</div>
                                <button type="button" @click="removeDocument(index)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Elimina</button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Titlu *</label>
                                    <input v-model="documentRow.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                    <p v-if="form.errors[`documents.${index}.title`]" class="text-xs text-red-600 mt-1">{{ form.errors[`documents.${index}.title`] }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Tip document *</label>
                                    <select v-model="documentRow.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <option v-for="(label, key) in resourceDocumentTypes" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <p v-if="form.errors[`documents.${index}.type`]" class="text-xs text-red-600 mt-1">{{ form.errors[`documents.${index}.type`] }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Numar document</label>
                                    <input v-model="documentRow.document_number" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Fisier *</label>
                                    <input type="file" @change="onDocumentFileChange(index, $event)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" accept=".pdf,.png,.jpg,.jpeg" />
                                    <p v-if="form.errors[`documents.${index}.attachment`]" class="text-xs text-red-600 mt-1">{{ form.errors[`documents.${index}.attachment`] }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Cantitate declarata</label>
                                    <input v-model.number="documentRow.declared_quantity" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Cantitate livrata</label>
                                    <input v-model.number="documentRow.delivered_quantity" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-gray-600 mb-1">Observatii document</label>
                                    <textarea v-model="documentRow.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza registrarea' }}
                    </button>
                    <Link :href="route('resource-orders.index')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Anuleaza</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    projects: Array,
    phasesByProject: Object,
    materials: Array,
    equipment: Array,
    users: Array,
    resourceTypes: Object,
    statuses: Object,
    resourceDocumentTypes: Object,
});

const form = useForm({
    project_id: '',
    phase_id: '',
    resource_type: 'material',
    material_id: '',
    equipment_id: '',
    supplier_name: '',
    carrier_name: '',
    equipment_name: '',
    ordered_quantity: 0,
    ordered_unit: '',
    unit_price: 0,
    delivery_date: '',
    responsible_user_id: '',
    status: 'ordered',
    notes: '',
    documents: [],
});

const availablePhases = computed(() => {
    if (!form.project_id) return [];
    return props.phasesByProject?.[form.project_id] || props.phasesByProject?.[String(form.project_id)] || [];
});

watch(() => form.resource_type, (value) => {
    if (value === 'material') {
        form.equipment_id = '';
    } else {
        form.material_id = '';
    }
});

function addDocument() {
    form.documents.push({
        title: '',
        type: 'delivery_note',
        document_number: '',
        declared_quantity: form.ordered_quantity || 0,
        delivered_quantity: form.ordered_quantity || 0,
        notes: '',
        attachment: null,
    });
}

function removeDocument(index) {
    form.documents.splice(index, 1);
}

function onDocumentFileChange(index, event) {
    const [file] = event.target.files || [];

    if (!form.documents[index]) {
        return;
    }

    form.documents[index].attachment = file || null;
}

function submit() {
    form.post(route('resource-orders.store'), { forceFormData: true });
}
</script>