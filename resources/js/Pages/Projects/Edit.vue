<template>
    <AppLayout :title="'Editeaza: ' + project.name">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('projects.show', project.id)" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Editeaza proiect</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

                <!-- Nume -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numele proiectului *</label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- Client -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                    <select v-model="form.client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <option value="">— Fara client —</option>
                        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <!-- Adresa -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresa santier</label>
                    <input v-model="form.address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                </div>

                <!-- Status + Budget -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                            <option value="draft">Ciorna</option>
                            <option value="active">Activ</option>
                            <option value="paused">Pauza</option>
                            <option value="completed">Finalizat</option>
                            <option value="cancelled">Anulat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buget total (RON)</label>
                        <input v-model="form.total_budget" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                    </div>
                </div>

                <!-- Date -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data inceput</label>
                        <input v-model="form.start_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data final estimata</label>
                        <input v-model="form.end_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                        <p v-if="form.errors.end_date" class="text-red-500 text-xs mt-1">{{ form.errors.end_date }}</p>
                    </div>
                </div>

                <!-- Descriere -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descriere</label>
                    <textarea v-model="form.description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"></textarea>
                </div>

                <!-- Butoane -->
                <div class="flex items-center justify-between pt-2">
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                            {{ form.processing ? 'Se salveaza...' : 'Salveaza modificarile' }}
                        </button>
                        <Link :href="route('projects.show', project.id)" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Anuleaza
                        </Link>
                    </div>
                    <button type="button" @click="confirmDelete = true" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">
                        Sterge proiect
                    </button>
                </div>
            </form>
        </div>

        <!-- Confirm delete modal -->
        <div v-if="confirmDelete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Stergi proiectul?</h3>
                <p class="text-gray-500 text-sm mb-6">
                    Proiectul <strong>{{ project.name }}</strong> va fi sters definitiv.
                </p>
                <div class="flex gap-3 justify-end">
                    <button @click="confirmDelete = false" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                        Anuleaza
                    </button>
                    <button @click="deleteProject" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600">
                        Da, sterge
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    project: Object,
    clients: Array,
});

const confirmDelete = ref(false);

const form = useForm({
    name:         props.project.name,
    client_id:    props.project.client_id ?? '',
    address:      props.project.address ?? '',
    status:       props.project.status,
    total_budget: props.project.total_budget ?? '',
    start_date:   props.project.start_date ?? '',
    end_date:     props.project.end_date ?? '',
    description:  props.project.description ?? '',
    notes:        props.project.notes ?? '',
});

function submit() {
    form.patch(route('projects.update', props.project.id));
}

function deleteProject() {
    router.delete(route('projects.destroy', props.project.id));
}
</script>
