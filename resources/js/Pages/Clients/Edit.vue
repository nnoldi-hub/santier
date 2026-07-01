<template>
    <AppLayout :title="'Editeaza: ' + client.name">
        <div class="max-w-xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('clients.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Editeaza client</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">

                <!-- Tip -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tip client *</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="form.type" value="company" class="accent-orange-500" />
                            <span class="text-sm">🏢 Firma / PFA</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" v-model="form.type" value="person" class="accent-orange-500" />
                            <span class="text-sm">👤 Persoana fizica</span>
                        </label>
                    </div>
                </div>

                <!-- Nume -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ form.type === 'company' ? 'Denumire firma *' : 'Nume complet *' }}
                    </label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- CUI / CNP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ form.type === 'company' ? 'CUI' : 'CNP' }}
                    </label>
                    <input v-model="form.tax_id" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                </div>

                <!-- Telefon + Email -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                        <input v-model="form.phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                </div>

                <!-- Adresa -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                    <input v-model="form.address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                </div>

                <!-- Persoana de contact -->
                <div v-if="form.type === 'company'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Persoana de contact</label>
                    <input v-model="form.contact_person" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                </div>

                <!-- Butoane -->
                <div class="flex items-center justify-between pt-2">
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                            {{ form.processing ? 'Se salveaza...' : 'Salveaza modificarile' }}
                        </button>
                        <Link :href="route('clients.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Anuleaza
                        </Link>
                    </div>
                    <!-- Delete -->
                    <button type="button" @click="confirmDelete = true" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">
                        Sterge client
                    </button>
                </div>
            </form>
        </div>

        <!-- Confirm delete modal -->
        <div v-if="confirmDelete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-6 max-w-sm w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Stergi clientul?</h3>
                <p class="text-gray-500 text-sm mb-6">
                    Clientul <strong>{{ client.name }}</strong> va fi sters. Proiectele asociate nu vor fi sterse.
                </p>
                <div class="flex gap-3 justify-end">
                    <button @click="confirmDelete = false" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">
                        Anuleaza
                    </button>
                    <button @click="deleteClient" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600">
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
    client: Object,
});

const confirmDelete = ref(false);

const form = useForm({
    name:           props.client.name,
    type:           props.client.type,
    tax_id:         props.client.tax_id ?? '',
    phone:          props.client.phone ?? '',
    email:          props.client.email ?? '',
    address:        props.client.address ?? '',
    contact_person: props.client.contact_person ?? '',
    notes:          props.client.notes ?? '',
});

function submit() {
    form.patch(route('clients.update', props.client.id));
}

function deleteClient() {
    router.delete(route('clients.destroy', props.client.id));
}
</script>
