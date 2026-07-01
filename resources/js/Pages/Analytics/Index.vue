<template>
    <AppLayout title="Analytics Funnel">
        <div class="max-w-6xl mx-auto space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Funnel Analytics</h2>
                <p class="text-sm text-gray-500 mt-1">Vizibilitate pe traseul landing -> signup -> onboarding -> primul proiect -> upgrade.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-4" v-for="card in funnelCards" :key="card.key">
                    <div class="text-xs text-gray-500">{{ card.label }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ card.value }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-xs text-gray-500">Signup -> Onboarding</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ conversion.signup_to_onboarding }}%</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-xs text-gray-500">Onboarding -> Primul proiect</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ conversion.onboarding_to_project }}%</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="text-xs text-gray-500">Signup -> Upgrade</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">{{ conversion.signup_to_upgrade }}%</div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <h3 class="font-semibold text-gray-800 mb-3">Evenimente recente</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pr-3">Data</th>
                                <th class="py-2 pr-3">Eveniment</th>
                                <th class="py-2 pr-3">Utilizator</th>
                                <th class="py-2 pr-3">Session</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="event in recentEvents" :key="event.id" class="border-b last:border-0">
                                <td class="py-2 pr-3">{{ formatDate(event.event_at) }}</td>
                                <td class="py-2 pr-3">{{ event.event_name }}</td>
                                <td class="py-2 pr-3">{{ event.user?.email || '-' }}</td>
                                <td class="py-2 pr-3">{{ event.session_id || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    funnel: { type: Object, required: true },
    conversion: { type: Object, required: true },
    recentEvents: { type: Array, default: () => [] },
});

const funnelCards = computed(() => [
    { key: 'landing_views', label: 'Landing views', value: props.funnel.landing_views || 0 },
    { key: 'signups', label: 'Signups', value: props.funnel.signups || 0 },
    { key: 'onboarding_completed', label: 'Onboarding complete', value: props.funnel.onboarding_completed || 0 },
    { key: 'first_project_created', label: 'First project', value: props.funnel.first_project_created || 0 },
    { key: 'trial_upgraded', label: 'Trial upgraded', value: props.funnel.trial_upgraded || 0 },
]);

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('ro-RO');
}
</script>
