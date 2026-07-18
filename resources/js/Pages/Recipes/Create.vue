<template>
    <AppLayout title="Reteta noua">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('recipes.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Reteta noua</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tip subiect *</label>
                    <div class="flex gap-2">
                        <button type="button" @click="setSubjectType('task_template')" class="flex-1 border rounded-lg px-3 py-2 text-sm" :class="form.subject_type === 'task_template' ? 'border-orange-400 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600'">
                            Operatie de lucru
                        </button>
                        <button type="button" @click="setSubjectType('material')" class="flex-1 border rounded-lg px-3 py-2 text-sm" :class="form.subject_type === 'material' ? 'border-orange-400 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600'">
                            Material compus
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ form.subject_type === 'material' ? 'Material *' : 'Sablon task (operatie) *' }}
                    </label>
                    <select v-model="form.subject_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="onSubjectChange">
                        <option value="">— Selecteaza —</option>
                        <option v-for="option in subjectOptions" :key="option.id" :value="option.id">{{ option.label }}</option>
                    </select>
                    <p v-if="form.errors.subject_id" class="text-red-500 text-xs mt-1">{{ form.errors.subject_id }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nume reteta *</label>
                        <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: Zugravit lavabil" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unitate de baza *</label>
                        <input v-model="form.unit" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: mp, mc, ml, buc" />
                        <p v-if="form.errors.unit" class="text-red-500 text-xs mt-1">{{ form.errors.unit }}</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Materiale necesare (consum per 1 {{ form.unit || 'unitate' }})</label>
                        <button type="button" @click="addItem" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Material</button>
                    </div>
                    <div v-if="form.items.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                        Adauga cel putin un material.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(item, index) in form.items" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                            <select v-model="item.material_id" class="md:col-span-7 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Material —</option>
                                <option v-for="material in materials" :key="material.id" :value="String(material.id)">{{ material.name }} ({{ material.unit }})</option>
                            </select>
                            <input v-model="item.quantity_per_unit" type="number" min="0.0001" step="0.0001" class="md:col-span-4 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cantitate" />
                            <button type="button" @click="removeItem(index)" class="md:col-span-1 text-xs border border-red-200 text-red-600 rounded px-2 py-2 hover:bg-red-50">X</button>
                        </div>
                    </div>
                    <p v-if="form.errors.items" class="text-red-500 text-xs mt-1">{{ form.errors.items }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea v-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                        {{ form.processing ? 'Se salveaza...' : 'Creeaza reteta' }}
                    </button>
                    <Link :href="route('recipes.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        Anuleaza
                    </Link>
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
    taskTemplates: { type: Array, default: () => [] },
    materials: { type: Array, default: () => [] },
    presetSubjectType: { type: String, default: null },
    presetSubjectId: { type: Number, default: null },
});

const form = useForm({
    subject_type: props.presetSubjectType || 'task_template',
    subject_id: props.presetSubjectId ? String(props.presetSubjectId) : '',
    name: '',
    unit: '',
    notes: '',
    items: [],
});

const subjectOptions = computed(() => {
    if (form.subject_type === 'material') {
        return props.materials.map((m) => ({ id: String(m.id), label: `${m.name} (${m.unit})` }));
    }

    return props.taskTemplates.map((t) => ({ id: String(t.id), label: t.title }));
});

function setSubjectType(type) {
    if (form.subject_type === type) return;
    form.subject_type = type;
    form.subject_id = '';
}

function onSubjectChange() {
    if (!form.name) {
        const option = subjectOptions.value.find((o) => o.id === form.subject_id);
        if (option) {
            form.name = option.label.replace(/\s*\([^)]*\)\s*$/, '');
        }
    }
}

function submit() {
    form.post(route('recipes.store'));
}

function addItem() {
    if (form.items.length >= 30) return;
    form.items.push({ material_id: '', quantity_per_unit: '' });
}

function removeItem(index) {
    form.items.splice(index, 1);
}
</script>
