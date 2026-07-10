<template>
    <AppLayout title="Configurare documente">
        <div class="max-w-5xl mx-auto space-y-5">
            <section class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 via-white to-cyan-50 p-5">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                            Doar conturi cu abonament platit
                        </div>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Configurare documente profesionale</h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Setezi emitentul, logo-ul, culorile si datele companiei pentru documentele emise clientilor.
                        </p>
                    </div>
                    <button type="button" @click="showPreview = !showPreview" class="rounded-xl border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                        {{ showPreview ? 'Ascunde preview' : 'Preview document' }}
                    </button>
                </div>
            </section>

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Ajutor contextual</div>
                        <div class="text-sm font-semibold text-amber-900">Checklist pentru documente cu aspect profesionist</div>
                    </div>
                    <button type="button" @click="showBrandingHelp = !showBrandingHelp" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">
                        {{ showBrandingHelp ? 'Ascunde checklist' : 'Arata checklist' }}
                    </button>
                </div>

                <div v-if="showBrandingHelp" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="rounded-lg border border-amber-200 bg-white p-3">
                        <div class="text-sm font-semibold text-slate-900">Ce trebuie setat obligatoriu</div>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="item in brandingChecklist" :key="item" class="flex gap-2 text-xs text-slate-700">
                                <span class="mt-1 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-white p-3">
                        <div class="text-sm font-semibold text-slate-900">Recomandari pentru preview</div>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="item in previewRecommendations" :key="item" class="flex gap-2 text-xs text-slate-700">
                                <span class="mt-1 h-1.5 w-1.5 rounded-full bg-indigo-500"></span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section v-if="showPreview" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Preview</div>
                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="rounded-lg border-b-4 bg-white p-4" :style="{ borderColor: settingsForm.document_brand_color }">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-12 w-auto object-contain mb-2" />
                                <div class="text-lg font-bold" :style="{ color: settingsForm.document_brand_color }">Oferta comerciala</div>
                                <div class="text-xs text-slate-500">Document preview pentru branding</div>
                            </div>
                            <div class="text-right text-xs text-slate-600">
                                <div class="font-semibold text-slate-900">{{ settingsForm.company_name }}</div>
                                <div v-if="settingsForm.company_address">{{ settingsForm.company_address }}</div>
                                <div v-if="settingsForm.company_phone">Tel: {{ settingsForm.company_phone }}</div>
                                <div>{{ settingsForm.support_email }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-700">
                        <div class="font-semibold">Emitent document</div>
                        <div class="mt-1">{{ effectiveIssuer }}</div>
                    </div>
                </div>
            </section>

            <form class="rounded-2xl border border-slate-200 bg-white p-5 space-y-4" @submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Companie</label>
                        <input v-model="settingsForm.company_name" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        <p v-if="settingsForm.errors.company_name" class="mt-1 text-xs text-red-600">{{ settingsForm.errors.company_name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Emitent document</label>
                        <input v-model="settingsForm.document_issuer_name" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Nume persoana sau departament" />
                        <p class="mt-1 text-[11px] text-slate-500">Daca nu completezi, se foloseste numele companiei.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Telefon</label>
                        <input v-model="settingsForm.company_phone" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Email suport</label>
                        <input v-model="settingsForm.support_email" type="email" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        <p v-if="settingsForm.errors.support_email" class="mt-1 text-xs text-red-600">{{ settingsForm.errors.support_email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Email vanzari</label>
                        <input v-model="settingsForm.sales_email" type="email" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                        <p v-if="settingsForm.errors.sales_email" class="mt-1 text-xs text-red-600">{{ settingsForm.errors.sales_email }}</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Adresa companie</label>
                    <input v-model="settingsForm.company_address" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Logo documente (URL)</label>
                        <input v-model="settingsForm.document_logo_url" type="text" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="https://.../logo.png" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Sau incarca logo</label>
                        <input type="file" accept="image/*" @change="onLogoFileChange" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm bg-white" />
                        <p class="mt-1 text-[11px] text-slate-500">PNG/JPG, maxim 2 MB.</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 mb-2">Alege culoarea documentelor</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="preset in colorPresets"
                            :key="preset.value"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-medium"
                            :class="settingsForm.document_brand_color === preset.value ? 'border-slate-900 text-slate-900' : 'border-slate-300 text-slate-600'"
                            @click="settingsForm.document_brand_color = preset.value"
                        >
                            <span class="h-3 w-3 rounded-full border border-slate-300" :style="{ backgroundColor: preset.value }" />
                            {{ preset.name }}
                        </button>
                    </div>
                    <input v-model="settingsForm.document_brand_color" type="color" class="mt-3 h-10 w-20 rounded border border-slate-300" />
                    <p v-if="settingsForm.errors.document_brand_color" class="mt-1 text-xs text-red-600">{{ settingsForm.errors.document_brand_color }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60" :disabled="settingsForm.processing">
                        {{ settingsForm.processing ? 'Se salveaza...' : 'Salveaza configurarea' }}
                    </button>
                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="showPreview = true">
                        Preview
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    colorPresets: { type: Array, default: () => [] },
});

const showPreview = ref(true);
const showBrandingHelp = ref(true);

const brandingChecklist = [
    'Companie + emitent document completate.',
    'Logo clar, pe fundal transparent daca este disponibil.',
    'Culoare brand lizibila atat pe ecran, cat si in PDF.',
    'Email suport si date de contact actualizate.',
];

const previewRecommendations = [
    'Verifica antetul, emitentul si contactele in preview.',
    'Salveaza, apoi genereaza un PDF real dintr-o oferta/document.',
    'Daca logo-ul este prea mare, incarca o varianta compacta.',
    'Evita culori foarte deschise pentru text si linii importante.',
];

const settingsForm = useForm({
    company_name: props.settings.company_name || '',
    document_issuer_name: props.settings.document_issuer_name || '',
    company_phone: props.settings.company_phone || '',
    company_address: props.settings.company_address || '',
    support_email: props.settings.support_email || '',
    sales_email: props.settings.sales_email || '',
    document_logo_url: props.settings.document_logo_url || '',
    document_logo_file: null,
    document_brand_color: props.settings.document_brand_color || '#f97316',
});

const logoPreview = ref(props.settings.document_logo_url || '');

const effectiveIssuer = computed(() => {
    const issuer = String(settingsForm.document_issuer_name || '').trim();
    if (issuer !== '') {
        return issuer;
    }

    return settingsForm.company_name || 'Companie';
});

function save() {
    settingsForm.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(route('documents.branding.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
}

function onLogoFileChange(event) {
    const [file] = event.target.files || [];

    settingsForm.document_logo_file = file || null;

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
        return;
    }

    logoPreview.value = settingsForm.document_logo_url || '';
}
</script>
