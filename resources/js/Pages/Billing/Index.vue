<template>
    <AppLayout title="Billing si planuri">
        <div class="max-w-6xl mx-auto space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Plan activ: {{ currentPlanLabel }}</h2>
                <p class="text-sm text-gray-500 mt-1">Schimba planul pentru a testa limitele comerciale direct in aplicatie.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div
                    v-for="(plan, key) in plans"
                    :key="key"
                    class="bg-white border rounded-xl p-5"
                    :class="key === currentPlan ? 'border-orange-400 shadow-sm' : 'border-gray-200'"
                >
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">{{ plan.label }}</h3>
                        <span v-if="key === currentPlan" class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded">Activ</span>
                    </div>

                    <div class="mt-3">
                        <div class="text-3xl font-black text-gray-900">{{ formatPrice(plan.price) }}</div>
                        <div class="text-xs text-gray-500">{{ plan.billing_period || 'luna' }}</div>
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

                    <button
                        @click="switchPlan(key)"
                        :disabled="key === currentPlan || form.processing"
                        class="mt-4 w-full px-3 py-2 rounded-lg text-sm font-medium border transition"
                        :class="key === currentPlan
                            ? 'border-gray-200 text-gray-400 cursor-not-allowed'
                            : 'border-orange-300 text-orange-700 hover:bg-orange-50'"
                    >
                        {{ key === currentPlan ? 'Plan activ' : 'Activeaza planul' }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    currentPlan: { type: String, required: true },
    plans: { type: Object, required: true },
});

const form = useForm({ plan: props.currentPlan });

const currentPlanLabel = computed(() => props.plans?.[props.currentPlan]?.label || props.currentPlan);

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

function switchPlan(plan) {
    form.plan = plan;
    form.patch(route('billing.update'), {
        preserveScroll: true,
    });
}
</script>
