<template>
    <AppLayout title="Client nou">
        <div class="max-w-xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('clients.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Client nou</h2>
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
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
                        :placeholder="form.type === 'company' ? 'ex: SC Construct SRL' : 'ex: Ion Popescu'" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <!-- CUI / CNP -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ form.type === 'company' ? 'CUI' : 'CNP' }}
                    </label>
                    <input v-model="form.tax_id" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
                        :placeholder="form.type === 'company' ? 'RO12345678' : '1234567890123'" />
                </div>

                <!-- Telefon + Email -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                        <input v-model="form.phone" type="tel" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="07xx xxx xxx" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="email@firma.ro" />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                </div>

                <!-- Adresa -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresa</label>
                    <input v-model="form.address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="Str. Exemplu nr. 1, Bucuresti" />
                </div>

                <!-- Persoana de contact (doar firma) -->
                <div v-if="form.type === 'company'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Persoana de contact</label>
                    <input v-model="form.contact_person" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" placeholder="Nume reprezentant" />
                </div>

                <!-- Butoane -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza client' }}
                    </button>
                    <Link :href="route('clients.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                        Anuleaza
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    name:           '',
    type:           'company',
    tax_id:         '',
    phone:          '',
    email:          '',
    address:        '',
    contact_person: '',
    notes:          '',
});

function submit() {
    form.post(route('clients.store'));
}
</script>
