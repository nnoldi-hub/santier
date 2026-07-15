<template>
    <AppLayout title="Document nou">
        <div class="max-w-3xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Adauga document financiar</h2>
                    <p class="text-sm text-gray-500 mt-1">Contract, factura, deviz, oferta sau proces verbal pe proiect/etapa.</p>
                </div>
                <Link :href="route('documents.index')" class="text-sm text-gray-500 hover:text-gray-700">Inapoi</Link>
            </div>

            <form @submit.prevent="submit" class="bg-white border border-gray-200 rounded-xl p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Titlu *</label>
                        <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.title" class="text-xs text-red-600 mt-1">{{ form.errors.title }}</p>
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
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Fisier</label>
                        <input type="file" @change="onFileChange" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.attachment" class="text-xs text-red-600 mt-1">{{ form.errors.attachment }}</p>
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

                <div v-else-if="form.type === 'proc_verbal_predare_primire'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii proces verbal de predare-primire</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Predat de *</label>
                        <input v-model="form.type_data.predat_de" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.predat_de']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.predat_de'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Primit de *</label>
                        <input v-model="form.type_data.primit_de" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.primit_de']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.primit_de'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Obiecte / materiale / echipamente *</label>
                        <textarea v-model="form.type_data.obiecte" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cate un obiect pe linie" />
                        <p v-if="form.errors['type_data.obiecte']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.obiecte'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Stare la predare *</label>
                        <textarea v-model="form.type_data.stare" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.stare']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.stare'] }}</p>
                    </div>
                </div>

                <div v-else-if="form.type === 'proc_verbal_remediere_defecte'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii proces verbal de remediere defecte</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Defect identificat *</label>
                        <textarea v-model="form.type_data.defect_identificat" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.defect_identificat']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.defect_identificat'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Responsabil remediere *</label>
                        <input v-model="form.type_data.responsabil_remediere" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.responsabil_remediere']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.responsabil_remediere'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Termen *</label>
                        <input v-model="form.type_data.termen" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.termen']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.termen'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Stare remediere *</label>
                        <select v-model="form.type_data.stare_remediere" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="remediat">Remediat</option>
                            <option value="nerezolvat">Nerezolvat</option>
                        </select>
                        <p v-if="form.errors['type_data.stare_remediere']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.stare_remediere'] }}</p>
                    </div>
                </div>

                <div v-else-if="form.type === 'proc_verbal_constatare'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii proces verbal de constatare</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Situatie constatata *</label>
                        <textarea v-model="form.type_data.situatie_constatata" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.situatie_constatata']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.situatie_constatata'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Martori *</label>
                        <textarea v-model="form.type_data.martori" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cate un martor pe linie" />
                        <p v-if="form.errors['type_data.martori']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.martori'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Masuri recomandate</label>
                        <textarea v-model="form.type_data.masuri_recomandate" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div v-else-if="form.type === 'contract'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii contract prestari servicii</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Parti contractante *</label>
                        <textarea v-model="form.type_data.parti_contractante" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Prestator: ... / Beneficiar: ..." />
                        <p v-if="form.errors['type_data.parti_contractante']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.parti_contractante'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Obiect contract *</label>
                        <textarea v-model="form.type_data.obiect_contract" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.obiect_contract']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.obiect_contract'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Termene *</label>
                        <textarea v-model="form.type_data.termene" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.termene']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.termene'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Penalitati *</label>
                        <textarea v-model="form.type_data.penalitati" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.penalitati']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.penalitati'] }}</p>
                    </div>
                </div>

                <div v-else-if="form.type === 'invoice'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii factura</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Serie + numar factura *</label>
                        <input v-model="form.invoice_number" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: FAC-2026-0001" />
                        <p v-if="form.errors.invoice_number" class="text-xs text-red-600 mt-1">{{ form.errors.invoice_number }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Produse / servicii *</label>
                        <textarea v-model="form.type_data.produse_servicii" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Produs/serviciu, cantitate, pret unitar - cate o linie" />
                        <p v-if="form.errors['type_data.produse_servicii']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.produse_servicii'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">TVA (%) *</label>
                        <input v-model.number="form.type_data.tva_pct" type="number" step="0.01" min="0" max="100" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.tva_pct']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.tva_pct'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Scadenta *</label>
                        <input v-model="form.type_data.scadenta" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.scadenta']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.scadenta'] }}</p>
                    </div>
                </div>

                <div v-else-if="form.type === 'delivery_note'" class="border-t border-gray-200 pt-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">Detalii aviz de insotire marfa</h3>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Furnizor *</label>
                        <input v-model="form.type_data.furnizor" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.furnizor']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.furnizor'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Materiale *</label>
                        <textarea v-model="form.type_data.materiale" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Material, cantitate - cate o linie" />
                        <p v-if="form.errors['type_data.materiale']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.materiale'] }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Transportator *</label>
                        <input v-model="form.type_data.transportator" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors['type_data.transportator']" class="text-xs text-red-600 mt-1">{{ form.errors['type_data.transportator'] }}</p>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                        {{ form.processing ? 'Se salveaza...' : 'Salveaza document' }}
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

defineProps({
    projects: Array,
    stages: Array,
    contractors: Array,
    types: Object,
    paymentStatuses: Object,
});

const form = useForm({
    title: '',
    type: 'invoice',
    project_id: '',
    stage_id: '',
    contractor_id: '',
    amount: 0,
    issued_at: '',
    payment_status: 'unpaid',
    notes: '',
    attachment: null,
    invoice_number: '',
    type_data: {
        comisie: '',
        descriere_lucrari: '',
        defecte: '',
        concluzie: 'admis',
        descriere_lucrari_ascunse: '',
        verificari_efectuate: '',
        responsabil_tehnic: '',
        predat_de: '',
        primit_de: '',
        obiecte: '',
        stare: '',
        defect_identificat: '',
        responsabil_remediere: '',
        termen: '',
        stare_remediere: 'remediat',
        situatie_constatata: '',
        martori: '',
        masuri_recomandate: '',
        parti_contractante: '',
        obiect_contract: '',
        termene: '',
        penalitati: '',
        produse_servicii: '',
        tva_pct: 19,
        scadenta: '',
        furnizor: '',
        materiale: '',
        transportator: '',
    },
});

function onFileChange(event) {
    const [file] = event.target.files || [];
    form.attachment = file || null;
}

function submit() {
    form.post(route('documents.store'));
}
</script>
