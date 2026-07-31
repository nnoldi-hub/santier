<template>
    <AppLayout title="Billing si planuri">
        <div class="max-w-6xl mx-auto space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Plan activ: {{ currentPlanLabel }}<span v-if="subscription?.interval === 'yearly'"> (anual)</span>
                </h2>
                <p class="text-sm text-gray-500 mt-1">Alege un plan sau gestioneaza abonamentul curent.</p>
            </div>

            <div v-if="checkoutMessage" class="rounded-lg border px-4 py-3 text-sm" :class="checkoutStatus === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800'">
                {{ checkoutMessage }}
            </div>

            <div v-if="subscription?.onGracePeriod" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex flex-wrap items-center justify-between gap-3">
                <span>Abonamentul este programat sa se incheie pe {{ subscription.endsAt }} - dupa aceasta data vei trece automat pe planul Demo.</span>
                <button type="button" @click="resumeSubscription" class="rounded-lg bg-amber-600 text-white px-3 py-1.5 text-xs font-medium hover:bg-amber-700 whitespace-nowrap">
                    Revoca anularea
                </button>
            </div>

            <div class="flex items-center gap-2">
                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                    <button
                        type="button"
                        @click="selectedInterval = 'monthly'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium"
                        :class="selectedInterval === 'monthly' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'"
                    >
                        Lunar
                    </button>
                    <button
                        type="button"
                        @click="selectedInterval = 'yearly'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium"
                        :class="selectedInterval === 'yearly' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'"
                    >
                        Anual
                    </button>
                </div>
                <span v-if="selectedInterval === 'yearly'" class="text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded font-medium">
                    2 luni gratis
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl">
                <div
                    v-for="(plan, key) in plans"
                    :key="key"
                    class="bg-white border rounded-xl p-5 flex flex-col"
                    :class="key === currentPlan ? 'border-orange-400 shadow-sm' : 'border-gray-200'"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">{{ plan.label }}</h3>
                        <span v-if="key === currentPlan" class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded">Activ</span>
                    </div>

                    <div class="mt-3">
                        <div class="text-3xl font-black text-gray-900">{{ formatPrice(planPrice(plan)) }}</div>
                        <div class="text-xs text-gray-500">{{ selectedInterval === 'yearly' && plan.price_yearly ? 'an' : (plan.billing_period || 'luna') }}</div>
                    </div>

                    <p v-if="plan.description" class="mt-3 text-sm text-gray-600">
                        {{ plan.description }}
                    </p>

                    <div class="mt-3 text-sm text-gray-600">
                        <div>
                            Proiecte:
                            <span class="font-medium text-gray-900">{{ plan.project_limit === null ? 'Nelimitat' : plan.project_limit }}</span>
                        </div>
                        <div class="mt-2">
                            Utilizatori:
                            <span class="font-medium text-gray-900">{{ plan.users_limit === null ? 'Nelimitat' : plan.users_limit }}</span>
                        </div>
                        <div class="mt-2">Gantt: <span class="font-medium text-gray-900">{{ plan.features?.gantt ? 'Da' : 'Nu' }}</span></div>
                        <div>Export CSV: <span class="font-medium text-gray-900">{{ plan.features?.exports_csv ? 'Da' : 'Nu' }}</span></div>
                        <div>Export Enterprise: <span class="font-medium text-gray-900">{{ plan.features?.exports_enterprise ? 'Da' : 'Nu' }}</span></div>
                    </div>

                    <div class="mt-4 flex-1"></div>

                    <button
                        v-if="key === currentPlan"
                        type="button"
                        disabled
                        class="mt-4 w-full px-3 py-2 rounded-lg text-sm font-medium border border-gray-200 text-gray-400 cursor-not-allowed"
                    >
                        Plan activ
                    </button>
                    <button
                        v-else-if="key === 'free'"
                        type="button"
                        :disabled="!hasActiveSubscription || swapForm.processing"
                        @click="cancelSubscription"
                        class="mt-4 w-full px-3 py-2 rounded-lg text-sm font-medium border border-red-300 text-red-700 hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Renunta la abonament
                    </button>
                    <a
                        v-else-if="!hasActiveSubscription"
                        :href="route('billing.checkout', key) + '?interval=' + selectedInterval"
                        class="mt-4 w-full text-center px-3 py-2 rounded-lg text-sm font-medium border border-orange-300 text-orange-700 hover:bg-orange-50"
                    >
                        Abonare
                    </a>
                    <button
                        v-else
                        type="button"
                        :disabled="swapForm.processing"
                        @click="swapPlan(key)"
                        class="mt-4 w-full px-3 py-2 rounded-lg text-sm font-medium border border-orange-300 text-orange-700 hover:bg-orange-50 disabled:opacity-50"
                    >
                        Schimba planul
                    </button>
                </div>
            </div>

            <div v-if="hasActiveSubscription" class="flex justify-end">
                <a :href="route('billing.portal')" class="text-sm text-gray-600 hover:text-gray-900 underline">
                    Gestioneaza plata (card, facturi, anulare)
                </a>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-5 max-w-2xl">
                <h3 class="font-semibold text-gray-800">Preferi factura si plata prin transfer?</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Cere o factura proforma cu <strong>20% discount de lansare</strong> - o trimitem imediat pe email,
                    platibila prin transfer bancar catre firma ta.
                </p>

                <div v-if="proformaForm.recentlySuccessful && $page.props.flash?.success" class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="proformaForm.recentlySuccessful && $page.props.flash?.error" class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                    {{ $page.props.flash.error }}
                </div>

                <form v-if="!(proformaForm.recentlySuccessful && $page.props.flash?.success)" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3" @submit.prevent="submitProformaRequest">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Denumire firma *</label>
                        <input v-model="proformaForm.company_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <div v-if="proformaForm.errors.company_name" class="mt-1 text-xs text-rose-600">{{ proformaForm.errors.company_name }}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">CUI *</label>
                        <input v-model="proformaForm.company_cui" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <div v-if="proformaForm.errors.company_cui" class="mt-1 text-xs text-rose-600">{{ proformaForm.errors.company_cui }}</div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Adresa firma</label>
                        <input v-model="proformaForm.company_address" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Persoana de contact *</label>
                        <input v-model="proformaForm.contact_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <div v-if="proformaForm.errors.contact_name" class="mt-1 text-xs text-rose-600">{{ proformaForm.errors.contact_name }}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Telefon *</label>
                        <input v-model="proformaForm.contact_phone" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <div v-if="proformaForm.errors.contact_phone" class="mt-1 text-xs text-rose-600">{{ proformaForm.errors.contact_phone }}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Email *</label>
                        <input v-model="proformaForm.contact_email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <div v-if="proformaForm.errors.contact_email" class="mt-1 text-xs text-rose-600">{{ proformaForm.errors.contact_email }}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Plan *</label>
                        <select v-model="proformaForm.plan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="start">Echipa Mica</option>
                            <option value="pro">Brand complet</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Perioada</label>
                        <select v-model="proformaForm.interval" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="monthly">Lunar</option>
                            <option value="yearly">Anual (2 luni gratis)</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <button :disabled="proformaForm.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                            {{ proformaForm.processing ? 'Se trimite...' : 'Trimite cererea' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();

const props = defineProps({
    currentPlan: { type: String, required: true },
    plans: { type: Object, required: true },
    subscription: { type: Object, default: null },
});

const swapForm = useForm({ plan: '', interval: '' });
const cancelForm = useForm({});
const resumeForm = useForm({});

const selectedInterval = ref(props.subscription?.interval || 'monthly');

const currentPlanLabel = computed(() => props.plans?.[props.currentPlan]?.label || props.currentPlan);
const hasActiveSubscription = computed(() => !!props.subscription?.active);

function planPrice(plan) {
    return selectedInterval.value === 'yearly' && plan.price_yearly ? plan.price_yearly : plan.price;
}

const checkoutStatus = ref(new URLSearchParams(window.location.search).get('checkout'));
const checkoutMessage = computed(() => {
    if (checkoutStatus.value === 'success') {
        return 'Plata a fost procesata cu succes - planul tau a fost activat.';
    }
    if (checkoutStatus.value === 'cancelled') {
        return 'Checkout-ul a fost anulat - nu s-a efectuat nicio plata.';
    }
    return '';
});

function formatPrice(price) {
    const numericPrice = Number(price || 0);

    if (numericPrice === 0) {
        return '0 lei';
    }

    return new Intl.NumberFormat('ro-RO', {
        style: 'currency',
        currency: 'RON',
        maximumFractionDigits: 0,
    }).format(numericPrice);
}

function swapPlan(plan) {
    swapForm.plan = plan;
    swapForm.interval = selectedInterval.value;
    swapForm.patch(route('billing.swap'), { preserveScroll: true });
}

function cancelSubscription() {
    if (confirm('Renunti la abonament? Accesul ramane activ pana la finalul perioadei deja platite, apoi treci automat pe Demo.')) {
        cancelForm.patch(route('billing.cancel'), { preserveScroll: true });
    }
}

function resumeSubscription() {
    resumeForm.patch(route('billing.resume'), { preserveScroll: true });
}

const proformaForm = useForm({
    company_name: '',
    company_cui: '',
    company_address: '',
    contact_name: page.props.auth?.user?.name || '',
    contact_email: page.props.auth?.user?.email || '',
    contact_phone: page.props.auth?.user?.phone || '',
    plan: 'pro',
    interval: 'monthly',
});

function submitProformaRequest() {
    proformaForm.post(route('proforma-request.store'), {
        preserveScroll: true,
    });
}
</script>
