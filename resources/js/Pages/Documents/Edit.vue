<template>
    <AppLayout title="Editeaza document">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Editeaza document</h2>
                    <p class="text-sm text-gray-500 mt-1">Actualizeaza metadatele si fisierul atasat.</p>
                </div>
                <Link :href="route('documents.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi</Link>
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Titlu *</label>
                        <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tip *</label>
                        <select v-model="form.type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Status plata *</label>
                        <select v-model="form.payment_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="(label, key) in paymentStatuses" :key="key" :value="key">{{ label }}</option>
                        </select>
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
                        <select v-model="form.stage_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Fara etapa</option>
                            <option v-for="stage in stages" :key="stage.id" :value="stage.id">{{ stage.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Contractor</label>
                        <select v-model="form.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Fara contractor</option>
                            <option v-for="contractor in contractors" :key="contractor.id" :value="contractor.id">{{ contractor.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Suma (RON) *</label>
                        <input v-model.number="form.amount" type="number" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Data emitere *</label>
                        <input v-model="form.issued_at" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Inlocuieste fisier</label>
                        <input type="file" @change="onFileChange" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <div v-if="document.file_name" class="text-xs text-gray-500 mt-1">
                            Curent: {{ document.file_name }}
                            <a v-if="document.file_path" :href="route('documents.download', document.id)" class="text-orange-600 hover:underline ml-2">Descarca</a>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Note</label>
                        <textarea v-model="form.notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div v-if="form.type === 'proc_verbal_receptie'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii proces verbal de receptie</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Comisie de receptie *</label>
                        <textarea v-model="form.type_data.comisie" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume si functie, cate un membru pe linie" />
                        <p v-if="form.errors['type_data.comisie']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.comisie'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Descriere lucrari receptionate *</label>
                        <textarea v-model="form.type_data.descriere_lucrari" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.descriere_lucrari']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.descriere_lucrari'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Defecte constatate</label>
                        <textarea v-model="form.type_data.defecte" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Concluzie *</label>
                        <select v-model="form.type_data.concluzie" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="admis">Admis</option>
                            <option value="respins">Respins</option>
                        </select>
                        <p v-if="form.errors['type_data.concluzie']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.concluzie'] }}</p>
                    </div>
                </div>

                <div v-else-if="form.type === 'proc_verbal_lucrari_ascunse'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii proces verbal de lucrari ascunse</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Descriere lucrari ascunse *</label>
                        <textarea v-model="form.type_data.descriere_lucrari_ascunse" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.descriere_lucrari_ascunse']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.descriere_lucrari_ascunse'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Verificari efectuate *</label>
                        <textarea v-model="form.type_data.verificari_efectuate" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.verificari_efectuate']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.verificari_efectuate'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Responsabil tehnic *</label>
                        <input v-model="form.type_data.responsabil_tehnic" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.responsabil_tehnic']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.responsabil_tehnic'] }}</p>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza modificari' }}
                    </button>
                    <Link :href="route('documents.index')" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Anuleaza</Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    document: Object,
    projects: Array,
    stages: Array,
    contractors: Array,
    types: Object,
    paymentStatuses: Object,
});

const form = useForm({
    title: props.document.title,
    type: props.document.type,
    project_id: props.document.project_id,
    stage_id: props.document.stage_id || '',
    contractor_id: props.document.contractor_id || '',
    amount: Number(props.document.amount || 0),
    issued_at: props.document.issued_at,
    payment_status: props.document.payment_status,
    notes: props.document.notes || '',
    attachment: null,
    type_data: {
        comisie: props.document.type_data?.comisie || '',
        descriere_lucrari: props.document.type_data?.descriere_lucrari || '',
        defecte: props.document.type_data?.defecte || '',
        concluzie: props.document.type_data?.concluzie || 'admis',
        descriere_lucrari_ascunse: props.document.type_data?.descriere_lucrari_ascunse || '',
        verificari_efectuate: props.document.type_data?.verificari_efectuate || '',
        responsabil_tehnic: props.document.type_data?.responsabil_tehnic || '',
    },
});

function onFileChange(event) {
    const [file] = event.target.files || [];
    form.attachment = file || null;
}

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('documents.update', props.document.id));
}
</script>
