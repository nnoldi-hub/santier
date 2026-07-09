<template>
    <AppLayout title="Dashboard Comercial">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-sky-50 p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            <span>📈</span>
                            Business overview
                        </div>
                        <h2 class="mt-3 text-3xl font-black text-slate-900">Dashboard Comercial</h2>
                        <p class="mt-2 max-w-3xl text-sm text-slate-600">
                            Vizibilitate pe venit, conversii si pipeline, pentru a decide mai clar unde investesti in demo, trial si follow-up.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a :href="route('admin.commercial-dashboard.export')" class="inline-flex items-center justify-center rounded-xl border border-emerald-300 bg-white px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 transition">
                            Export CSV management
                        </a>
                        <a :href="route('admin.commercial-dashboard.export-xlsx')" class="inline-flex items-center justify-center rounded-xl border border-sky-300 bg-white px-4 py-3 text-sm font-semibold text-sky-700 hover:bg-sky-50 transition">
                            Export XLSX board
                        </a>
                        <Link :href="route('admin.tenants.index')" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Firme & Abonamente
                        </Link>
                        <Link :href="route('pilot-invites.index')" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">
                            Vezi firme pilot
                        </Link>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <article v-for="card in kpiCards" :key="card.key" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ card.label }}</div>
                    <div class="mt-2 text-3xl font-black text-slate-900">{{ card.value }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ card.note }}</div>
                </article>
            </div>

            <section class="grid grid-cols-1 xl:grid-cols-[1fr_1fr] gap-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Funnel</div>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Lead -> demo -> trial -> paid</h3>
                    <div class="mt-5 grid grid-cols-2 md:grid-cols-3 gap-3">
                        <div v-for="item in funnelCards" :key="item.key" class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs text-slate-500">{{ item.label }}</div>
                            <div class="mt-1 text-2xl font-black text-slate-900">{{ item.value }}</div>
                        </div>
                    </div>
                    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div v-for="item in conversionCards" :key="item.key" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <div class="text-xs text-emerald-700">{{ item.label }}</div>
                            <div class="mt-1 text-2xl font-black text-emerald-900">{{ item.value }}%</div>
                        </div>
                    </div>
                    <div class="mt-5 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Etape CRM</div>
                        <div class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div v-for="item in stageCards" :key="item.key" class="rounded-xl border border-sky-100 bg-white px-4 py-3">
                                <div class="text-xs text-slate-500">{{ item.label }}</div>
                                <div class="mt-1 text-lg font-black text-slate-900">{{ item.value }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Forecast</div>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Prognoza simpla 30 / 60 / 90 zile</h3>
                    <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div v-for="item in forecastCards" :key="item.key" class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs text-slate-500">{{ item.label }}</div>
                            <div class="mt-1 text-2xl font-black text-slate-900">{{ item.value }}</div>
                        </div>
                    </div>
                    <div class="mt-5 rounded-2xl border border-sky-200 bg-sky-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Valoare pipeline ponderat</div>
                        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                            <div class="rounded-xl bg-white px-4 py-3 border border-sky-100">
                                <div class="text-slate-500">Contactati</div>
                                <div class="mt-1 font-bold text-slate-900">{{ formatMoney(pipelineValue.contacted) }}</div>
                            </div>
                            <div class="rounded-xl bg-white px-4 py-3 border border-sky-100">
                                <div class="text-slate-500">Demo programat</div>
                                <div class="mt-1 font-bold text-slate-900">{{ formatMoney(pipelineValue.demo_scheduled) }}</div>
                            </div>
                            <div class="rounded-xl bg-white px-4 py-3 border border-sky-100">
                                <div class="text-slate-500">Trial pornit</div>
                                <div class="mt-1 font-bold text-slate-900">{{ formatMoney(pipelineValue.trial_started) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-[0.9fr_1.1fr] gap-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Semnale platforma</div>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Sanatate comerciala actuala</h3>
                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl bg-slate-50 p-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Firme totale</div>
                                <div class="text-xs text-slate-500">Baza activa pentru crestere</div>
                            </div>
                            <div class="text-2xl font-black text-slate-900">{{ tenantStats.tenants_total }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Firme trial</div>
                                <div class="text-xs text-slate-500">Potential de conversie imediata</div>
                            </div>
                            <div class="text-2xl font-black text-slate-900">{{ tenantStats.tenants_trial }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Firme platitoare</div>
                                <div class="text-xs text-slate-500">Venit recurent deja validat</div>
                            </div>
                            <div class="text-2xl font-black text-slate-900">{{ tenantStats.tenants_paid }}</div>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-amber-900">Trial-uri aproape de expirare</div>
                                <div class="text-xs text-amber-700">Necesita follow-up comercial</div>
                            </div>
                            <div class="text-2xl font-black text-amber-900">{{ tenantStats.tenants_at_risk }}</div>
                        </div>
                        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-rose-900">Risc ridicat (scor complet)</div>
                                <div class="text-xs text-rose-700">Trial + onboarding + semnal churn</div>
                            </div>
                            <div class="text-2xl font-black text-rose-900">{{ riskOverview.high_risk_count }}</div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Semnale comerciale recente</div>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">Lead-uri si miscari recente in pipeline</h3>
                    </div>
                    <div v-if="recentCommercialSignals.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                        Nu exista semnale comerciale inca.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                                <tr>
                                    <th class="px-5 py-3 text-left">Firma</th>
                                    <th class="px-5 py-3 text-left">Status</th>
                                    <th class="px-5 py-3 text-left">Etapa comerciala</th>
                                    <th class="px-5 py-3 text-left">Utilizatori</th>
                                    <th class="px-5 py-3 text-left">Personalizare</th>
                                    <th class="px-5 py-3 text-left">Data</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="signal in recentCommercialSignals" :key="signal.id">
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ signal.company_name }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ labelStatus(signal.status) }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ labelStage(signal.commercial_stage) }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ signal.estimated_users ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ signal.customization_scope_label || '-' }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ formatDateTime(signal.created_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 xl:grid-cols-[0.95fr_1.05fr] gap-4">
                <div class="rounded-3xl border border-amber-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-amber-100 px-5 py-4 bg-amber-50/70">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Risc churn / trial</div>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">Trial-uri aproape de expirare</h3>
                    </div>
                    <div v-if="trialRiskTenants.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                        Nu exista trial-uri care expira in urmatoarele 7 zile.
                    </div>
                    <div v-else class="divide-y divide-slate-100">
                        <div v-for="tenant in trialRiskTenants" :key="tenant.id" class="px-5 py-4 flex items-start justify-between gap-4">
                            <div>
                                <div class="font-semibold text-slate-900">{{ tenant.name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ tenant.active_memberships_count }} utilizatori activi · plan {{ tenant.billing_plan }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-semibold text-amber-900">{{ tenant.days_left }} zile</div>
                                <div class="text-xs text-slate-500">Expira la {{ formatDate(tenant.trial_ends_at) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-sky-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-sky-100 px-5 py-4 bg-sky-50/70">
                        <div class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Oportunitati</div>
                        <h3 class="mt-1 text-lg font-bold text-slate-900">Top oportunitati din pipeline</h3>
                    </div>
                    <div v-if="topPipelineOpportunities.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                        Nu exista oportunitati active in pipeline.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                                <tr>
                                    <th class="px-5 py-3 text-left">Status</th>
                                    <th class="px-5 py-3 text-left">Etapa comerciala</th>
                                    <th class="px-5 py-3 text-left">Utilizatori</th>
                                    <th class="px-5 py-3 text-left">Personalizare</th>
                                    <th class="px-5 py-3 text-left">Plan recomandat</th>
                                    <th class="px-5 py-3 text-left">MRR potential</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="(item, index) in topPipelineOpportunities" :key="`${item.status}-${item.recommended_plan}-${index}`">
                                    <td class="px-5 py-4 text-slate-700">{{ labelStatus(item.status) }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ labelStage(item.commercial_stage) }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ item.estimated_users ?? '-' }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ item.customization_scope_label || '-' }}</td>
                                    <td class="px-5 py-4 text-slate-700">{{ labelPlan(item.recommended_plan) }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ formatMoney(item.recommended_mrr) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-rose-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-rose-100 px-5 py-4 bg-rose-50/70">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-700">Scor risc complet</div>
                    <h3 class="mt-1 text-lg font-bold text-slate-900">Top firme la risc comercial</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 px-5 py-4 border-b border-slate-100 bg-slate-50/60">
                    <div class="rounded-xl bg-white border border-slate-200 p-3">
                        <div class="text-xs text-slate-500">Risc ridicat</div>
                        <div class="mt-1 text-xl font-black text-rose-700">{{ riskOverview.high_risk_count }}</div>
                    </div>
                    <div class="rounded-xl bg-white border border-slate-200 p-3">
                        <div class="text-xs text-slate-500">Risc mediu</div>
                        <div class="mt-1 text-xl font-black text-amber-700">{{ riskOverview.medium_risk_count }}</div>
                    </div>
                    <div class="rounded-xl bg-white border border-slate-200 p-3">
                        <div class="text-xs text-slate-500">Gap onboarding</div>
                        <div class="mt-1 text-xl font-black text-slate-900">{{ riskOverview.tenants_with_onboarding_gap }}</div>
                    </div>
                    <div class="rounded-xl bg-white border border-slate-200 p-3">
                        <div class="text-xs text-slate-500">Semnal churn</div>
                        <div class="mt-1 text-xl font-black text-slate-900">{{ riskOverview.tenants_with_churn_signal }}</div>
                    </div>
                </div>

                <div v-if="riskScoredTenants.length === 0" class="px-5 py-12 text-center text-sm text-slate-500">
                    Nu exista semnale de risc active in acest moment.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 uppercase text-[11px] tracking-[0.15em]">
                            <tr>
                                <th class="px-5 py-3 text-left">Firma</th>
                                <th class="px-5 py-3 text-left">Scor</th>
                                <th class="px-5 py-3 text-left">Factori</th>
                                <th class="px-5 py-3 text-left">Trial</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="tenant in riskScoredTenants" :key="tenant.id">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-slate-900">{{ tenant.name }}</div>
                                    <div class="text-xs text-slate-500">Plan {{ tenant.billing_plan }} · {{ tenant.active_memberships_count }} utilizatori activi</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold" :class="riskTone(tenant.risk_level)">
                                        {{ riskLabel(tenant.risk_level) }} · {{ tenant.risk_score }} / 100
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div class="text-xs">Onboarding incomplet: {{ tenant.onboarding_incomplete_memberships_count }}</div>
                                    <div class="text-xs">Semnal churn: {{ tenant.churn_signal ? 'Da' : 'Nu' }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-700">
                                    <div class="text-xs">{{ tenant.trial_expiring_soon ? 'Expira curand' : 'Stabil' }}</div>
                                    <div class="text-xs text-slate-500">{{ formatDate(tenant.trial_ends_at) }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { commercialRiskTone, labelCommercialPlan, labelCommercialRisk, labelCommercialStage, labelCommercialStatus } from '@/Support/commercialLabels';

const props = defineProps({
    kpis: { type: Object, required: true },
    funnel: { type: Object, required: true },
    stageFunnel: { type: Object, default: () => ({}) },
    conversion: { type: Object, required: true },
    forecast: { type: Object, required: true },
    pipelineValue: { type: Object, required: true },
    tenantStats: { type: Object, required: true },
    recentCommercialSignals: { type: Array, default: () => [] },
    trialRiskTenants: { type: Array, default: () => [] },
    riskOverview: { type: Object, default: () => ({}) },
    riskScoredTenants: { type: Array, default: () => [] },
    topPipelineOpportunities: { type: Array, default: () => [] },
});

const kpiCards = computed(() => [
    { key: 'current_mrr', label: 'MRR total', value: formatMoney(props.kpis.current_mrr || 0), note: 'Venit recurent lunar estimat acum' },
    { key: 'tenants_paid', label: 'Firme platitoare', value: props.kpis.tenants_paid || 0, note: 'Starter, Pro sau Enterprise' },
    { key: 'tenants_trial', label: 'Firme trial', value: props.kpis.tenants_trial || 0, note: 'Trial activ si conversie potentiala' },
    { key: 'tenants_at_risk', label: 'Trial la risc', value: props.kpis.tenants_at_risk || 0, note: 'Expira in urmatoarele 7 zile' },
]);

const funnelCards = computed(() => [
    { key: 'invited', label: 'Invitate', value: props.funnel.invited || 0 },
    { key: 'contacted', label: 'Contactate', value: props.funnel.contacted || 0 },
    { key: 'demo_scheduled', label: 'Demo programat', value: props.funnel.demo_scheduled || 0 },
    { key: 'trial_started', label: 'Trial pornit', value: props.funnel.trial_started || 0 },
    { key: 'closed_won', label: 'Castigate', value: props.funnel.closed_won || 0 },
    { key: 'closed_lost', label: 'Pierdute', value: props.funnel.closed_lost || 0 },
]);

const stageCards = computed(() => [
    { key: 'prospecting', label: 'Prospectare', value: props.stageFunnel.prospecting || 0 },
    { key: 'contacted', label: 'Contactat', value: props.stageFunnel.contacted || 0 },
    { key: 'follow_up', label: 'Follow-up', value: props.stageFunnel.follow_up || 0 },
    { key: 'demo', label: 'Demo', value: props.stageFunnel.demo || 0 },
    { key: 'trial', label: 'Trial', value: props.stageFunnel.trial || 0 },
    { key: 'negotiation', label: 'Negociere', value: props.stageFunnel.negotiation || 0 },
    { key: 'won', label: 'Castigat', value: props.stageFunnel.won || 0 },
    { key: 'lost', label: 'Pierdut', value: props.stageFunnel.lost || 0 },
]);

const conversionCards = computed(() => [
    { key: 'pilot_to_demo', label: 'Pilot -> Demo', value: props.conversion.pilot_to_demo || 0 },
    { key: 'pilot_to_trial', label: 'Pilot -> Trial', value: props.conversion.pilot_to_trial || 0 },
    { key: 'pilot_to_paid', label: 'Pilot -> Paid', value: props.conversion.pilot_to_paid || 0 },
]);

const forecastCards = computed(() => [
    { key: 'forecast_30_days', label: 'Forecast 30 zile', value: formatMoney(props.forecast.forecast_30_days || 0) },
    { key: 'forecast_60_days', label: 'Forecast 60 zile', value: formatMoney(props.forecast.forecast_60_days || 0) },
    { key: 'forecast_90_days', label: 'Forecast 90 zile', value: formatMoney(props.forecast.forecast_90_days || 0) },
]);

function formatMoney(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(Number(value || 0));
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('ro-RO');
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('ro-RO');
}

const labelStatus = labelCommercialStatus;
const labelPlan = labelCommercialPlan;
const labelStage = labelCommercialStage;
const riskTone = commercialRiskTone;
const riskLabel = labelCommercialRisk;
</script>