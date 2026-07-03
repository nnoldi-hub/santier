<template>
    <AppLayout :title="'Editeaza task: ' + task.title">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('tasks.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Editeaza task</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titlu *</label>
                    <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
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
                    <textarea v-model="form.description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Consum materiale</label>
                        <button type="button" @click="addMaterialRow" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Material</button>
                    </div>
                    <div v-if="form.task_materials.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                        Leaga materialele consumate de task (ex: 3 saci glet, 20m cablu).
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="(item, index) in form.task_materials" :key="`mat-${index}`" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                            <select v-model="item.material_id" class="md:col-span-5 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Material —</option>
                                <option v-for="material in materials" :key="material.id" :value="String(material.id)">{{ material.name }} ({{ material.unit }})</option>
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

                <div class="flex items-center justify-between pt-2">
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                            {{ form.processing ? 'Se salveaza...' : 'Salveaza modificarile' }}
                        </button>
                        <Link :href="route('tasks.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Anuleaza
                        </Link>
                    </div>
                    <button type="button" @click="remove" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">
                        Sterge task
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    task: Object,
    projects: Array,
    users: Array,
    materials: Array,
    taskMaterials: Array,
    phasesByProject: Object,
});

const form = useForm({
    project_id: props.task.project_id ? String(props.task.project_id) : '',
    phase_id: props.task.phase_id ? String(props.task.phase_id) : '',
    assigned_to: props.task.assigned_to ? String(props.task.assigned_to) : '',
    title: props.task.title || '',
    description: props.task.description || '',
    status: props.task.status || 'todo',
    priority: props.task.priority || 'medium',
    deadline: props.task.deadline ? props.task.deadline.slice(0, 10) : '',
    checklist: Array.isArray(props.task.checklist) ? props.task.checklist.map((item) => ({ text: item.text || '', done: !!item.done })) : [],
    task_materials: Array.isArray(props.taskMaterials)
        ? props.taskMaterials.map((item) => ({
            material_id: item.material_id ? String(item.material_id) : '',
            quantity: item.quantity ?? '',
            unit_override: item.unit_override || '',
            unit_price: item.unit_price ?? '',
        }))
        : [],
});

const selectedPhases = computed(() => {
    if (!form.project_id) return [];
    return props.phasesByProject[form.project_id] || props.phasesByProject[Number(form.project_id)] || [];
});

function onProjectChange() {
    form.phase_id = '';
}

function submit() {
    form.patch(route('tasks.update', props.task.id));
}

function remove() {
    if (confirm(`Stergi taskul "${props.task.title}"?`)) {
        router.delete(route('tasks.destroy', props.task.id));
    }
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
</script>
