<template>
    <AppLayout title="Onboarding">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h2 class="text-xl font-semibold text-gray-800">Onboarding initial (3 pasi)</h2>
                <p class="text-sm text-gray-500 mt-1">Completeaza setup-ul de baza pentru a intra in aplicatie.</p>

                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div v-for="step in [1, 2, 3]" :key="step" class="rounded-lg px-3 py-2 border text-sm" :class="stepClass(step)">
                        Pasul {{ step }}
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6" v-if="currentStep <= 1">
                <h3 class="font-semibold text-gray-800">Pasul 1 - Date companie</h3>
                <form class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitStep1">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Nume companie *</label>
                        <input v-model="step1.company_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tip *</label>
                        <select v-model="step1.company_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="company">Companie</option>
                            <option value="person">Persoana</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Telefon contact</label>
                        <input v-model="step1.contact_phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-3">
                        <button :disabled="step1.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                            {{ step1.processing ? 'Se salveaza...' : 'Salveaza pasul 1' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6" v-if="currentStep <= 2">
                <h3 class="font-semibold text-gray-800">Pasul 2 - Primul proiect</h3>
                <form class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitStep2">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Nume proiect *</label>
                        <input v-model="step2.project_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Buget estimat</label>
                        <input v-model="step2.project_budget" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-600 mb-1">Adresa proiect</label>
                        <input v-model="step2.project_address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-3">
                        <button :disabled="step2.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                            {{ step2.processing ? 'Se salveaza...' : 'Salveaza pasul 2' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="font-semibold text-gray-800">Pasul 3 - Prima echipa</h3>
                <form class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitStep3">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Nume echipa *</label>
                        <input v-model="step3.team_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Specialitate</label>
                        <input v-model="step3.team_specialty" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-3">
                        <button :disabled="step3.processing" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-60">
                            {{ step3.processing ? 'Se finalizeaza...' : 'Finalizeaza onboarding' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    currentStep: { type: Number, default: 1 },
    onboardingData: { type: Object, default: () => ({}) },
});

const step1 = useForm({
    company_name: props.onboardingData.company_name || '',
    company_type: props.onboardingData.company_type || 'company',
    contact_phone: props.onboardingData.contact_phone || '',
});

const step2 = useForm({
    project_name: props.onboardingData.project_name || '',
    project_address: props.onboardingData.project_address || '',
    project_budget: props.onboardingData.project_budget || '',
});

const step3 = useForm({
    team_name: props.onboardingData.team_name || '',
    team_specialty: props.onboardingData.team_specialty || '',
});

function submitStep1() {
    step1.post(route('onboarding.step1'));
}

function submitStep2() {
    step2.post(route('onboarding.step2'));
}

function submitStep3() {
    step3.post(route('onboarding.step3'));
}

function stepClass(step) {
    if (props.currentStep > step) {
        return 'bg-green-50 border-green-300 text-green-700';
    }

    if (props.currentStep === step) {
        return 'bg-orange-50 border-orange-300 text-orange-700';
    }

    return 'bg-gray-50 border-gray-200 text-gray-500';
}
</script>
