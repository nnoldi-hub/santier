<template>
    <AppLayout title="Defect nou">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('defects.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Defect nou</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titlu defect *</label>
                    <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: fisura pe perete in baie" />
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
                        <input v-model="form.location" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: baie etaj 1" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descriere</label>
                    <textarea v-model="form.description" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Detalii defect si context..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Poze defect {{ requiresPhoto ? '(obligatorii pentru Rezolvat)' : '(telefon, optional)' }}</label>
                    <input type="file" accept="image/*" capture="environment" multiple @change="onPhotosSelected" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="form.errors.photos" class="text-red-500 text-xs mt-1">{{ form.errors.photos }}</p>
                    <div v-if="photoNames.length" class="flex flex-wrap gap-2 mt-2">
                        <span v-for="(name, index) in photoNames" :key="`photo-${index}`" class="text-xs bg-gray-100 rounded px-2 py-1 text-gray-600">{{ name }}</span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note rezolvare (optional)</label>
                    <textarea v-model="form.resolution_notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cum a fost/va fi rezolvat defectul..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semnatura digitala (optional)</label>
                    <SignaturePad v-model="form.signature_data_url" />
                    <input v-model="form.signed_by_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mt-2" placeholder="Semnat de (nume)" />
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                        {{ form.processing ? 'Se salveaza...' : 'Creeaza defect' }}
                    </button>
                    <Link :href="route('defects.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        Anuleaza
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps({
    projects: Array,
    users: Array,
    selectedProjectId: Number,
    phasesByProject: Object,
});

const form = useForm({
    project_id: props.selectedProjectId ? String(props.selectedProjectId) : '',
    phase_id: '',
    assigned_to: '',
    title: '',
    description: '',
    location: '',
    status: 'open',
    priority: 'medium',
    due_date: '',
    resolution_notes: '',
    photos: [],
    signature_data_url: '',
    signed_by_name: '',
});

const photoNames = ref([]);

const selectedPhases = computed(() => {
    if (!form.project_id) return [];
    return props.phasesByProject[form.project_id] || props.phasesByProject[Number(form.project_id)] || [];
});

const requiresPhoto = computed(() => form.status === 'resolved');

function onProjectChange() {
    form.phase_id = '';
}

function submit() {
    form.post(route('defects.store'), { forceFormData: true });
}

function onPhotosSelected(event) {
    const files = Array.from(event.target.files || []);
    form.photos = files;
    photoNames.value = files.map((file) => file.name);
}
</script>
