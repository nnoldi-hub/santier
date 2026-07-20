<template>
    <AppLayout :title="'Organizare santier - ' + project.name">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <Link :href="route('projects.show', project.id)" class="text-gray-400 hover:text-gray-600 text-sm">← {{ project.name }}</Link>
                    <h2 class="mt-1 text-2xl font-black text-slate-900">Organizare Șantier</h2>
                    <p class="mt-1 text-sm text-gray-500">Pregatirea santierului inainte de executie: echipe, subcontractori, resurse, logistica si buget.</p>
                </div>
                <div class="flex gap-2 shrink-0">
                    <a :href="route('site-organization.export.pdf', project.id)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Export PDF
                    </a>
                    <a :href="route('site-organization.export.xlsx', project.id)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Export XLSX
                    </a>
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

            <div
                class="rounded-xl border p-4 flex items-center justify-between gap-4 flex-wrap"
                :class="isLocked ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200'"
            >
                <div class="text-sm" :class="isLocked ? 'text-emerald-800' : 'text-amber-800'">
                    <p v-if="isLocked" class="font-semibold">Plan aprobat pe {{ formatDate(project.plan_approved_at) }} de {{ project.plan_approved_by_name || 'un utilizator' }}.</p>
                    <p v-if="isLocked">Editarea planurilor este blocata. Elementele de executie au fost generate.</p>
                    <p v-else class="font-semibold">Planul nu este inca aprobat.</p>
                    <p v-if="!isLocked">Cand toate domeniile sunt pregatite, aproba planul pentru a genera automat elementele de executie.</p>
                </div>
                <button
                    v-if="!isLocked"
                    type="button"
                    class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 shrink-0"
                    @click="approvePlan"
                >
                    Aproba planul si activeaza executia
                </button>
                <button
                    v-else
                    type="button"
                    class="border border-emerald-300 text-emerald-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-100 shrink-0"
                    @click="unapprovePlan"
                >
                    Anuleaza aprobarea
                </button>
            </div>

            <div v-if="activeTab === 'summary'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-6 text-center">
                    <p class="text-xs uppercase tracking-wide text-gray-500">Scor de pregatire santier</p>
                    <p class="mt-2 text-5xl font-black" :class="readinessTone(readiness.score).text">{{ readiness.score }}</p>
                    <span class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold" :class="readinessTone(readiness.score).badge">
                        {{ readiness.label }}
                    </span>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Scor pe domenii</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <button
                            v-for="domain in readiness.domains"
                            :key="domain.key"
                            type="button"
                            class="text-left border border-gray-200 rounded-lg p-3 hover:border-orange-300 transition"
                            @click="activeTab = domain.key"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ domain.label }}</span>
                                <span class="text-sm font-semibold" :class="readinessTone(domain.score).text">{{ domain.score }}</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-gray-100">
                                <div class="h-1.5 rounded-full" :class="readinessTone(domain.score).bar" :style="{ width: domain.score + '%' }"></div>
                            </div>
                        </button>
                    </div>
                </div>

                <div v-if="readiness.blockers.length" class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Blocaje</h3>
                    <ul class="space-y-2">
                        <li v-for="(blocker, index) in readiness.blockers" :key="index" class="flex items-start gap-2 text-sm text-gray-700">
                            <span class="mt-0.5 h-1.5 w-1.5 rounded-full bg-rose-500 shrink-0"></span>
                            {{ blocker }}
                        </li>
                    </ul>
                </div>

                <EmptyState
                    v-else
                    :icon="ChartBarIcon"
                    title="Niciun blocaj identificat"
                    description="Toate domeniile verificate arata bine pe baza datelor introduse pana acum."
                />
            </div>

            <div v-else-if="activeTab === 'staff'" class="space-y-6">
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
                            <label class="block text-xs text-gray-600 mb-1">Tarif orar (RON/ora)</label>
                            <input v-model.number="staffPlanForm.hourly_rate" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: 45" />
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
                            <button :disabled="staffPlanForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ isLocked ? 'Editare blocata (plan aprobat)' : (staffPlanForm.processing ? 'Se salveaza...' : 'Adauga plan') }}
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
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Ore estimate</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cost estimat</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Ore reale</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cost real</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Risc</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-for="plan in staffPlans" :key="plan.id">
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ plan.specialty }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ plan.planned_headcount }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ plan.team?.name || plan.contractor?.name || '-' }}
                                        <span v-if="plan.team_overlap_count > 0" class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                            {{ plan.team_overlap_count }} suprapuneri echipa
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 text-xs">
                                        {{ formatDate(plan.planned_start) }} → {{ formatDate(plan.planned_end) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ plan.estimated_hours }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ formatCurrency(plan.estimated_cost) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ plan.actual_hours }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="plan.actual_cost > plan.estimated_cost ? 'text-rose-600 font-semibold' : 'text-gray-600'">
                                            {{ formatCurrency(plan.actual_cost) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="riskTone(plan.risk_level)">
                                            {{ riskLevels[plan.risk_level] || plan.risk_level }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                                        <button type="button" class="text-xs text-orange-600 hover:underline" @click="toggleTimeEntries(plan)">
                                            {{ expandedTimeEntryPlanId === plan.id ? 'Ascunde pontaj' : 'Pontaj' }}
                                        </button>
                                        <button v-if="!isLocked" type="button" class="text-xs text-red-600 hover:underline" @click="deleteStaffPlan(plan)">Sterge</button>
                                    </td>
                                </tr>
                                <tr v-if="expandedTimeEntryPlanId === plan.id" class="bg-gray-50">
                                    <td colspan="11" class="px-4 py-4">
                                        <form class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end" @submit.prevent="submitTimeEntry(plan)">
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Data *</label>
                                                <input v-model="timeEntryForm.entry_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Ore lucrate *</label>
                                                <input v-model.number="timeEntryForm.hours_worked" type="number" min="0.1" step="0.1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                            </div>
                                            <div>
                                                <label class="block text-xs text-gray-600 mb-1">Nota</label>
                                                <input v-model="timeEntryForm.notes" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                                            </div>
                                            <div>
                                                <button :disabled="timeEntryForm.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60 w-full">
                                                    {{ timeEntryForm.processing ? 'Se salveaza...' : 'Adauga pontaj' }}
                                                </button>
                                            </div>
                                        </form>

                                        <p v-if="!plan.timeEntries || plan.timeEntries.length === 0" class="text-xs text-gray-500 mt-3">Nicio inregistrare de pontaj inca.</p>
                                        <ul v-else class="mt-3 divide-y divide-gray-200 border border-gray-200 rounded-lg bg-white">
                                            <li v-for="entry in plan.timeEntries" :key="entry.id" class="px-3 py-2 flex items-center justify-between text-sm">
                                                <span class="text-gray-700">
                                                    {{ formatDate(entry.entry_date) }} · {{ entry.hours_worked }} ore
                                                    <span v-if="entry.notes" class="text-gray-500">· {{ entry.notes }}</span>
                                                </span>
                                                <button type="button" class="text-xs text-red-600 hover:underline" @click="deleteTimeEntry(plan, entry)">Sterge</button>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </template>
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
                            <button :disabled="contractorPlanForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ isLocked ? 'Editare blocata (plan aprobat)' : (contractorPlanForm.processing ? 'Se salveaza...' : 'Adauga plan') }}
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
                                    <button v-if="!isLocked" type="button" class="text-xs text-red-600 hover:underline" @click="deleteContractorPlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'materials'" class="space-y-6">
                <div v-if="recipes.length" class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Aplica reteta</h3>
                    <form class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end" @submit.prevent="submitApplyRecipe">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Reteta *</label>
                            <select v-model="applyRecipeForm.recipe_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Selecteaza —</option>
                                <option v-for="recipe in recipes" :key="recipe.id" :value="recipe.id">{{ recipe.name }} ({{ recipe.unit }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="applyRecipeForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Cantitate lucrare *</label>
                            <input v-model.number="applyRecipeForm.work_quantity" type="number" min="0.01" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <button :disabled="applyRecipeForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60 w-full">
                                {{ applyRecipeForm.processing ? 'Se genereaza...' : 'Genereaza planuri' }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Adauga plan de material</h3>
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitMaterialPlan">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="materialPlanForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Material *</label>
                            <select v-model="materialPlanForm.material_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="prefillMaterialPrice">
                                <option value="">— Selecteaza —</option>
                                <option v-for="material in materials" :key="material.id" :value="material.id">{{ material.name }} ({{ material.unit }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Cantitate planificata *</label>
                            <input v-model.number="materialPlanForm.planned_quantity" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Pret unitar (RON)</label>
                            <input v-model.number="materialPlanForm.unit_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="din catalog daca lasi gol" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Furnizor</label>
                            <input v-model="materialPlanForm.supplier_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Lead-time (zile)</label>
                            <input v-model.number="materialPlanForm.lead_time_days" type="number" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Risc</label>
                            <select v-model="materialPlanForm.risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in materialRiskLevels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Data comanda planificata</label>
                            <input v-model="materialPlanForm.planned_order_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Data livrare planificata</label>
                            <input v-model="materialPlanForm.planned_delivery_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Note</label>
                            <textarea v-model="materialPlanForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <button :disabled="materialPlanForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ isLocked ? 'Editare blocata (plan aprobat)' : (materialPlanForm.processing ? 'Se salveaza...' : 'Adauga plan') }}
                            </button>
                        </div>
                    </form>
                </div>

                <EmptyState
                    v-if="materialPlans.length === 0"
                    :icon="CubeIcon"
                    title="Niciun plan de material"
                    description="Adauga primul plan de necesar de materiale pentru a incepe pregatirea santierului."
                />

                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Etapa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Material</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cantitate</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Pret unitar</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cost estimat</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Furnizor</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Lead-time</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Perioada</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Risc</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in materialPlans" :key="plan.id">
                                <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ plan.material?.name || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.planned_quantity }} {{ plan.material?.unit }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatCurrency(plan.unit_price) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatCurrency(plan.estimated_cost) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.supplier_name || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.lead_time_days ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ formatDate(plan.planned_order_date) }} → {{ formatDate(plan.planned_delivery_date) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="riskTone(plan.risk_level)">
                                        {{ materialRiskLevels[plan.risk_level] || plan.risk_level }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button v-if="!isLocked" type="button" class="text-xs text-red-600 hover:underline" @click="deleteMaterialPlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'equipment'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Adauga plan de utilaj</h3>
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitEquipmentPlan">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="equipmentPlanForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Utilaj *</label>
                            <select v-model="equipmentPlanForm.equipment_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" @change="prefillEquipmentRate">
                                <option value="">— Selecteaza —</option>
                                <option v-for="equipment in equipmentCatalog" :key="equipment.id" :value="equipment.id">{{ equipment.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Cantitate *</label>
                            <input v-model.number="equipmentPlanForm.quantity" type="number" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Tarif orar (RON/ora)</label>
                            <input v-model.number="equipmentPlanForm.hourly_rate" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="din catalog daca lasi gol" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Inceput planificat</label>
                            <input v-model="equipmentPlanForm.usage_start" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Sfarsit planificat</label>
                            <input v-model="equipmentPlanForm.usage_end" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Risc</label>
                            <select v-model="equipmentPlanForm.risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in equipmentRiskLevels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Note</label>
                            <textarea v-model="equipmentPlanForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <button :disabled="equipmentPlanForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ isLocked ? 'Editare blocata (plan aprobat)' : (equipmentPlanForm.processing ? 'Se salveaza...' : 'Adauga plan') }}
                            </button>
                        </div>
                    </form>
                </div>

                <EmptyState
                    v-if="equipmentPlans.length === 0"
                    :icon="TruckIcon"
                    title="Niciun plan de utilaj"
                    description="Adauga primul plan de necesar de utilaje pentru a incepe pregatirea santierului."
                />

                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Etapa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Utilaj</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cantitate</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Perioada</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Zile</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tarif orar</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cost estimat</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Risc</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in equipmentPlans" :key="plan.id">
                                <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">
                                    {{ plan.equipment?.name || '-' }}
                                    <span v-if="plan.reserved_elsewhere_count > 0" class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">
                                        {{ plan.reserved_elsewhere_count }} rezervari suprapuse
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.quantity }}</td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ formatDate(plan.usage_start) }} → {{ formatDate(plan.usage_end) }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.reserved_days }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatCurrency(plan.hourly_rate) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatCurrency(plan.estimated_cost) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="riskTone(plan.risk_level)">
                                        {{ equipmentRiskLevels[plan.risk_level] || plan.risk_level }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button v-if="!isLocked" type="button" class="text-xs text-red-600 hover:underline" @click="deleteEquipmentPlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'logistics'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Adauga element logistic</h3>
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitLogisticsPlan">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="logisticsPlanForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Categorie *</label>
                            <select v-model="logisticsPlanForm.category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in logisticsCategories" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Titlu *</label>
                            <input v-model="logisticsPlanForm.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Poarta acces principala" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Locatie</label>
                            <input v-model="logisticsPlanForm.location_description" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Capacitate / note cantitative</label>
                            <input v-model="logisticsPlanForm.capacity_notes" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex: 2 camioane simultan" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Risc</label>
                            <select v-model="logisticsPlanForm.risk_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in logisticsRiskLevels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Note</label>
                            <textarea v-model="logisticsPlanForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <button :disabled="logisticsPlanForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ isLocked ? 'Editare blocata (plan aprobat)' : (logisticsPlanForm.processing ? 'Se salveaza...' : 'Adauga element') }}
                            </button>
                        </div>
                    </form>
                </div>

                <EmptyState
                    v-if="logisticsPlans.length === 0"
                    :icon="MapPinIcon"
                    title="Niciun element logistic"
                    description="Adauga primul element de logistica (acces, depozitare, zona de siguranta sau restrictie) pentru a incepe pregatirea santierului."
                />

                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Etapa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Categorie</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Titlu</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Locatie</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Capacitate</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Risc</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in logisticsPlans" :key="plan.id">
                                <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="categoryTone(plan.category)">
                                        {{ logisticsCategories[plan.category] || plan.category }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ plan.title }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.location_description || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.capacity_notes || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="riskTone(plan.risk_level)">
                                        {{ logisticsRiskLevels[plan.risk_level] || plan.risk_level }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button v-if="!isLocked" type="button" class="text-xs text-red-600 hover:underline" @click="deleteLogisticsPlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'documents'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Adauga element de conformitate</h3>
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitCompliancePlan">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="compliancePlanForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Tip *</label>
                            <select v-model="compliancePlanForm.item_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in complianceItemTypeLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Subcontractor (optional)</label>
                            <select v-model="compliancePlanForm.contractor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Neselectat —</option>
                                <option v-for="contractor in contractors" :key="contractor.id" :value="contractor.id">{{ contractor.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Titlu *</label>
                            <input v-model="compliancePlanForm.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Autorizatie de construire" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Data scadenta</label>
                            <input v-model="compliancePlanForm.due_date" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Status</label>
                            <select v-model="compliancePlanForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in complianceStatusLabels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Note</label>
                            <textarea v-model="compliancePlanForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <button :disabled="compliancePlanForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ isLocked ? 'Editare blocata (plan aprobat)' : (compliancePlanForm.processing ? 'Se salveaza...' : 'Adauga element') }}
                            </button>
                        </div>
                    </form>
                </div>

                <EmptyState
                    v-if="compliancePlans.length === 0"
                    :icon="DocumentTextIcon"
                    title="Niciun element de conformitate"
                    description="Adauga primul element din checklist-ul de contracte, avize si autorizatii."
                />

                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Etapa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Tip</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Titlu</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Subcontractor</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Scadenta</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in compliancePlans" :key="plan.id">
                                <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ complianceItemTypeLabels[plan.item_type] || plan.item_type }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ plan.title }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ plan.contractor?.name || '-' }}</td>
                                <td class="px-4 py-3 text-gray-600 text-xs">{{ formatDate(plan.due_date) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="complianceStatusTone(plan.status)">
                                        {{ complianceStatusLabels[plan.status] || plan.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button v-if="!isLocked" type="button" class="text-xs text-red-600 hover:underline" @click="deleteCompliancePlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'budget'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Rezumat buget estimat</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500">Cost manopera (auto)</p>
                            <p class="text-sm font-semibold text-gray-800">{{ formatCurrency(budgetSummary.labor_cost) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Cost manopera real: {{ formatCurrency(budgetSummary.labor_cost_actual) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Cost materiale (auto)</p>
                            <p class="text-sm font-semibold text-gray-800">{{ formatCurrency(budgetSummary.materials_cost) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Cost utilaje (auto)</p>
                            <p class="text-sm font-semibold text-gray-800">{{ formatCurrency(budgetSummary.equipment_cost) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Cost manual adaugat</p>
                            <p class="text-sm font-semibold text-gray-800">{{ formatCurrency(budgetSummary.manual_cost) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Total estimat</p>
                            <p class="text-sm font-semibold text-gray-800">{{ formatCurrency(budgetSummary.total_estimated) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Buget alocat proiect</p>
                            <p class="text-sm font-semibold text-gray-800">{{ formatCurrency(budgetSummary.project_budget) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Diferenta</p>
                            <p class="text-sm font-semibold" :class="budgetSummary.difference >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ formatCurrency(budgetSummary.difference) }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">Liniile manuale de tip "Manopera" din tabelul de mai jos nu mai sunt incluse in total - costul se calculeaza acum automat din planul de personal.</p>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Adauga linie bugetara</h3>
                    <form class="grid grid-cols-1 md:grid-cols-3 gap-3" @submit.prevent="submitBudgetPlan">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Etapa (optional)</label>
                            <select v-model="budgetPlanForm.phase_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">Fara etapa specifica</option>
                                <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Categorie *</label>
                            <select v-model="budgetPlanForm.category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option v-for="(label, key) in budgetCategories" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Descriere *</label>
                            <input v-model="budgetPlanForm.description" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Ex: Manopera echipa structura" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Cost estimat (lei) *</label>
                            <input v-model.number="budgetPlanForm.estimated_cost" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div class="md:col-span-3">
                            <label class="block text-xs text-gray-600 mb-1">Note</label>
                            <textarea v-model="budgetPlanForm.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                        </div>
                        <div class="md:col-span-3">
                            <button :disabled="budgetPlanForm.processing || isLocked" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-60">
                                {{ isLocked ? 'Editare blocata (plan aprobat)' : (budgetPlanForm.processing ? 'Se salveaza...' : 'Adauga linie') }}
                            </button>
                        </div>
                    </form>
                </div>

                <EmptyState
                    v-if="budgetPlans.length === 0"
                    :icon="BanknotesIcon"
                    title="Nicio linie bugetara manuala"
                    description="Adauga linii bugetare pentru manopera, subcontractori, logistica, conformitate sau rezerva."
                />

                <div v-else class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Etapa</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Categorie</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Descriere</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Cost</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="plan in budgetPlans" :key="plan.id">
                                <td class="px-4 py-3 text-gray-700">{{ plan.phase?.name || 'Fara etapa' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ budgetCategories[plan.category] || plan.category }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ plan.description }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ formatCurrency(plan.estimated_cost) }}</td>
                                <td class="px-4 py-3">
                                    <button v-if="!isLocked" type="button" class="text-xs text-red-600 hover:underline" @click="deleteBudgetPlan(plan)">Sterge</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-else-if="activeTab === 'ai-tools'" class="space-y-6">
                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Personal necesar</h3>
                    <EmptyState
                        v-if="aiSuggestions.staff.length === 0"
                        :icon="SparklesIcon"
                        title="Nicio sugestie"
                        description="Toate etapele acoperite de catalog au deja un plan de personal."
                    />
                    <ul v-else class="space-y-2">
                        <li v-for="item in aiSuggestions.staff" :key="item.phase_id" class="flex items-start gap-2 text-sm text-gray-700">
                            <SparklesIcon class="h-4 w-4 text-orange-500 shrink-0 mt-0.5" />
                            <span><strong>{{ item.phase_name }}</strong>: {{ item.message }}</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Materiale necesare</h3>
                    <EmptyState
                        v-if="aiSuggestions.materials.length === 0"
                        :icon="SparklesIcon"
                        title="Nicio sugestie"
                        description="Toate etapele acoperite de catalog au deja un plan de materiale."
                    />
                    <ul v-else class="space-y-2">
                        <li v-for="item in aiSuggestions.materials" :key="item.phase_id" class="flex items-start gap-2 text-sm text-gray-700">
                            <SparklesIcon class="h-4 w-4 text-orange-500 shrink-0 mt-0.5" />
                            <span><strong>{{ item.phase_name }}</strong>: {{ item.message }}</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-5">
                    <h3 class="font-semibold text-gray-800 mb-3">Timeline realist</h3>
                    <EmptyState
                        v-if="aiSuggestions.timeline.length === 0"
                        :icon="SparklesIcon"
                        title="Nicio sugestie"
                        description="Durata planificata a etapelor se incadreaza in intervalele tipice."
                    />
                    <ul v-else class="space-y-2">
                        <li v-for="item in aiSuggestions.timeline" :key="item.phase_id" class="flex items-start gap-2 text-sm text-gray-700">
                            <SparklesIcon class="h-4 w-4 text-orange-500 shrink-0 mt-0.5" />
                            <span><strong>{{ item.phase_name }}</strong>: {{ item.message }}</span>
                        </li>
                    </ul>
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
    SparklesIcon,
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
    materialPlans: { type: Array, default: () => [] },
    materials: { type: Array, default: () => [] },
    materialRiskLevels: { type: Object, default: () => ({}) },
    recipes: { type: Array, default: () => [] },
    equipmentPlans: { type: Array, default: () => [] },
    equipmentCatalog: { type: Array, default: () => [] },
    equipmentRiskLevels: { type: Object, default: () => ({}) },
    logisticsPlans: { type: Array, default: () => [] },
    logisticsCategories: { type: Object, default: () => ({}) },
    logisticsRiskLevels: { type: Object, default: () => ({}) },
    compliancePlans: { type: Array, default: () => [] },
    complianceItemTypeLabels: { type: Object, default: () => ({}) },
    complianceStatusLabels: { type: Object, default: () => ({}) },
    budgetPlans: { type: Array, default: () => [] },
    budgetCategories: { type: Object, default: () => ({}) },
    budgetSummary: { type: Object, default: () => ({}) },
    readiness: { type: Object, default: () => ({ score: 0, label: '', domains: [], blockers: [] }) },
    aiSuggestions: { type: Object, default: () => ({ staff: [], materials: [], timeline: [] }) },
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
    { key: 'ai-tools', label: 'AI Tools', icon: SparklesIcon },
];

const activeTab = ref('staff');
const activeTabInfo = computed(() => tabs.find((tab) => tab.key === activeTab.value));

const isLocked = computed(() => !!props.project.plan_approved_at);

function approvePlan() {
    if (!window.confirm('Aprobi planul de organizare? Se vor genera automat sarcini de personal, alocari de subcontractori, comenzi de materiale si rezervari de utilaje, iar editarea planurilor va fi blocata.')) {
        return;
    }

    router.post(route('site-organization.approve', props.project.id), {}, { preserveScroll: true });
}

function unapprovePlan() {
    if (!window.confirm('Anulezi aprobarea planului? Editarea planurilor va fi din nou permisa. Elementele de executie deja generate nu vor fi sterse.')) {
        return;
    }

    router.post(route('site-organization.unapprove', props.project.id), {}, { preserveScroll: true });
}

const staffPlanForm = useForm({
    phase_id: '',
    specialty: '',
    planned_headcount: 1,
    hourly_rate: '',
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

const expandedTimeEntryPlanId = ref(null);

const timeEntryForm = useForm({
    entry_date: '',
    hours_worked: '',
    notes: '',
});

function toggleTimeEntries(plan) {
    expandedTimeEntryPlanId.value = expandedTimeEntryPlanId.value === plan.id ? null : plan.id;
    timeEntryForm.reset();
}

function submitTimeEntry(plan) {
    timeEntryForm.post(route('site-organization.staff-plans.time-entries.store', [props.project.id, plan.id]), {
        preserveScroll: true,
        onSuccess: () => timeEntryForm.reset(),
    });
}

function deleteTimeEntry(plan, entry) {
    router.delete(route('site-organization.staff-plans.time-entries.destroy', [props.project.id, plan.id, entry.id]), {
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

const materialPlanForm = useForm({
    phase_id: '',
    material_id: '',
    planned_quantity: 0,
    unit_price: '',
    supplier_name: '',
    lead_time_days: '',
    planned_order_date: '',
    planned_delivery_date: '',
    risk_level: 'medium',
    notes: '',
});

function prefillMaterialPrice() {
    const material = props.materials.find((item) => item.id === Number(materialPlanForm.material_id));
    materialPlanForm.unit_price = material?.unit_price ?? '';
}

function submitMaterialPlan() {
    materialPlanForm.post(route('site-organization.material-plans.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            materialPlanForm.reset();
            materialPlanForm.risk_level = 'medium';
            materialPlanForm.planned_quantity = 0;
        },
    });
}

function deleteMaterialPlan(plan) {
    router.delete(route('site-organization.material-plans.destroy', [props.project.id, plan.id]), {
        preserveScroll: true,
    });
}

const applyRecipeForm = useForm({
    recipe_id: '',
    phase_id: '',
    work_quantity: '',
});

function submitApplyRecipe() {
    applyRecipeForm.post(route('site-organization.material-plans.apply-recipe', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            applyRecipeForm.reset();
        },
    });
}

const equipmentPlanForm = useForm({
    phase_id: '',
    equipment_id: '',
    quantity: 1,
    hourly_rate: '',
    usage_start: '',
    usage_end: '',
    risk_level: 'medium',
    notes: '',
});

function prefillEquipmentRate() {
    const equipment = props.equipmentCatalog.find((item) => item.id === Number(equipmentPlanForm.equipment_id));
    equipmentPlanForm.hourly_rate = equipment?.cost_per_hour ?? '';
}

function submitEquipmentPlan() {
    equipmentPlanForm.post(route('site-organization.equipment-plans.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            equipmentPlanForm.reset();
            equipmentPlanForm.risk_level = 'medium';
            equipmentPlanForm.quantity = 1;
        },
    });
}

function deleteEquipmentPlan(plan) {
    router.delete(route('site-organization.equipment-plans.destroy', [props.project.id, plan.id]), {
        preserveScroll: true,
    });
}

const logisticsPlanForm = useForm({
    phase_id: '',
    category: 'access',
    title: '',
    location_description: '',
    capacity_notes: '',
    risk_level: 'medium',
    notes: '',
});

function submitLogisticsPlan() {
    logisticsPlanForm.post(route('site-organization.logistics-plans.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            logisticsPlanForm.reset();
            logisticsPlanForm.category = 'access';
            logisticsPlanForm.risk_level = 'medium';
        },
    });
}

function deleteLogisticsPlan(plan) {
    router.delete(route('site-organization.logistics-plans.destroy', [props.project.id, plan.id]), {
        preserveScroll: true,
    });
}

const compliancePlanForm = useForm({
    phase_id: '',
    item_type: 'contract',
    contractor_id: '',
    title: '',
    due_date: '',
    status: 'missing',
    notes: '',
});

function submitCompliancePlan() {
    compliancePlanForm.post(route('site-organization.compliance-plans.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            compliancePlanForm.reset();
            compliancePlanForm.item_type = 'contract';
            compliancePlanForm.status = 'missing';
        },
    });
}

function deleteCompliancePlan(plan) {
    router.delete(route('site-organization.compliance-plans.destroy', [props.project.id, plan.id]), {
        preserveScroll: true,
    });
}

const budgetPlanForm = useForm({
    phase_id: '',
    category: 'labor',
    description: '',
    estimated_cost: 0,
    notes: '',
});

function submitBudgetPlan() {
    budgetPlanForm.post(route('site-organization.budget-plans.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            budgetPlanForm.reset();
            budgetPlanForm.category = 'labor';
            budgetPlanForm.estimated_cost = 0;
        },
    });
}

function deleteBudgetPlan(plan) {
    router.delete(route('site-organization.budget-plans.destroy', [props.project.id, plan.id]), {
        preserveScroll: true,
    });
}

function complianceStatusTone(status) {
    if (status === 'valid') return 'bg-emerald-100 text-emerald-700';
    if (status === 'expiring_soon') return 'bg-amber-100 text-amber-700';
    if (status === 'expired') return 'bg-rose-100 text-rose-700';
    return 'bg-gray-100 text-gray-700';
}

function categoryTone(category) {
    if (category === 'access') return 'bg-blue-100 text-blue-700';
    if (category === 'storage') return 'bg-purple-100 text-purple-700';
    if (category === 'safety_zone') return 'bg-amber-100 text-amber-700';
    if (category === 'restriction') return 'bg-rose-100 text-rose-700';
    return 'bg-gray-100 text-gray-700';
}

function readinessTone(score) {
    if (score >= 80) return { text: 'text-emerald-600', badge: 'bg-emerald-100 text-emerald-700', bar: 'bg-emerald-500' };
    if (score >= 50) return { text: 'text-amber-600', badge: 'bg-amber-100 text-amber-700', bar: 'bg-amber-500' };
    return { text: 'text-rose-600', badge: 'bg-rose-100 text-rose-700', bar: 'bg-rose-500' };
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ro-RO', { maximumFractionDigits: 2 }).format(value || 0) + ' lei';
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
