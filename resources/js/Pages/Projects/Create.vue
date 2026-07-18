<template>
    <AppLayout title="Proiect nou">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('projects.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Proiect nou</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <!-- Nume -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numele proiectului *</label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="ex: Renovare apartament Floreasca" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- Client -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Client</label>
                        <button type="button" @click="showNewClientModal = true" class="text-xs text-orange-500 hover:underline">+ Client nou</button>
                    </div>
                    <select v-model="form.client_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                        <option value="">— Selecteaza client —</option>
                        <option v-for="c in clientsList" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <!-- Adresa -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresa santier</label>
                    <input v-model="form.address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="Str. Exemplu nr. 10, Sector 1, Bucuresti" />
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
                        <input v-model="form.total_budget" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="0" />
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
                    <textarea v-model="form.description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="Descriere scurta a proiectului..."></textarea>
                </div>

                <!-- Butoane -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                        {{ form.processing ? 'Se salveaza...' : 'Creeaza proiect' }}
                    </button>
                    <Link :href="route('projects.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        Anuleaza
                    </Link>
                </div>
            </form>
        </div>

        <QuickCreateModal
            :show="showNewClientModal"
            title="Client nou"
            :processing="newClientProcessing"
            :error="newClientError"
            @close="showNewClientModal = false"
            @submit="submitNewClient"
        >
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nume *</label>
                <input v-model="newClient.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tip *</label>
                <select v-model="newClient.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="company">Firma</option>
                    <option value="person">Persoana fizica</option>
                </select>
            </div>
        </QuickCreateModal>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuickCreateModal from '@/Components/QuickCreateModal.vue';

const props = defineProps({
    clients: Array,
});

const form = useForm({
    name: '',
    client_id: '',
    address: '',
    status: 'draft',
    total_budget: '',
    start_date: '',
    end_date: '',
    description: '',
    notes: '',
});

function submit() {
    form.post(route('projects.store'));
}

const clientsList = ref([...props.clients]);
const showNewClientModal = ref(false);
const newClientProcessing = ref(false);
const newClientError = ref('');
const newClient = ref({ name: '', type: 'company' });

function submitNewClient() {
    newClientProcessing.value = true;
    newClientError.value = '';

    axios.post(route('clients.quick-create'), newClient.value)
        .then((response) => {
            clientsList.value.push(response.data);
            form.client_id = response.data.id;
            showNewClientModal.value = false;
            newClient.value = { name: '', type: 'company' };
        })
        .catch((error) => {
            newClientError.value = error.response?.data?.message || 'Nu am putut salva clientul.';
        })
        .finally(() => {
            newClientProcessing.value = false;
        });
}
</script>
