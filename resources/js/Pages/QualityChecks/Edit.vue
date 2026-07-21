<template>
    <AppLayout title="Editeaza verificare">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Editeaza verificare</h2>
                    <p class="text-sm text-gray-500 mt-1">Actualizeaza starea si datele controlului de calitate.</p>
                </div>
                <Link :href="route('quality-checks.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi</Link>
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Titlu *</label>
                        <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Proiect *</label>
                        <select v-model="form.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Selecteaza</option>
                            <option v-for="project in projects" :key="project.id" :value="project.id">{{ project.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Etapa</label>
                        <select v-model="form.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Fara etapa</option>
                            <option v-for="phase in availablePhases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Responsabil</label>
                        <select v-model="form.assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Nealocat</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tip verificare *</label>
                        <select v-model="form.check_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tip receptie *</label>
                        <select v-model="form.reception_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in receptionTypes" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Status *</label>
                        <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Planificat la</label>
                        <input v-model="form.planned_at" type="datetime-local" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Descriere</label>
                        <textarea v-model="form.description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Note</label>
                        <textarea v-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs text-gray-600">Checklist verificare</label>
                            <div class="flex items-center gap-2">
                                <select v-model="selectedRecipeId" class="text-xs border border-gray-300 rounded-lg px-2 py-1">
                                    <option value="">Reteta (optional)</option>
                                    <option v-for="recipe in recipes" :key="recipe.id" :value="recipe.id">{{ recipe.name }}</option>
                                </select>
                                <button type="button" @click="applyChecklistFromRecipe" :disabled="!selectedRecipeId" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50 disabled:opacity-50">Aplica checklist</button>
                                <button type="button" @click="addChecklistItem" class="text-xs border border-gray-300 rounded px-2 py-1 text-gray-600 hover:bg-gray-50">+ Item</button>
                            </div>
                        </div>
                        <div v-if="form.checklist.length === 0" class="text-xs text-gray-400 border border-dashed border-gray-300 rounded-lg p-3">
                            Pentru verificari complexe adauga itemi de checklist.
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="(item, index) in form.checklist" :key="`qc-edit-item-${index}`" class="flex items-center gap-2">
                                <input v-model="item.done" type="checkbox" class="rounded border-gray-300" />
                                <input v-model="item.text" type="text" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" :placeholder="`Item ${index + 1}`" />
                                <button type="button" @click="removeChecklistItem(index)">Sterge</button>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Poze {{ requiresPhoto ? '(obligatorii pentru Conform/Neconform)' : '(optional)' }}</label>
                        <div v-if="photos.length" class="flex flex-wrap gap-2 mb-2">
                            <div v-for="photo in photos" :key="photo.id" class="flex items-center gap-1 text-xs bg-gray-100 rounded px-2 py-1 text-gray-600">
                                <a :href="photo.url" target="_blank" rel="noopener" class="hover:underline">{{ photo.name }}</a>
                                <button type="button" @click="removePhoto(photo.id)" class="text-red-500 hover:text-red-700">×</button>
                            </div>
                        </div>
                        <input type="file" accept="image/*" multiple @change="onPhotosSelected" class="text-sm" />
                        <div v-if="newPhotoNames.length" class="flex flex-wrap gap-2 mt-2">
                            <span v-for="(name, index) in newPhotoNames" :key="`new-photo-${index}`" class="text-xs bg-orange-50 rounded px-2 py-1 text-orange-700">{{ name }}</span>
                        </div>
                        <p v-if="form.errors.photos" class="text-xs text-red-600 mt-1">{{ form.errors.photos }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Semnatura digitala (optional)</label>
                        <p v-if="qualityCheck.signature_path" class="text-xs text-gray-500 mb-2">
                            Semnata deja de {{ qualityCheck.signed_by_name || '-' }} la {{ qualityCheck.signed_at ? new Date(qualityCheck.signed_at).toLocaleString('ro-RO') : '-' }}. Deseneaza mai jos pentru a inlocui.
                        </p>
                        <SignaturePad v-model="form.signature_data_url" />
                        <input v-model="form.signed_by_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-2" placeholder="Semnat de (nume)" />
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza modificari' }}
                    </button>
                    <Link :href="route('quality-checks.index')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Anuleaza</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps({
    qualityCheck: Object,
    projects: Array,
    users: Array,
    phasesByProject: Object,
    statuses: Object,
    types: Object,
    receptionTypes: Object,
    recipes: { type: Array, default: () => [] },
    photos: { type: Array, default: () => [] },
});

const form = useForm({
    project_id: props.qualityCheck.project_id,
    phase_id: props.qualityCheck.phase_id || '',
    assigned_to: props.qualityCheck.assigned_to || '',
    title: props.qualityCheck.title,
    description: props.qualityCheck.description || '',
    checklist: Array.isArray(props.qualityCheck.checklist) ? props.qualityCheck.checklist.map((item) => ({ text: item.text || '', done: !!item.done })) : [],
    check_type: props.qualityCheck.check_type,
    reception_type: props.qualityCheck.reception_type || 'partial',
    status: props.qualityCheck.status,
    planned_at: props.qualityCheck.planned_at ? props.qualityCheck.planned_at.slice(0, 16) : '',
    notes: props.qualityCheck.notes || '',
    photos: [],
    signature_data_url: '',
    signed_by_name: props.qualityCheck.signed_by_name || '',
});

const selectedRecipeId = ref('');
const newPhotoNames = ref([]);
const photos = ref(props.photos);

const availablePhases = computed(() => {
    if (!form.project_id) return [];
    return props.phasesByProject?.[form.project_id] || props.phasesByProject?.[String(form.project_id)] || [];
});

const requiresPhoto = computed(() => ['passed', 'failed'].includes(form.status));

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('quality-checks.update', props.qualityCheck.id));
}

function addChecklistItem() {
    if (form.checklist.length >= 40) return;
    form.checklist.push({ text: '', done: false });
}

function removeChecklistItem(index) {
    form.checklist.splice(index, 1);
}

function applyChecklistFromRecipe() {
    const recipe = props.recipes.find((r) => String(r.id) === String(selectedRecipeId.value));
    if (!recipe) return;

    for (const text of recipe.default_checklist || []) {
        if (form.checklist.length >= 40) break;
        form.checklist.push({ text, done: false });
    }
}

function onPhotosSelected(event) {
    const files = Array.from(event.target.files || []);
    form.photos = files;
    newPhotoNames.value = files.map((file) => file.name);
}

function removePhoto(photoId) {
    if (!confirm('Stergi aceasta poza?')) return;
    router.delete(route('quality-checks.photos.destroy', [props.qualityCheck.id, photoId]), {
        preserveScroll: true,
        onSuccess: () => {
            photos.value = photos.value.filter((photo) => photo.id !== photoId);
        },
    });
}
</script>
