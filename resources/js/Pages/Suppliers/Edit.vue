<template>
    <AppLayout :title="'Editeaza furnizor: ' + supplier.name">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('suppliers.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Editeaza furnizor</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nume furnizor *</label>
                    <input v-model="form.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Persoana de contact</label>
                        <input v-model="form.contact_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                        <input v-model="form.phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="form.email" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" v-model="form.active" class="rounded border-gray-300 text-orange-500" />
                        Furnizor activ
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <textarea v-model="form.notes" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <div class="flex gap-3">
                        <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                            {{ form.processing ? 'Se salveaza...' : 'Salveaza modificarile' }}
                        </button>
                        <Link :href="route('suppliers.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Anuleaza
                        </Link>
                    </div>
                    <button type="button" @click="remove" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">
                        Sterge furnizor
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    supplier: Object,
});

const form = useForm({
    name: props.supplier.name || '',
    contact_name: props.supplier.contact_name || '',
    phone: props.supplier.phone || '',
    email: props.supplier.email || '',
    active: !!props.supplier.active,
    notes: props.supplier.notes || '',
});

function submit() {
    form.patch(route('suppliers.update', props.supplier.id));
}

function remove() {
    if (confirm(`Stergi furnizorul "${props.supplier.name}"?`)) {
        router.delete(route('suppliers.destroy', props.supplier.id));
    }
}
</script>
