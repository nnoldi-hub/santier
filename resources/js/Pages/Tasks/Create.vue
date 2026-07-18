<template>
    <AppLayout title="Task nou">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('tasks.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Task nou</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sabloane</label>
                    <select v-model="selectedTemplateId" @change="applyTemplate" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">— Alege un sablon (optional) —</option>
                        <option v-for="template in templatesList" :key="template.id" :value="template.id">{{ template.title }}</option>
                    </select>

                    <div v-if="selectedTemplate?.recipe" class="mt-2 bg-orange-50 border border-orange-200 rounded-lg p-3 flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-600 mb-1">Cantitate lucrare ({{ selectedTemplate.recipe.unit }})</label>
                            <input v-model="workQuantity" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <button type="button" @click="applyRecipe" :disabled="!workQuantity" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-600 disabled:opacity-50 shrink-0">
                            Aplica reteta
                        </button>
                    </div>
                    <button v-else-if="selectedTemplate" type="button" @click="showNewRecipeModal = true" class="mt-1 text-xs text-orange-500 hover:underline">
                        + Reteta pentru acest sablon
                    </button>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Titlu *</label>
                        <button type="button" @click="saveAsTemplate" :disabled="!canSaveAsTemplate || savingTemplate" class="text-xs text-orange-500 hover:underline disabled:opacity-50 disabled:no-underline">
                            {{ savingTemplate ? 'Se salveaza...' : '+ Salveaza ca sablon' }}
                        </button>
                    </div>
                    <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: Comanda materiale pentru faza de tencuieli" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proiect *</label>
                        <select v-model="form.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="onProjectChange">
                            <option value="">— Selecteaza proiect —</option>
                            <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                        </select>
                        <p v-if="form.errors.project_id" class="text-red-500 text-xs mt-1">{{ form.errors.project_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Etapa</label>
                        <select v-model="form.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— Fara etapa —</option>
                            <option v-for="phase in selectedPhases" :key="phase.id" :value="String(phase.id)">{{ phase.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="todo">To do</option>
                            <option value="in_progress">In progres</option>
                            <option value="done">Finalizat</option>
                            <option value="cancelled">Anulat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioritate *</label>
                        <select v-model="form.priority" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deadline</label>
                        <input v-model="form.deadline" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Responsabil</label>
                    <select v-model="form.assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">— Nealocat —</option>
                        <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descriere</label>
                    <textarea v-model="form.description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Detalii task..."></textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Consum materiale</label>
                        <div class="flex gap-2">
                            <button type="button" @click="showNewMaterialModal = true" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Material nou (catalog)</button>
                            <button type="button" @click="addMaterialRow" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Material</button>
                        </div>
                    </div>
                    <div v-if="form.task_materials.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                        Leaga materialele consumate de task (ex: 3 saci glet, 20m cablu).
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(item, index) in form.task_materials" :key="`mat-${index}`" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                            <select v-model="item.material_id" class="md:col-span-5 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Material —</option>
                                <option v-for="material in materialsList" :key="material.id" :value="String(material.id)">{{ material.name }} ({{ material.unit }})</option>
                            </select>
                            <input v-model="item.quantity" type="number" min="0.01" step="0.01" class="md:col-span-2 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cant." />
                            <input v-model="item.unit_override" type="text" class="md:col-span-2 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="UM" />
                            <input v-model="item.unit_price" type="number" min="0" step="0.01" class="md:col-span-2 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Pret" />
                            <button type="button" @click="removeMaterialRow(index)" class="md:col-span-1 text-xs border border-red-200 text-red-600 rounded px-2 py-2 hover:bg-red-50">X</button>
                        </div>
                    </div>
                    <p v-if="form.errors.task_materials" class="text-red-500 text-xs mt-1">{{ form.errors.task_materials }}</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Checklist intern</label>
                        <button type="button" @click="addChecklistItem" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Pas</button>
                    </div>
                    <div v-if="form.checklist.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                        Adauga pasi interni pentru task (maxim 30).
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(item, index) in form.checklist" :key="index" class="flex items-center gap-2">
                            <input v-model="item.done" type="checkbox" class="rounded border-gray-300" />
                            <input v-model="item.text" type="text" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" :placeholder="`Pas ${index + 1}`" />
                            <button type="button" @click="removeChecklistItem(index)" class="text-xs border border-red-200 text-red-600 rounded px-2 py-1 hover:bg-red-50">Sterge</button>
                        </div>
                    </div>
                    <p v-if="form.errors.checklist" class="text-red-500 text-xs mt-1">{{ form.errors.checklist }}</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                        {{ form.processing ? 'Se salveaza...' : 'Creeaza task' }}
                    </button>
                    <Link :href="route('tasks.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        Anuleaza
                    </Link>
                </div>
            </form>
        </div>

        <QuickCreateModal
            :show="showNewMaterialModal"
            title="Material nou"
            :processing="newMaterialProcessing"
            :error="newMaterialError"
            @close="showNewMaterialModal = false"
            @submit="submitNewMaterial"
        >
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nume *</label>
                <input v-model="newMaterial.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unitate de masura *</label>
                <input v-model="newMaterial.unit" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: buc, mp, kg" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Pret unitar (RON) *</label>
                <input v-model="newMaterial.unit_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
        </QuickCreateModal>

        <QuickCreateModal
            :show="showNewRecipeModal"
            title="Reteta noua pentru acest sablon"
            max-width="lg"
            :processing="newRecipeProcessing"
            :error="newRecipeError"
            @close="showNewRecipeModal = false"
            @submit="submitNewRecipe"
        >
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nume reteta *</label>
                <input v-model="newRecipe.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unitate de baza *</label>
                <input v-model="newRecipe.unit" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: mp, mc, ml" />
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Materiale (consum per 1 unitate)</label>
                    <button type="button" @click="addNewRecipeItem" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Material</button>
                </div>
                <div v-for="(item, index) in newRecipe.items" :key="index" class="grid grid-cols-12 gap-2 items-center mb-2">
                    <select v-model="item.material_id" class="col-span-7 border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                        <option value="">— Material —</option>
                        <option v-for="material in materialsList" :key="material.id" :value="String(material.id)">{{ material.name }} ({{ material.unit }})</option>
                    </select>
                    <input v-model="item.quantity_per_unit" type="number" min="0.0001" step="0.0001" class="col-span-4 border border-gray-300 rounded-lg px-2 py-1.5 text-sm" placeholder="Cant." />
                    <button type="button" @click="newRecipe.items.splice(index, 1)" class="col-span-1 text-xs border border-red-200 text-red-600 rounded px-1 py-1.5 hover:bg-red-50">X</button>
                </div>
            </div>
        </QuickCreateModal>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuickCreateModal from '@/Components/QuickCreateModal.vue';

const props = defineProps({
    projects: Array,
    users: Array,
    materials: Array,
    selectedProjectId: Number,
    phasesByProject: Object,
    taskTemplates: { type: Array, default: () => [] },
});

const form = useForm({
    project_id: props.selectedProjectId ? String(props.selectedProjectId) : '',
    phase_id: '',
    assigned_to: '',
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    deadline: '',
    checklist: [],
    task_materials: [],
});

const selectedPhases = computed(() => {
    if (!form.project_id) return [];
    return props.phasesByProject[form.project_id] || props.phasesByProject[Number(form.project_id)] || [];
});

function onProjectChange() {
    form.phase_id = '';
}

function submit() {
    form.post(route('tasks.store'));
}

function addChecklistItem() {
    if (form.checklist.length >= 30) return;
    form.checklist.push({ text: '', done: false });
}

function removeChecklistItem(index) {
    form.checklist.splice(index, 1);
}

function addMaterialRow() {
    if (form.task_materials.length >= 20) return;
    form.task_materials.push({
        material_id: '',
        quantity: '',
        unit_override: '',
        unit_price: '',
    });
}

function removeMaterialRow(index) {
    form.task_materials.splice(index, 1);
}

const materialsList = ref([...props.materials]);
const showNewMaterialModal = ref(false);
const newMaterialProcessing = ref(false);
const newMaterialError = ref('');
const newMaterial = ref({ name: '', unit: '', unit_price: '' });

function submitNewMaterial() {
    newMaterialProcessing.value = true;
    newMaterialError.value = '';

    axios.post(route('materials.quick-create'), newMaterial.value)
        .then((response) => {
            materialsList.value.push(response.data);
            form.task_materials.push({
                material_id: String(response.data.id),
                quantity: '',
                unit_override: '',
                unit_price: '',
            });
            showNewMaterialModal.value = false;
            newMaterial.value = { name: '', unit: '', unit_price: '' };
        })
        .catch((error) => {
            newMaterialError.value = error.response?.data?.message || 'Nu am putut salva materialul.';
        })
        .finally(() => {
            newMaterialProcessing.value = false;
        });
}

const templatesList = ref([...props.taskTemplates]);
const selectedTemplateId = ref('');
const savingTemplate = ref(false);

const canSaveAsTemplate = computed(() => {
    const title = form.title.trim();
    if (!title) return false;
    return !templatesList.value.some((template) => template.title === title);
});

function applyTemplate() {
    const template = templatesList.value.find((t) => t.id === Number(selectedTemplateId.value));
    if (template) {
        form.title = template.title;
    }
}

function saveAsTemplate() {
    if (!canSaveAsTemplate.value) return;

    savingTemplate.value = true;

    axios.post(route('task-templates.quick-create'), { title: form.title.trim() })
        .then((response) => {
            templatesList.value.push(response.data);
        })
        .finally(() => {
            savingTemplate.value = false;
        });
}

const selectedTemplate = computed(() => templatesList.value.find((t) => t.id === Number(selectedTemplateId.value)) || null);
const workQuantity = ref('');

function applyRecipe() {
    const recipe = selectedTemplate.value?.recipe;
    if (!recipe || !workQuantity.value) return;

    const qty = Number(workQuantity.value);

    recipe.items.forEach((item) => {
        form.task_materials.push({
            material_id: String(item.material_id),
            quantity: Number((item.quantity_per_unit * qty).toFixed(4)),
            unit_override: item.unit || '',
            unit_price: '',
        });
    });

    workQuantity.value = '';
}

const showNewRecipeModal = ref(false);
const newRecipeProcessing = ref(false);
const newRecipeError = ref('');
const newRecipe = ref({ name: '', unit: '', items: [] });

function addNewRecipeItem() {
    if (newRecipe.value.items.length >= 30) return;
    newRecipe.value.items.push({ material_id: '', quantity_per_unit: '' });
}

function submitNewRecipe() {
    newRecipeProcessing.value = true;
    newRecipeError.value = '';

    axios.post(route('recipes.quick-create'), {
        subject_type: 'task_template',
        subject_id: selectedTemplateId.value,
        name: newRecipe.value.name,
        unit: newRecipe.value.unit,
        items: newRecipe.value.items,
    })
        .then((response) => {
            const template = templatesList.value.find((t) => t.id === Number(selectedTemplateId.value));
            if (template) {
                template.recipe = response.data;
            }
            showNewRecipeModal.value = false;
            newRecipe.value = { name: '', unit: '', items: [] };
        })
        .catch((error) => {
            newRecipeError.value = error.response?.data?.message || 'Nu am putut salva reteta.';
        })
        .finally(() => {
            newRecipeProcessing.value = false;
        });
}
</script>
