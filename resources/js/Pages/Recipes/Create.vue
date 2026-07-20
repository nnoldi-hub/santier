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
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Manopera necesara (ore per 1 {{ form.unit || 'unitate' }})</label>
                        <button type="button" @click="addLaborItem" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Manopera</button>
                    </div>
                    <div v-if="form.labor_items.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                        Optional - fara randuri de manopera.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(item, index) in form.labor_items" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                            <input v-model="item.role" type="text" class="md:col-span-5 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Rol (ex: zugrav)" />
                            <input v-model="item.hours_per_unit" type="number" min="0.0001" step="0.0001" class="md:col-span-3 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ore/unitate" />
                            <input v-model="item.hourly_rate" type="number" min="0" step="0.01" class="md:col-span-3 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Tarif orar (RON)" />
                            <button type="button" @click="removeLaborItem(index)" class="md:col-span-1 text-xs border border-red-200 text-red-600 rounded px-2 py-2 hover:bg-red-50">X</button>
                        </div>
                    </div>
                    <p v-if="form.errors.labor_items" class="text-red-500 text-xs mt-1">{{ form.errors.labor_items }}</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Utilaje necesare (ore per 1 {{ form.unit || 'unitate' }})</label>
                        <button type="button" @click="addEquipmentItem" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Utilaj</button>
                    </div>
                    <div v-if="form.equipment_items.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                        Optional - fara randuri de utilaje.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(item, index) in form.equipment_items" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                            <select v-model="item.equipment_id" class="md:col-span-7 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Utilaj —</option>
                                <option v-for="eq in equipment" :key="eq.id" :value="String(eq.id)">{{ eq.name }} ({{ eq.cost_per_hour }} RON/h)</option>
                            </select>
                            <input v-model="item.hours_per_unit" type="number" min="0.0001" step="0.0001" class="md:col-span-4 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ore/unitate" />
                            <button type="button" @click="removeEquipmentItem(index)" class="md:col-span-1 text-xs border border-red-200 text-red-600 rounded px-2 py-2 hover:bg-red-50">X</button>
                        </div>
                    </div>
                    <p v-if="form.errors.equipment_items" class="text-red-500 text-xs mt-1">{{ form.errors.equipment_items }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Timp uscare (ore)</label>
                        <input v-model="form.drying_hours" type="number" min="0" step="0.5" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: 24" />
                        <p v-if="form.errors.drying_hours" class="text-red-500 text-xs mt-1">{{ form.errors.drying_hours }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Timp intarire - curing (ore)</label>
                        <input v-model="form.curing_hours" type="number" min="0" step="0.5" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: 168" />
                        <p v-if="form.errors.curing_hours" class="text-red-500 text-xs mt-1">{{ form.errors.curing_hours }}</p>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Etape proprii de executie (optional)</label>
                        <button type="button" @click="addWbsStage" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Etapa</button>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Daca definesti etape proprii, devizul automat le foloseste in loc de o singura etapa generica "Executie". Fiecare etapa poate avea task-uri implicite generate automat la commit.</p>
                    <div v-if="form.wbs_stages.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                        Optional - fara etape proprii, devizul foloseste o singura etapa generica de executie.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-for="(stage, index) in form.wbs_stages" :key="index" class="border border-gray-200 rounded-lg p-3 space-y-2">
                            <div class="flex items-center gap-2">
                                <input v-model="stage.name" type="text" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume etapa (ex: Sapatura)" />
                                <button type="button" @click="removeWbsStage(index)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-2 hover:bg-red-50 shrink-0">X</button>
                            </div>
                            <textarea v-model="stage.default_tasks_text" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Task-uri implicite, unul pe linie (optional)"></textarea>
                        </div>
                    </div>
                    <p v-if="form.errors.wbs_stages" class="text-red-500 text-xs mt-1">{{ form.errors.wbs_stages }}</p>
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
    equipment: { type: Array, default: () => [] },
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
    labor_items: [],
    equipment_items: [],
    drying_hours: '',
    curing_hours: '',
    wbs_stages: [],
});

form.transform((data) => ({
    ...data,
    wbs_stages: data.wbs_stages.map((stage) => ({
        name: stage.name,
        default_tasks: (stage.default_tasks_text || '')
            .split('\n')
            .map((task) => task.trim())
            .filter(Boolean),
    })),
}));

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

function addLaborItem() {
    if (form.labor_items.length >= 30) return;
    form.labor_items.push({ role: '', hours_per_unit: '', hourly_rate: '' });
}

function removeLaborItem(index) {
    form.labor_items.splice(index, 1);
}

function addEquipmentItem() {
    if (form.equipment_items.length >= 30) return;
    form.equipment_items.push({ equipment_id: '', hours_per_unit: '' });
}

function removeEquipmentItem(index) {
    form.equipment_items.splice(index, 1);
}

function addWbsStage() {
    if (form.wbs_stages.length >= 10) return;
    form.wbs_stages.push({ name: '', default_tasks_text: '' });
}

function removeWbsStage(index) {
    form.wbs_stages.splice(index, 1);
}
</script>
