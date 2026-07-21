<template>
    <AppLayout :title="'Editeaza defect: ' + defect.title">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('defects.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Editeaza defect</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titlu defect *</label>
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
                            <option value="open">Deschis</option>
                            <option value="in_progress">In progres</option>
                            <option value="resolved">Rezolvat</option>
                            <option value="rejected">Respins</option>
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
                        <input v-model="form.due_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Responsabil</label>
                        <select v-model="form.assigned_to" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— Nealocat —</option>
                            <option v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Locatie in santier</label>
                        <input v-model="form.location" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descriere</label>
                    <textarea v-model="form.description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poze defect {{ requiresPhoto ? '(obligatorii pentru Rezolvat)' : '(telefon, optional)' }}</label>
                    <div v-if="defect.photo_url" class="mb-2">
                        <p class="text-xs text-gray-500 mb-1">Foto initiala</p>
                        <img :src="defect.photo_url" alt="Foto initiala defect" class="rounded-lg border border-gray-200 max-h-40 object-cover" />
                    </div>
                    <div v-if="photos.length" class="flex flex-wrap gap-2 mb-2">
                        <div v-for="photo in photos" :key="photo.id" class="flex items-center gap-1 text-xs bg-gray-100 rounded px-2 py-1 text-gray-600">
                            <a :href="photo.url" target="_blank" rel="noopener" class="hover:underline">{{ photo.name }}</a>
                            <button type="button" @click="removePhoto(photo.id)" class="text-red-500 hover:text-red-700">×</button>
                        </div>
                    </div>
                    <input type="file" accept="image/*" capture="environment" multiple @change="onPhotosSelected" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="form.errors.photos" class="text-red-500 text-xs mt-1">{{ form.errors.photos }}</p>
                    <div v-if="newPhotoNames.length" class="flex flex-wrap gap-2 mt-2">
                        <span v-for="(name, index) in newPhotoNames" :key="`new-photo-${index}`" class="text-xs bg-orange-50 rounded px-2 py-1 text-orange-700">{{ name }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note rezolvare (optional)</label>
                    <textarea v-model="form.resolution_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cum a fost/va fi rezolvat defectul..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semnatura digitala (optional)</label>
                    <p v-if="defect.signature_path" class="text-xs text-gray-500 mb-2">
                        Semnata deja de {{ defect.signed_by_name || '-' }} la {{ defect.signed_at ? new Date(defect.signed_at).toLocaleString('ro-RO') : '-' }}. Deseneaza mai jos pentru a inlocui.
                    </p>
                    <SignaturePad v-model="form.signature_data_url" />
                    <input v-model="form.signed_by_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-2" placeholder="Semnat de (nume)" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                            {{ form.processing ? 'Se salveaza...' : 'Salveaza modificarile' }}
                        </button>
                        <Link :href="route('defects.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Anuleaza
                        </Link>
                    </div>
                    <button type="button" @click="remove" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">
                        Sterge defect
                    </button>
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
    defect: Object,
    projects: Array,
    users: Array,
    phasesByProject: Object,
});

const form = useForm({
    project_id: props.defect.project_id ? String(props.defect.project_id) : '',
    phase_id: props.defect.phase_id ? String(props.defect.phase_id) : '',
    assigned_to: props.defect.assigned_to ? String(props.defect.assigned_to) : '',
    title: props.defect.title || '',
    description: props.defect.description || '',
    location: props.defect.location || '',
    status: props.defect.status || 'open',
    priority: props.defect.priority || 'medium',
    due_date: props.defect.due_date || '',
    resolution_notes: props.defect.resolution_notes || '',
    photos: [],
    signature_data_url: '',
    signed_by_name: props.defect.signed_by_name || '',
});

const newPhotoNames = ref([]);
const photos = ref(props.defect.photos || []);

const selectedPhases = computed(() => {
    if (!form.project_id) return [];
    return props.phasesByProject[form.project_id] || props.phasesByProject[Number(form.project_id)] || [];
});

const requiresPhoto = computed(() => form.status === 'resolved');

function onProjectChange() {
    form.phase_id = '';
}

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(route('defects.update', props.defect.id), { forceFormData: true });
}

function remove() {
    if (confirm(`Stergi defectul "${props.defect.title}"?`)) {
        router.delete(route('defects.destroy', props.defect.id));
    }
}

function onPhotosSelected(event) {
    const files = Array.from(event.target.files || []);
    form.photos = files;
    newPhotoNames.value = files.map((file) => file.name);
}

function removePhoto(photoId) {
    if (!confirm('Stergi aceasta poza?')) return;
    router.delete(route('defects.photos.destroy', [props.defect.id, photoId]), {
        preserveScroll: true,
        onSuccess: () => {
            photos.value = photos.value.filter((photo) => photo.id !== photoId);
        },
    });
}
</script>
