<template>
    <AppLayout :title="'Editeaza oferta: ' + quote.title">
        <div class="max-w-2xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('quotes.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Editeaza oferta</h2>
            </div>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titlu oferta *</label>
                    <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Proiect *</label>
                        <select v-model="form.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— Selecteaza proiect —</option>
                            <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                        </select>
                        <p v-if="form.errors.project_id" class="text-red-500 text-xs mt-1">{{ form.errors.project_id }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="draft">Ciorna</option>
                            <option value="sent">Trimisa</option>
                            <option value="accepted">Acceptata</option>
                            <option value="rejected">Respinsa</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total net (RON) *</label>
                        <input v-model="form.total_net" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.total_net" class="text-red-500 text-xs mt-1">{{ form.errors.total_net }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount %</label>
                        <input v-model="form.discount_pct" type="number" min="0" max="100" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">TVA %</label>
                        <input v-model="form.tva_pct" type="number" min="0" max="100" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Valabil pana la</label>
                    <input v-model="form.valid_until" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
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
                        <Link :href="route('quotes.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                            Anuleaza
                        </Link>
                    </div>
                    <button type="button" @click="remove" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">
                        Sterge oferta
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
    quote: Object,
    projects: Array,
});

const form = useForm({
    project_id: props.quote.project_id ? String(props.quote.project_id) : '',
    title: props.quote.title || '',
    status: props.quote.status || 'draft',
    valid_until: props.quote.valid_until || '',
    discount_pct: props.quote.discount_pct ?? 0,
    tva_pct: props.quote.tva_pct ?? 21,
    notes: props.quote.notes || '',
    total_net: props.quote.total_net ?? 0,
});

function submit() {
    form.patch(route('quotes.update', props.quote.id));
}

function remove() {
    if (confirm(`Stergi oferta "${props.quote.title}"?`)) {
        router.delete(route('quotes.destroy', props.quote.id));
    }
}
</script>
