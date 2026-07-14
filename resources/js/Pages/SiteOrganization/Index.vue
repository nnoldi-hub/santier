<template>
    <AppLayout :title="'Organizare santier - ' + project.name">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="route('projects.show', project.id)" class="text-gray-400 hover:text-gray-600 text-sm">← {{ project.name }}</Link>
                    <h2 class="mt-1 text-2xl font-black text-slate-900">Organizare Șantier</h2>
                    <p class="mt-1 text-sm text-gray-500">Pregatirea santierului inainte de executie: echipe, subcontractori, resurse, logistica si buget.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="rounded-full border px-3 py-1.5 text-xs font-medium transition"
                    :class="activeTab === tab.key ? 'border-orange-300 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div v-if="activeTab === 'staff'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Adauga plan de personal</h3>
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitStaffPlan">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="staffPlanForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Specialitate *</label>
                            <input v-model="staffPlanForm.specialty" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex: zidar, electrician" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Necesar oameni *</label>
                            <input v-model.number="staffPlanForm.planned_headcount" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Echipa (optional)</label>
                            <select v-model="staffPlanForm.team_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Neselectat —</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Subcontractor (optional)</label>
                            <select v-model="staffPlanForm.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Neselectat —</option>
                                <option v-for="contractor in contractors" :key="contractor.id" :value="contractor.id">{{ contractor.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Risc</label>
                            <select v-model="staffPlanForm.risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in riskLevels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Inceput planificat</label>
                            <input v-model="staffPlanForm.planned_start" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Sfarsit planificat</label>
                            <input v-model="staffPlanForm.planned_end" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Note</label>
                            <textarea v-model="staffPlanForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <button :disabled="staffPlanForm.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ staffPlanForm.processing ? 'Se salveaza...' : 'Adauga plan' }}
                            </button>
                        </div>
                    </form>
                </div>

                <EmptyState
                    v-if="staffPlans.length === 0"
                    :icon="UserGroupIcon"
                    title="Niciun plan de personal"
                    description="Adauga primul plan de personal pentru a incepe pregatirea santierului."
                />

                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Etapa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Specialitate</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Necesar</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Responsabil</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Perioada</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Risc</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in staffPlans" :key="plan.id">
                                <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ plan.specialty }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.planned_headcount }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.team?.name || plan.contractor?.name || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ formatDate(plan.planned_start) }} → {{ formatDate(plan.planned_end) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="riskTone(plan.risk_level)">
                                        {{ riskLevels[plan.risk_level] || plan.risk_level }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button" class="text-xs text-red-600 hover:underline" @click="deleteStaffPlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'contractors'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Adauga plan de subcontractor</h3>
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitContractorPlan">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="contractorPlanForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Subcontractor *</label>
                            <select v-model="contractorPlanForm.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Selecteaza —</option>
                                <option v-for="contractor in contractors" :key="contractor.id" :value="contractor.id">{{ contractor.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Status contract</label>
                            <select v-model="contractorPlanForm.contract_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in contractStatusLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Disponibilitate</label>
                            <select v-model="contractorPlanForm.availability_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in availabilityLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Inceput planificat</label>
                            <input v-model="contractorPlanForm.planned_start" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Sfarsit planificat</label>
                            <input v-model="contractorPlanForm.planned_end" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Note</label>
                            <textarea v-model="contractorPlanForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <button :disabled="contractorPlanForm.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ contractorPlanForm.processing ? 'Se salveaza...' : 'Adauga plan' }}
                            </button>
                        </div>
                    </form>
                </div>

                <EmptyState
                    v-if="contractorPlans.length === 0"
                    :icon="BuildingOffice2Icon"
                    title="Niciun plan de subcontractor"
                    description="Adauga primul candidat de subcontractor pentru a evalua disponibilitatea si contractul."
                />

                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Etapa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Subcontractor</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Contract</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Disponibilitate</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Proiecte paralele</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Perioada</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in contractorPlans" :key="plan.id">
                                <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ plan.contractor?.name || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="contractTone(plan.contract_status)">
                                        {{ contractStatusLabels[plan.contract_status] || plan.contract_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="availabilityTone(plan.availability_status)">
                                        {{ availabilityLabels[plan.availability_status] || plan.availability_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.parallel_projects_count ?? 0 }}</td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ formatDate(plan.planned_start) }} → {{ formatDate(plan.planned_end) }}
                                </td>
                                <td class="px-4 py-3">
                                    <button type="button" class="text-xs text-red-600 hover:underline" @click="deleteContractorPlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <EmptyState
                v-else
                :icon="activeTabInfo?.icon"
                :title="activeTabInfo?.label"
                :description="'Aceasta sectiune va fi disponibila intr-o runda viitoare (vezi organizare-santier.md).'"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';
import {
    UserGroupIcon,
    BuildingOffice2Icon,
    CubeIcon,
    TruckIcon,
    MapPinIcon,
    DocumentTextIcon,
    BanknotesIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project: { type: Object, required: true },
    teams: { type: Array, default: () => [] },
    contractors: { type: Array, default: () => [] },
    staffPlans: { type: Array, default: () => [] },
    riskLevels: { type: Object, default: () => ({}) },
    contractorPlans: { type: Array, default: () => [] },
    contractStatusLabels: { type: Object, default: () => ({}) },
    availabilityLabels: { type: Object, default: () => ({}) },
});

const tabs = [
    { key: 'summary', label: 'Rezumat', icon: ChartBarIcon },
    { key: 'staff', label: 'Echipe & specialitati', icon: UserGroupIcon },
    { key: 'contractors', label: 'Subcontractori', icon: BuildingOffice2Icon },
    { key: 'materials', label: 'Materiale', icon: CubeIcon },
    { key: 'equipment', label: 'Utilaje', icon: TruckIcon },
    { key: 'logistics', label: 'Logistica', icon: MapPinIcon },
    { key: 'documents', label: 'Documente', icon: DocumentTextIcon },
    { key: 'budget', label: 'Buget', icon: BanknotesIcon },
];

const activeTab = ref('staff');
const activeTabInfo = computed(() => tabs.find((tab) => tab.key === activeTab.value));

const staffPlanForm = useForm({
    phase_id: '',
    specialty: '',
    planned_headcount: 1,
    team_id: '',
    contractor_id: '',
    risk_level: 'medium',
    planned_start: '',
    planned_end: '',
    notes: '',
});

function submitStaffPlan() {
    staffPlanForm.post(route('site-organization.staff-plans.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            staffPlanForm.reset();
            staffPlanForm.risk_level = 'medium';
            staffPlanForm.planned_headcount = 1;
        },
    });
}

function deleteStaffPlan(plan) {
    router.delete(route('site-organization.staff-plans.destroy', [props.project.id, plan.id]), {
        preserveScroll: true,
    });
}

const contractorPlanForm = useForm({
    phase_id: '',
    contractor_id: '',
    contract_status: 'missing',
    availability_status: 'ok',
    planned_start: '',
    planned_end: '',
    notes: '',
});

function submitContractorPlan() {
    contractorPlanForm.post(route('site-organization.contractor-plans.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            contractorPlanForm.reset();
            contractorPlanForm.contract_status = 'missing';
            contractorPlanForm.availability_status = 'ok';
        },
    });
}

function deleteContractorPlan(plan) {
    router.delete(route('site-organization.contractor-plans.destroy', [props.project.id, plan.id]), {
        preserveScroll: true,
    });
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('ro-RO');
}

function riskTone(level) {
    if (level === 'high') return 'bg-rose-100 text-rose-700';
    if (level === 'medium') return 'bg-amber-100 text-amber-700';
    return 'bg-emerald-100 text-emerald-700';
}

function contractTone(status) {
    if (status === 'signed') return 'bg-emerald-100 text-emerald-700';
    if (status === 'draft') return 'bg-amber-100 text-amber-700';
    return 'bg-rose-100 text-rose-700';
}

function availabilityTone(status) {
    if (status === 'ok') return 'bg-emerald-100 text-emerald-700';
    if (status === 'risk') return 'bg-amber-100 text-amber-700';
    return 'bg-rose-100 text-rose-700';
}
</script>
