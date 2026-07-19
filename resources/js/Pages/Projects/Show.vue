<template>
    <AppLayout :title="project.name">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <Link :href="route('projects.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Proiecte</Link>
                <h2 class="text-xl font-semibold text-gray-800">{{ project.name }}</h2>
                <StatusBadge :status="project.status" />
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('site-organization.index', project.id)" class="border border-orange-300 text-orange-700 bg-orange-50 px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-100 transition">
                    Organizare Șantier
                </Link>
                <Link :href="route('daily-briefing.show', project.id)" class="border border-blue-300 text-blue-700 bg-blue-50 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition">
                    Memento Zilnic
                </Link>
                <Link :href="route('projects.edit', project.id)" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Editeaza
                </Link>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800 inline-flex items-center gap-1.5"><Icon :icon="SparklesIcon" size="h-4 w-4 text-orange-500" /> AI Tools (Asistent inteligent)</h3>
                    <p class="text-xs text-gray-500 mt-1">Instrumente de automatizare pentru factura, deviz si buget pe proiect.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <button @click="openAiInvoiceFlow" class="text-left border border-orange-200 bg-orange-50 rounded-xl p-4 hover:bg-orange-100 transition">
                    <div class="text-sm font-semibold text-orange-700 mb-1">Înregistrare facturi prin poză</div>
                    <div class="text-xs text-orange-700/90">Faci o poză -> furnizor, sumă, TVA extrase și transformate în document financiar.</div>
                </button>

                <button @click="openEstimateFlow" class="text-left border border-violet-200 bg-violet-50 rounded-xl p-4 hover:bg-violet-100 transition">
                    <div class="text-sm font-semibold text-violet-700 mb-1">Deviz automat din dimensiuni</div>
                    <div class="text-xs text-violet-700/90">Introduci dimensiuni -> AI generează materiale, manoperă, utilaje, cost și etape WBS.</div>
                </button>

                <button @click="openBudgetAlertFlow" class="text-left border border-blue-200 bg-blue-50 rounded-xl p-4 hover:bg-blue-100 transition">
                    <div class="text-sm font-semibold text-blue-700 mb-1">Alertă depășire buget</div>
                    <div class="text-xs text-blue-700/90">Înregistrezi o achiziție -> AI calculează depășirea pe etapă și impactul pe profit.</div>
                </button>
            </div>

            <div v-if="aiInvoiceState.success" class="mt-3 text-xs bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-2 rounded-lg">
                {{ aiInvoiceState.success }}
            </div>
            <div v-if="aiInvoiceState.error" class="mt-3 text-xs bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg">
                {{ aiInvoiceState.error }}
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800 inline-flex items-center gap-1.5"><Icon :icon="CalendarDaysIcon" size="h-4 w-4 text-orange-500" /> Mini-calendar proiect</h3>
                    <p class="text-xs text-gray-500 mt-1">Agenda operationala pentru {{ todayCalendar.date }}.</p>
                </div>
                <div class="flex items-center gap-3">
                    <select
                        :value="todayCalendar.window || 'today'"
                        @change="changeCalendarWindow($event.target.value)"
                        class="text-xs border border-gray-300 rounded-lg px-2 py-1 text-gray-600"
                    >
                        <option value="today">Azi</option>
                        <option value="7d">7 zile</option>
                        <option value="30d">30 zile</option>
                    </select>
                    <Link :href="route('dashboard')" class="text-xs text-orange-500 hover:underline">Calendar complet in Dashboard →</Link>
                </div>
            </div>

            <div class="mb-3 rounded-lg border p-3" :class="riskCardClass(todayCalendar.risk?.level)">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-wider">Indicator AI</div>
                        <div class="text-sm font-semibold">Risc intarziere: {{ todayCalendar.risk?.score ?? 0 }}%</div>
                    </div>
                    <div class="text-[11px]">
                        blocaje: {{ todayCalendar.risk?.blocked_tasks ?? 0 }} ·
                        etape risc: {{ todayCalendar.risk?.risky_stages ?? 0 }} ·
                        utilaje indisponibile: {{ todayCalendar.risk?.unavailable_equipment ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 text-xs">
                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="font-semibold text-gray-700 mb-1">Etape</div>
                    <div v-if="todayCalendar.stages?.length" class="space-y-1">
                        <a v-for="item in todayCalendar.stages" :key="`st-${item.id}`" :href="item.url" class="block rounded px-1.5 py-1 hover:bg-red-50" :class="itemRowClass('stage', item.criticality)">• {{ item.title }} ({{ item.status }})</a>
                    </div>
                    <div v-else class="text-gray-400">Fara etape azi.</div>
                </div>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="font-semibold text-gray-700 mb-1">Taskuri</div>
                    <div v-if="todayCalendar.tasks?.length" class="space-y-1">
                        <a v-for="item in todayCalendar.tasks" :key="`tk-${item.id}`" :href="item.url" class="block rounded px-1.5 py-1 hover:bg-red-50" :class="itemRowClass('task', item.criticality)">• {{ item.title }} ({{ item.status }})</a>
                    </div>
                    <div v-else class="text-gray-400">Fara taskuri azi.</div>
                </div>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="font-semibold text-gray-700 mb-1">Utilaje</div>
                    <div v-if="todayCalendar.equipment?.length" class="space-y-1">
                        <a v-for="item in todayCalendar.equipment" :key="`eq-${item.id}`" :href="item.url" class="block rounded px-1.5 py-1 hover:bg-blue-50" :class="itemRowClass('equipment', item.criticality)">• {{ item.title }} ({{ item.window }})</a>
                    </div>
                    <div v-else class="text-gray-400">Fara utilaje azi.</div>
                </div>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="font-semibold text-gray-700 mb-1">Subcontractori</div>
                    <div v-if="todayCalendar.subcontractors?.length" class="space-y-1">
                        <a v-for="item in todayCalendar.subcontractors" :key="`sub-${item.id}`" :href="item.url" class="block rounded px-1.5 py-1 hover:bg-purple-50" :class="itemRowClass('subcontractor', item.criticality)">• {{ item.title }} ({{ item.stage }})</a>
                    </div>
                    <div v-else class="text-gray-400">Fara subcontractori azi.</div>
                </div>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="font-semibold text-gray-700 mb-1">Documente</div>
                    <div v-if="todayCalendar.documents?.length" class="space-y-1">
                        <a v-for="item in todayCalendar.documents" :key="`doc-${item.id}`" :href="item.url" class="block rounded px-1.5 py-1 hover:bg-orange-50" :class="itemRowClass('document', item.criticality)">• {{ item.title }} ({{ fmtCur(item.amount) }})</a>
                    </div>
                    <div v-else class="text-gray-400">Fara documente azi.</div>
                </div>

                <div class="border border-gray-200 rounded-lg p-3">
                    <div class="font-semibold text-gray-700 mb-1">Calitate</div>
                    <div v-if="todayCalendar.quality_checks?.length" class="space-y-1">
                        <a v-for="item in todayCalendar.quality_checks" :key="`qc-${item.id}`" :href="item.url" class="block rounded px-1.5 py-1 hover:bg-emerald-50" :class="itemRowClass('quality', item.criticality)">• {{ item.title }} ({{ item.time || '-' }})</a>
                    </div>
                    <div v-else class="text-gray-400">Fara verificari azi.</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Detalii -->
            <div class="xl:col-span-1 bg-white rounded-xl border border-gray-200 p-6 space-y-4 h-fit">
                <h3 class="font-semibold text-gray-500 text-xs uppercase tracking-wider">Detalii proiect</h3>
                <div v-if="project.client" class="flex items-start gap-2 text-sm">
                    <span class="w-5 text-gray-400"><Icon :icon="UserGroupIcon" size="h-4 w-4" /></span>
                    <div><div class="text-xs text-gray-400">Client</div><div class="font-medium">{{ project.client.name }}</div></div>
                </div>
                <div v-if="project.address" class="flex items-start gap-2 text-sm">
                    <span class="w-5 text-gray-400"><Icon :icon="MapPinIcon" size="h-4 w-4" /></span>
                    <div><div class="text-xs text-gray-400">Adresa</div><div>{{ project.address }}</div></div>
                </div>
                <div v-if="project.start_date" class="flex items-start gap-2 text-sm">
                    <span class="w-5 text-gray-400"><Icon :icon="CalendarIcon" size="h-4 w-4" /></span>
                    <div><div class="text-xs text-gray-400">Perioada</div><div>{{ fmt(project.start_date) }} → {{ project.end_date ? fmt(project.end_date) : '?' }}</div></div>
                </div>
                <div v-if="project.total_budget" class="flex items-start gap-2 text-sm">
                    <span class="w-5 text-gray-400"><Icon :icon="BanknotesIcon" size="h-4 w-4" /></span>
                    <div><div class="text-xs text-gray-400">Buget</div><div class="font-semibold">{{ fmtCur(project.total_budget) }}</div></div>
                </div>
                <div v-if="project.description" class="text-sm border-t border-gray-100 pt-3">
                    <div class="text-xs text-gray-400 mb-1">Descriere</div>
                    <p class="text-gray-600">{{ project.description }}</p>
                </div>

                <div class="border-t border-gray-100 pt-3 space-y-3">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Roluri dinamice pe proiect</div>
                        <p class="text-xs text-gray-500">Owner / Contributor / Viewer</p>
                    </div>

                    <div v-if="projectRoleAssignments.length" class="space-y-2">
                        <div v-for="assignment in projectRoleAssignments" :key="assignment.id" class="rounded-lg border border-gray-200 p-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-700 truncate">{{ assignment.user_name }}</div>
                                    <div class="text-[11px] text-gray-400 truncate">{{ assignment.user_email }}</div>
                                </div>
                                <span class="text-[11px] px-2 py-1 rounded-full border"
                                    :class="assignment.role_key === 'owner' ? 'bg-red-50 text-red-700 border-red-200' : assignment.role_key === 'contributor' ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-blue-50 text-blue-700 border-blue-200'">
                                    {{ assignment.role_name }}
                                </span>
                            </div>

                            <div v-if="canManageProjectRoles" class="mt-2 flex items-center gap-2">
                                <select
                                    v-model="projectRoleDrafts[assignment.id]"
                                    @change="updateProjectRole(assignment)"
                                    class="flex-1 border border-gray-300 rounded px-2 py-1 text-xs"
                                >
                                    <option v-for="role in projectRoleOptions" :key="role.key" :value="role.key">{{ role.name }}</option>
                                </select>
                                <button @click="removeProjectRole(assignment)" class="text-xs text-red-600 hover:text-red-800">Revoca</button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Nicio asignare dinamica pe proiect.</div>

                    <div v-if="canManageProjectRoles" class="rounded-lg border border-gray-200 p-3">
                        <div class="text-xs font-medium text-gray-600 mb-2">Acorda rol pe proiect</div>
                        <div class="space-y-2">
                            <select v-model="projectRoleForm.user_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                                <option value="">— Selecteaza utilizator —</option>
                                <option v-for="member in projectMemberCandidates" :key="member.id" :value="member.id">
                                    {{ member.name }} ({{ member.email }})
                                </option>
                            </select>
                            <select v-model="projectRoleForm.role_key" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                                <option value="">— Selecteaza rol —</option>
                                <option v-for="role in projectRoleOptions" :key="role.key" :value="role.key">{{ role.name }}</option>
                            </select>
                            <button
                                @click="assignProjectRole"
                                :disabled="projectRoleForm.processing || !projectRoleForm.user_id || !projectRoleForm.role_key"
                                class="w-full bg-orange-500 text-white px-3 py-1.5 rounded text-xs hover:bg-orange-600 disabled:opacity-50"
                            >
                                {{ projectRoleForm.processing ? 'Se salveaza...' : 'Acorda rol' }}
                            </button>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <div class="text-xs font-medium text-gray-600 mb-2">Acorda rol bulk</div>
                            <div class="space-y-2">
                                <select
                                    v-model="projectRoleBulkForm.user_ids"
                                    multiple
                                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs min-h-[84px]"
                                >
                                    <option v-for="member in projectMemberCandidates" :key="`bulk-${member.id}`" :value="member.id">
                                        {{ member.name }} ({{ member.email }})
                                    </option>
                                </select>
                                <select v-model="projectRoleBulkForm.role_key" class="w-full border border-gray-300 rounded px-2 py-1.5 text-xs">
                                    <option value="">— Selecteaza rol —</option>
                                    <option v-for="role in projectRoleOptions" :key="`bulk-role-${role.key}`" :value="role.key">{{ role.name }}</option>
                                </select>
                                <button
                                    @click="assignProjectRoleBulk"
                                    :disabled="projectRoleBulkForm.processing || !projectRoleBulkForm.role_key || (projectRoleBulkForm.user_ids || []).length === 0"
                                    class="w-full bg-slate-700 text-white px-3 py-1.5 rounded text-xs hover:bg-slate-800 disabled:opacity-50"
                                >
                                    {{ projectRoleBulkForm.processing ? 'Se aplica...' : 'Aplica rol pentru selectie' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Etape -->
            <div class="xl:col-span-2 space-y-4">
                <!-- Lista etape -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 inline-flex items-center gap-1.5"><Icon :icon="CalendarDaysIcon" size="h-4 w-4 text-orange-500" /> Etape proiect</h3>
                        <button @click="toggleAddPhase" class="text-sm bg-orange-500 text-white px-3 py-1.5 rounded-lg hover:bg-orange-600 transition">
                            + Adauga etapa
                        </button>
                    </div>

                    <!-- Formular adaugare/editare etapa -->
                    <div v-if="showAddPhase" class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                        <h4 class="text-sm font-medium text-orange-700 mb-3">{{ editingPhaseId ? 'Editeaza etapa' : 'Etapa noua' }}</h4>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-600 mb-1">Tip etapa</label>
                                <select v-model="phaseForm.type" @change="autoName" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                                    <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-600 mb-1">Contractor responsabil</label>
                                <select v-model="phaseForm.contractor_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                                    <option value="">— Nealocat —</option>
                                    <option v-for="contractor in contractors" :key="contractor.id" :value="contractor.id">{{ contractor.name }} ({{ contractor.type }})</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-600 mb-1">Etapa parinte (sub-etapa)</label>
                                <select v-model="phaseForm.parent_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                                    <option value="">— Fara parinte —</option>
                                    <option v-for="existingPhase in project.phases" :key="existingPhase.id" :value="existingPhase.id">{{ existingPhase.name }}</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs text-gray-600 mb-1">Denumire etapa *</label>
                                <input v-model="phaseForm.name" type="text" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                                <p v-if="phaseForm.errors.name" class="text-red-500 text-xs mt-0.5">{{ phaseForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Data inceput</label>
                                <input v-model="phaseForm.start_date" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">Data sfarsit</label>
                                <input v-model="phaseForm.end_date" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="submitPhaseForm" :disabled="phaseForm.processing" class="bg-orange-500 text-white px-4 py-1.5 rounded text-sm hover:bg-orange-600 disabled:opacity-50 transition">
                                {{ phaseForm.processing ? '...' : (editingPhaseId ? 'Salveaza' : 'Adauga') }}
                            </button>
                            <button @click="cancelPhaseForm" class="border border-gray-300 text-gray-600 px-4 py-1.5 rounded text-sm hover:bg-gray-50">Anuleaza</button>
                        </div>
                    </div>

                    <!-- Fara etape -->
                    <div v-if="project.phases.length === 0 && !showAddPhase" class="text-center py-8 text-gray-400 text-sm">
                        <div class="text-3xl mb-2 flex justify-center text-gray-300"><Icon :icon="CalendarDaysIcon" size="h-8 w-8" /></div>
                        Nicio etapa definita.<br />
                        <button @click="showAddPhase = true" class="text-orange-500 hover:underline mt-1">Adauga prima etapa</button>
                    </div>

                    <!-- Lista etape existente -->
                    <div v-else-if="project.phases.length > 0" class="space-y-2">
                        <div v-for="phase in project.phases" :key="phase.id" :id="`phase-${phase.id}`"
                            class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 bg-gray-50">
                            <!-- Progress bar + info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-sm font-medium text-gray-800 truncate">{{ phase.name }}</span>
                                    <StatusBadge :status="phase.status" />
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-400">
                                    <span v-if="phase.start_date" class="inline-flex items-center gap-1"><Icon :icon="CalendarIcon" size="h-3.5 w-3.5" /> {{ fmt(phase.start_date) }}</span>
                                    <span v-if="phase.end_date">→ {{ fmt(phase.end_date) }}</span>
                                    <span v-if="phase.contractor" class="inline-flex items-center gap-1">· <Icon :icon="UserIcon" size="h-3.5 w-3.5" /> {{ phase.contractor.name }}</span>
                                </div>
                                <!-- Progress bar -->
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-orange-400 h-1.5 rounded-full transition-all" :style="{ width: phase.progress_pct + '%' }"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 w-8 text-right">{{ phase.progress_pct }}%</span>
                                </div>
                            </div>
                            <!-- Editeaza -->
                            <button @click="editPhase(phase)" class="text-xs text-gray-400 hover:text-orange-500 transition shrink-0">Editeaza</button>
                            <!-- Delete -->
                            <button @click="deletePhase(phase)" class="text-gray-300 hover:text-red-400 transition text-lg leading-none">×</button>
                        </div>
                    </div>
                </div>

                <!-- Taskuri placeholder -->
                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 inline-flex items-center gap-1.5"><Icon :icon="CheckCircleIcon" size="h-4 w-4 text-orange-500" /> Taskuri</h3>
                        <Link :href="route('tasks.create', { project_id: project.id })" class="text-xs text-orange-500 hover:underline">+ Task nou</Link>
                    </div>
                    <div v-if="!project.tasks || project.tasks.length === 0" class="text-center py-6 text-gray-400 text-sm">
                        <div class="text-3xl mb-2 flex justify-center text-gray-300"><Icon :icon="CheckCircleIcon" size="h-8 w-8" /></div>
                        Niciun task pentru acest proiect.
                    </div>
                    <div v-else class="space-y-2">
                        <Link
                            v-for="task in project.tasks"
                            :key="task.id"
                            :href="route('tasks.edit', task.id)"
                            class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 border border-gray-100"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-800 truncate">{{ task.title }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ task.phase?.name || 'Fara etapa' }}
                                    <span v-if="task.assignee"> · {{ task.assignee.name }}</span>
                                </div>
                            </div>
                            <StatusBadge :status="task.status === 'in_progress' ? 'active' : task.status === 'done' ? 'completed' : task.status === 'cancelled' ? 'cancelled' : 'draft'" />
                        </Link>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-800 mb-4 inline-flex items-center gap-1.5"><Icon :icon="UserGroupIcon" size="h-4 w-4 text-orange-500" /> Alocari echipe pe etape</h3>
                    <div v-if="project.phases.length === 0" class="text-center py-6 text-gray-400 text-sm">
                        Defineste mai intai etape pentru a putea aloca echipe.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="phase in project.phases" :key="'assign-' + phase.id" class="border border-gray-100 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-medium text-gray-800">{{ phase.name }}</div>
                                <div class="text-xs text-gray-400">{{ phase.assignments?.length || 0 }} alocari</div>
                            </div>

                            <div v-if="phase.assignments && phase.assignments.length > 0" class="space-y-2 mb-3">
                                <div v-for="assignment in phase.assignments" :key="assignment.id" class="flex items-center justify-between text-xs bg-gray-50 border border-gray-100 rounded p-2">
                                    <div class="text-gray-700">
                                        <span class="font-medium">{{ assignment.team?.name || 'Echipa' }}</span>
                                        <span> · necesar {{ assignment.workers_needed }}</span>
                                        <span> · alocati {{ assignment.workers_assigned }}</span>
                                    </div>
                                    <button @click="removeAssignment(phase, assignment)" class="text-red-500 hover:text-red-700">Elimina</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-gray-600 mb-1">Echipa</label>
                                    <select v-model="getAssignmentDraft(phase.id).team_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                                        <option value="">— Selecteaza —</option>
                                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Necesar</label>
                                    <input v-model.number="getAssignmentDraft(phase.id).workers_needed" type="number" min="1" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Alocati</label>
                                    <input v-model.number="getAssignmentDraft(phase.id).workers_assigned" type="number" min="0" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Start</label>
                                    <input v-model="getAssignmentDraft(phase.id).start_date" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">End</label>
                                    <input v-model="getAssignmentDraft(phase.id).end_date" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                            </div>

                            <div class="mt-2">
                                <button @click="addAssignment(phase)" class="bg-orange-500 text-white px-3 py-1.5 rounded text-xs hover:bg-orange-600" :disabled="!getAssignmentDraft(phase.id).team_id">
                                    + Aloca echipa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <h3 class="font-semibold text-gray-800 mb-4 inline-flex items-center gap-1.5"><Icon :icon="TruckIcon" size="h-4 w-4 text-orange-500" /> Utilaje pe etape</h3>
                    <div v-if="project.phases.length === 0" class="text-center py-6 text-gray-400 text-sm">
                        Defineste mai intai etape pentru a putea rezerva utilaje.
                    </div>
                    <div v-else class="space-y-4">
                        <div v-for="phase in project.phases" :key="'equip-' + phase.id" class="border border-gray-100 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-sm font-medium text-gray-800">{{ phase.name }}</div>
                                <div class="text-xs text-gray-400">{{ phase.equipment_reservations?.length || 0 }} rezervari</div>
                            </div>

                            <div v-if="phase.equipment_reservations && phase.equipment_reservations.length > 0" class="space-y-2 mb-3">
                                <div v-for="reservation in phase.equipment_reservations" :key="reservation.id" class="flex items-center justify-between text-xs bg-gray-50 border border-gray-100 rounded p-2">
                                    <div class="text-gray-700">
                                        <span class="font-medium">{{ reservation.equipment?.name || 'Utilaj' }}</span>
                                        <span> · qty {{ reservation.quantity }}</span>
                                        <span v-if="reservation.usage_start && reservation.usage_end"> · {{ fmt(reservation.usage_start) }} → {{ fmt(reservation.usage_end) }}</span>
                                        <span v-if="reservation.equipment?.cost_per_hour"> · {{ fmtCur(Number(reservation.equipment.cost_per_hour) * reservation.quantity) }}/h</span>
                                    </div>
                                    <button @click="removeEquipmentReservation(phase, reservation)" class="text-red-500 hover:text-red-700">Elimina</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-2 items-end">
                                <div class="md:col-span-2">
                                    <label class="block text-xs text-gray-600 mb-1">Utilaj</label>
                                    <select v-model="getEquipmentDraft(phase.id).equipment_id" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm">
                                        <option value="">— Selecteaza —</option>
                                        <option v-for="item in equipment" :key="item.id" :value="item.id">
                                            {{ item.name }} ({{ fmtCur(item.cost_per_hour) }}/ora)
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Cantitate</label>
                                    <input v-model.number="getEquipmentDraft(phase.id).quantity" type="number" min="1" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">Start</label>
                                    <input v-model="getEquipmentDraft(phase.id).usage_start" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">End</label>
                                    <input v-model="getEquipmentDraft(phase.id).usage_end" type="date" class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm" />
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">Cost estimat interval</div>
                                    <div class="text-sm font-semibold text-gray-800">
                                        {{ estimateReservationCost(getEquipmentDraft(phase.id)) === null ? '-' : fmtCur(estimateReservationCost(getEquipmentDraft(phase.id))) }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-2">
                                <button @click="addEquipmentReservation(phase)" class="bg-orange-500 text-white px-3 py-1.5 rounded text-xs hover:bg-orange-600" :disabled="!getEquipmentDraft(phase.id).equipment_id">
                                    + Rezerva utilaj
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-800 inline-flex items-center gap-1.5"><Icon :icon="WrenchScrewdriverIcon" size="h-4 w-4 text-orange-500" /> Defecte (Snag)</h3>
                        <Link :href="route('defects.create', { project_id: project.id })" class="text-xs text-orange-500 hover:underline">+ Defect nou</Link>
                    </div>
                    <div v-if="!project.defects || project.defects.length === 0" class="text-center py-6 text-gray-400 text-sm">
                        <div class="text-3xl mb-2 flex justify-center text-gray-300"><Icon :icon="WrenchScrewdriverIcon" size="h-8 w-8" /></div>
                        Niciun defect raportat pentru acest proiect.
                    </div>
                    <div v-else class="space-y-2">
                        <Link
                            v-for="defect in project.defects"
                            :key="defect.id"
                            :href="route('defects.edit', defect.id)"
                            class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 border border-gray-100"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-800 truncate">{{ defect.title }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ defect.phase?.name || 'Fara etapa' }}
                                    <span v-if="defect.assignee"> · {{ defect.assignee.name }}</span>
                                </div>
                            </div>
                            <StatusBadge :status="defect.status === 'in_progress' ? 'active' : defect.status === 'resolved' ? 'completed' : defect.status === 'rejected' ? 'cancelled' : 'draft'" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showAiInvoiceFlow" class="fixed inset-0 z-50 bg-black/40 p-4 overflow-y-auto">
            <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-5 mt-8">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Factură prin poză -> document financiar</h3>
                        <p class="text-xs text-gray-500 mt-1">Upload, extragere draft AI, verificare manuală, apoi confirmare în proiect.</p>
                    </div>
                    <button @click="closeAiInvoiceFlow" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Etapa asociată *</label>
                        <select v-model="aiInvoiceForm.stage_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="">— Selecteaza etapa —</option>
                            <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Poza/PDF factura *</label>
                        <input ref="aiInvoiceFileInput" type="file" accept="image/*,.pdf" @change="onAiInvoiceFileChange" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <button @click="extractAiInvoiceDraft" :disabled="aiInvoiceState.extracting" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-orange-600 disabled:opacity-50">
                            {{ aiInvoiceState.extracting ? 'Se extrage...' : 'Extrage date (AI)' }}
                        </button>
                        <button @click="closeAiInvoiceFlow" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Anuleaza</button>
                    </div>
                </div>

                <div v-if="aiInvoiceState.draftLoaded" class="mt-5 border-t border-gray-100 pt-4">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Verificare draft extras</h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Furnizor</label>
                            <input v-model="aiInvoiceForm.supplier_name" type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Titlu document</label>
                            <input v-model="aiInvoiceForm.title" type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Numar factura</label>
                            <input v-model="aiInvoiceForm.invoice_number" type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Suma totală (RON)</label>
                            <input v-model="aiInvoiceForm.amount" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">TVA (RON)</label>
                            <input v-model="aiInvoiceForm.vat_amount" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Data emiterii</label>
                            <input v-model="aiInvoiceForm.issued_at" type="date" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Status plată</label>
                            <select v-model="aiInvoiceForm.payment_status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="unpaid">Neplatit</option>
                                <option value="partial">Plata partiala</option>
                                <option value="paid">Platit</option>
                                <option value="cancelled">Anulat</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-600 mb-1">Observatii</label>
                            <textarea v-model="aiInvoiceForm.notes" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 text-sm"></textarea>
                        </div>
                    </div>

                    <div class="mt-3 text-xs text-gray-500">
                        Fisier draft: {{ aiInvoiceForm.file_name }}
                    </div>

                    <div class="mt-4 flex gap-2">
                        <button @click="commitAiInvoiceDraft" :disabled="aiInvoiceState.saving" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-emerald-700 disabled:opacity-50">
                            {{ aiInvoiceState.saving ? 'Se salveaza...' : 'Confirmă și creează document' }}
                        </button>
                    </div>
                </div>

                <div v-if="aiInvoiceState.error" class="mt-3 text-xs bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg">
                    {{ aiInvoiceState.error }}
                </div>
            </div>
        </div>

        <div v-if="showBudgetAlertFlow" class="fixed inset-0 z-50 bg-black/40 p-4 overflow-y-auto">
            <div class="max-w-2xl mx-auto bg-white rounded-xl border border-gray-200 p-5 mt-8">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Alertă depășire buget pe etapă</h3>
                        <p class="text-xs text-gray-500 mt-1">Simulează o achiziție și vezi instant impactul financiar pe etapă și pe proiect.</p>
                    </div>
                    <button @click="closeBudgetAlertFlow" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Etapa *</label>
                        <select v-model="budgetAlertForm.stage_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="">— Selecteaza etapa —</option>
                            <option v-for="phase in project.phases" :key="phase.id" :value="phase.id">{{ phase.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Sursa achiziției</label>
                        <select v-model="budgetAlertForm.purchase_source" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="other">Alta cheltuiala</option>
                            <option value="document">Document financiar</option>
                            <option value="material_invoice">Factura materiale</option>
                            <option value="equipment">Utilaje</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Valoare achiziție (RON) *</label>
                        <input v-model="budgetAlertForm.purchase_amount" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="ex: 12500" />
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <button @click="runBudgetAlert" :disabled="budgetAlertState.loading" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                        {{ budgetAlertState.loading ? 'Se calculeaza...' : 'Calculeaza impactul' }}
                    </button>
                    <button @click="closeBudgetAlertFlow" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Inchide</button>
                </div>

                <div v-if="budgetAlertState.error" class="mt-3 text-xs bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg">
                    {{ budgetAlertState.error }}
                </div>

                <div v-if="budgetAlertState.hasResult" class="mt-4 border-t border-gray-100 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500">Depasire buget etapa</div>
                            <div class="text-lg font-semibold" :class="(budgetAlertState.result.stage_overrun_amount || 0) > 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ fmtCur(budgetAlertState.result.stage_overrun_amount || 0) }}
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500">Procent depasire etapa</div>
                            <div class="text-lg font-semibold" :class="(budgetAlertState.result.stage_overrun_pct || 0) > 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ budgetAlertState.result.stage_overrun_pct || 0 }}%
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500">Impact pe profit (proxy)</div>
                            <div class="text-lg font-semibold" :class="(budgetAlertState.result.profit_impact_amount || 0) < 0 ? 'text-red-600' : 'text-emerald-600'">
                                {{ fmtCur(budgetAlertState.result.profit_impact_amount || 0) }}
                            </div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500">Cost estimat etapa dupa achizitie</div>
                            <div class="text-lg font-semibold text-gray-800">
                                {{ fmtCur(budgetAlertState.result.predicted_stage_cost || 0) }}
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-lg p-3 text-sm" :class="recommendationTone(budgetAlertState.result)">
                        <div class="font-semibold mb-1">Recomandare AI</div>
                        <div>{{ budgetAlertState.result.recommendation }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showEstimateFlow" class="fixed inset-0 z-50 bg-black/40 p-4 overflow-y-auto">
            <div class="max-w-3xl mx-auto bg-white rounded-xl border border-gray-200 p-5 mt-8">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800">Deviz automat din dimensiuni</h3>
                        <p class="text-xs text-gray-500 mt-1">Alege operatia de lucru si cantitatea, iar devizul se calculeaza din reteta de consum a sablonului.</p>
                    </div>
                    <button @click="closeEstimateFlow" class="text-gray-400 hover:text-gray-600 text-xl leading-none">×</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Operatie de lucru (sablon) *</label>
                        <select v-model="estimateForm.task_template_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="">Alege un sablon...</option>
                            <option v-for="template in props.taskTemplates" :key="template.id" :value="template.id">
                                {{ template.title }}{{ template.recipe ? '' : ' (fara reteta)' }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Cantitate lucrare {{ selectedTemplate?.recipe ? `(${selectedTemplate.recipe.unit})` : '' }} *</label>
                        <input v-model="estimateForm.measure_value" type="number" min="0.1" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="ex: 120" />
                    </div>

                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Complexitate</label>
                        <select v-model="estimateForm.complexity" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="low">Redusa</option>
                            <option value="medium">Medie</option>
                            <option value="high">Ridicata</option>
                        </select>
                    </div>

                </div>

                <div v-if="selectedTemplate && !selectedTemplate.recipe" class="mt-3 text-xs bg-amber-50 border border-amber-200 text-amber-800 px-3 py-2 rounded-lg flex items-center justify-between gap-3">
                    <span>Acest sablon nu are inca o reteta de consum, deci nu se poate calcula un deviz corect. Creeaza mai intai o reteta.</span>
                    <a :href="route('recipes.create', { subject_type: 'task_template', subject_id: selectedTemplate.id })" target="_blank" class="shrink-0 bg-amber-600 text-white px-3 py-1.5 rounded-lg hover:bg-amber-700">
                        + Reteta pentru sablon
                    </a>
                </div>

                <div class="mt-3 flex gap-2">
                    <button @click="generateEstimate" :disabled="estimateState.loading || !selectedTemplate?.recipe" class="bg-violet-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-violet-700 disabled:opacity-50">
                        {{ estimateState.loading ? 'Se genereaza...' : 'Genereaza deviz' }}
                    </button>
                    <button @click="closeEstimateFlow" class="border border-gray-300 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-50">Inchide</button>
                </div>

                <div v-if="estimateState.error" class="mt-3 text-xs bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg">
                    {{ estimateState.error }}
                </div>

                <div v-if="estimateState.hasResult" class="mt-4 border-t border-gray-100 pt-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500">Materiale</div>
                            <div class="text-lg font-semibold text-gray-800">{{ fmtCur(estimateState.result.totals.materials_cost || 0) }}</div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500">Manopera</div>
                            <div class="text-lg font-semibold text-gray-800">{{ fmtCur(estimateState.result.totals.labor_cost || 0) }}</div>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-3">
                            <div class="text-xs text-gray-500">Utilaje</div>
                            <div class="text-lg font-semibold text-gray-800">{{ fmtCur(estimateState.result.totals.equipment_cost || 0) }}</div>
                        </div>
                        <div class="bg-violet-50 border border-violet-200 rounded-lg p-3">
                            <div class="text-xs text-violet-700">Total net estimat</div>
                            <div class="text-lg font-semibold text-violet-700">{{ fmtCur(estimateState.result.totals.total_net || 0) }}</div>
                        </div>
                    </div>

                    <div v-if="estimateState.result.timing?.total_hours > 0" class="bg-sky-50 border border-sky-200 rounded-lg p-3 mb-3 text-xs text-sky-800">
                        <span class="font-semibold">Durata estimata:</span>
                        {{ estimateState.result.timing.execution_hours }}h executie
                        <template v-if="estimateState.result.timing.drying_hours > 0"> + {{ estimateState.result.timing.drying_hours }}h uscare</template>
                        <template v-if="estimateState.result.timing.curing_hours > 0"> + {{ estimateState.result.timing.curing_hours }}h intarire</template>
                        = <span class="font-semibold">{{ estimateState.result.timing.total_hours }}h total</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-sm font-semibold text-gray-800 mb-2">Materiale estimate</div>
                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                <div v-for="item in estimateState.result.materials" :key="item.name" class="text-xs text-gray-700 flex items-center justify-between gap-3">
                                    <span>{{ item.name }} ({{ item.quantity }} {{ item.unit }})</span>
                                    <span class="font-medium">{{ fmtCur(item.estimated_cost) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-sm font-semibold text-gray-800 mb-2">Etape WBS propuse</div>
                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                <div v-for="(stage, idx) in estimateState.result.wbs_stages" :key="stage.name + idx" class="text-xs text-gray-700">
                                    {{ idx + 1 }}. {{ stage.name }}
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-sm font-semibold text-gray-800 mb-2">Manopera estimata</div>
                            <div v-if="!estimateState.result.labor.lines.length" class="text-xs text-gray-400">
                                Nicio manopera definita pe reteta.
                            </div>
                            <div v-else class="space-y-1 max-h-48 overflow-y-auto">
                                <div v-for="(item, idx) in estimateState.result.labor.lines" :key="item.role + idx" class="text-xs text-gray-700 flex items-center justify-between gap-3">
                                    <span>{{ item.role }} ({{ item.hours }}h × {{ item.hourly_rate }} RON)</span>
                                    <span class="font-medium">{{ fmtCur(item.estimated_cost) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="text-sm font-semibold text-gray-800 mb-2">Utilaje estimate</div>
                            <div v-if="!estimateState.result.equipment.lines.length" class="text-xs text-gray-400">
                                Niciun utilaj definit pe reteta.
                            </div>
                            <div v-else class="space-y-1 max-h-48 overflow-y-auto">
                                <div v-for="(item, idx) in estimateState.result.equipment.lines" :key="item.equipment_id + idx" class="text-xs text-gray-700 flex items-center justify-between gap-3">
                                    <span>{{ item.name }} ({{ item.hours }}h × {{ item.hourly_rate }} RON)</span>
                                    <span class="font-medium">{{ fmtCur(item.estimated_cost) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Titlu ofertă draft</label>
                            <input v-model="estimateCommitForm.title" type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-1">Note ofertă</label>
                            <input v-model="estimateCommitForm.notes" type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Deviz AI pentru proiect" />
                        </div>
                    </div>

                    <button @click="commitEstimate" :disabled="estimateState.saving" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-emerald-700 disabled:opacity-50">
                        {{ estimateState.saving ? 'Se salveaza...' : 'Creeaza oferta draft + etape WBS' }}
                    </button>

                    <button v-if="estimateState.quoteId" @click="downloadEstimatePdf" class="ml-2 border border-indigo-200 text-indigo-700 px-4 py-2 rounded-lg text-sm hover:bg-indigo-50">
                        Descarca PDF oferta
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import axios from 'axios';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';
import {
    SparklesIcon,
    CalendarDaysIcon,
    CalendarIcon,
    UserGroupIcon,
    MapPinIcon,
    BanknotesIcon,
    UserIcon,
    CheckCircleIcon,
    TruckIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project:    Object,
    typeLabels: Object,
    teams:      { type: Array, default: () => [] },
    contractors:{ type: Array, default: () => [] },
    equipment:  { type: Array, default: () => [] },
    taskTemplates: { type: Array, default: () => [] },
    projectRoleOptions: { type: Array, default: () => [] },
    projectRoleAssignments: { type: Array, default: () => [] },
    projectMemberCandidates: { type: Array, default: () => [] },
    canManageProjectRoles: { type: Boolean, default: false },
    todayCalendar: { type: Object, default: () => ({ date: '', window: 'today', stages: [], tasks: [], equipment: [], subcontractors: [], documents: [], quality_checks: [] }) },
});

const showAddPhase = ref(false);
const editingPhaseId = ref(null);
const showAiInvoiceFlow = ref(false);
const showBudgetAlertFlow = ref(false);
const showEstimateFlow = ref(false);
const aiInvoiceFileInput = ref(null);

const aiInvoiceState = reactive({
    extracting: false,
    saving: false,
    error: '',
    success: '',
    draftLoaded: false,
});

const budgetAlertState = reactive({
    loading: false,
    error: '',
    success: '',
    hasResult: false,
    result: null,
});

const estimateState = reactive({
    loading: false,
    saving: false,
    error: '',
    success: '',
    hasResult: false,
    result: null,
    quoteId: null,
});

const aiInvoiceForm = reactive({
    stage_id: '',
    supplier_name: '',
    invoice_number: '',
    amount: '',
    vat_amount: '',
    issued_at: new Date().toISOString().slice(0, 10),
    payment_status: 'unpaid',
    title: '',
    notes: '',
    temp_file_path: '',
    file_name: '',
    attachment: null,
});

const budgetAlertForm = reactive({
    stage_id: '',
    purchase_amount: '',
    purchase_source: 'other',
});

const estimateForm = reactive({
    task_template_id: '',
    measure_value: '',
    complexity: 'medium',
});

const estimateCommitForm = reactive({
    title: '',
    notes: 'Deviz generat automat din modul AI Tools.',
});

const selectedTemplate = computed(() => {
    return props.taskTemplates.find((template) => template.id === Number(estimateForm.task_template_id)) || null;
});

const phaseForm = useForm({
    name:         '',
    type:         'custom',
    status:       'pending',
    start_date:   '',
    end_date:     '',
    duration_days: null,
    progress_pct: 0,
    contractor_id: '',
    parent_id: '',
    notes:        '',
});

const assignmentDrafts = reactive({});
const equipmentDrafts = reactive({});
const projectRoleDrafts = reactive(
    Object.fromEntries((props.projectRoleAssignments || []).map((assignment) => [assignment.id, assignment.role_key]))
);

const projectRoleForm = useForm({
    user_id: '',
    role_key: '',
});

const projectRoleBulkForm = useForm({
    user_ids: [],
    role_key: '',
});

function getAssignmentDraft(phaseId) {
    if (!assignmentDrafts[phaseId]) {
        assignmentDrafts[phaseId] = {
            team_id: '',
            workers_needed: 1,
            workers_assigned: 0,
            start_date: '',
            end_date: '',
            notes: '',
        };
    }

    return assignmentDrafts[phaseId];
}

function getEquipmentDraft(phaseId) {
    if (!equipmentDrafts[phaseId]) {
        equipmentDrafts[phaseId] = {
            equipment_id: '',
            quantity: 1,
            usage_start: '',
            usage_end: '',
            notes: '',
        };
    }

    return equipmentDrafts[phaseId];
}

function autoName() {
    if (props.typeLabels[phaseForm.type]) {
        phaseForm.name = props.typeLabels[phaseForm.type];
    }
}

function openAiInvoiceFlow() {
    showAiInvoiceFlow.value = true;
    aiInvoiceState.error = '';
    aiInvoiceState.success = '';
}

function resetAiInvoiceFlow() {
    aiInvoiceState.error = '';
    aiInvoiceState.success = '';
    aiInvoiceState.extracting = false;
    aiInvoiceState.saving = false;
    aiInvoiceState.draftLoaded = false;

    aiInvoiceForm.stage_id = '';
    aiInvoiceForm.supplier_name = '';
    aiInvoiceForm.invoice_number = '';
    aiInvoiceForm.amount = '';
    aiInvoiceForm.vat_amount = '';
    aiInvoiceForm.issued_at = new Date().toISOString().slice(0, 10);
    aiInvoiceForm.payment_status = 'unpaid';
    aiInvoiceForm.title = '';
    aiInvoiceForm.notes = '';
    aiInvoiceForm.temp_file_path = '';
    aiInvoiceForm.file_name = '';
    aiInvoiceForm.attachment = null;

    if (aiInvoiceFileInput.value) {
        aiInvoiceFileInput.value.value = '';
    }
}

function closeAiInvoiceFlow() {
    showAiInvoiceFlow.value = false;
    resetAiInvoiceFlow();
}

function openBudgetAlertFlow() {
    showBudgetAlertFlow.value = true;
    budgetAlertState.error = '';
    budgetAlertState.success = '';
}

function openEstimateFlow() {
    showEstimateFlow.value = true;
    estimateState.error = '';
    estimateState.success = '';
}

function closeEstimateFlow() {
    showEstimateFlow.value = false;
    estimateState.loading = false;
    estimateState.saving = false;
    estimateState.error = '';
    estimateState.success = '';
    estimateState.hasResult = false;
    estimateState.result = null;
    estimateState.quoteId = null;
    estimateForm.task_template_id = '';
    estimateForm.measure_value = '';
    estimateForm.complexity = 'medium';
    estimateCommitForm.title = '';
    estimateCommitForm.notes = 'Deviz generat automat din modul AI Tools.';
}

async function generateEstimate() {
    estimateState.error = '';
    estimateState.success = '';

    if (!estimateForm.task_template_id) {
        estimateState.error = 'Alege operatia de lucru pentru care generezi devizul.';
        return;
    }

    if (!selectedTemplate.value?.recipe) {
        estimateState.error = 'Sablonul ales nu are inca o reteta de consum.';
        return;
    }

    if (!estimateForm.measure_value || Number(estimateForm.measure_value) <= 0) {
        estimateState.error = 'Introdu o cantitate de lucrare valida pentru generarea devizului.';
        return;
    }

    estimateState.loading = true;

    try {
        const response = await axios.post(route('projects.ai.estimate.generate', props.project.id), {
            task_template_id: Number(estimateForm.task_template_id),
            measure_value: Number(estimateForm.measure_value),
            complexity: estimateForm.complexity,
        });

        estimateState.result = response.data?.estimate || null;
        estimateState.hasResult = !!estimateState.result;
        estimateState.success = response.data?.message || 'Deviz generat cu succes.';
        estimateState.quoteId = null;

        estimateCommitForm.title = `Deviz AI - ${props.project.name} - ${selectedTemplate.value.title}`;
    } catch (error) {
        estimateState.error = error?.response?.data?.message || 'Nu am putut genera devizul.';
    } finally {
        estimateState.loading = false;
    }
}

async function commitEstimate() {
    estimateState.error = '';
    estimateState.success = '';

    if (!estimateState.hasResult || !estimateState.result) {
        estimateState.error = 'Genereaza mai intai un deviz.';
        return;
    }

    estimateState.saving = true;

    try {
        const response = await axios.post(route('projects.ai.estimate.commit', props.project.id), {
            title: estimateCommitForm.title || `Deviz AI - ${props.project.name}`,
            total_net: estimateState.result.totals?.total_net || 0,
            wbs_stages: estimateState.result.wbs_stages || [],
            notes: estimateCommitForm.notes,
            estimate_details: {
                task_template_id: estimateState.result.task_template_id,
                task_template_title: estimateState.result.task_template_title,
                recipe_unit: estimateState.result.recipe_unit,
                measure_value: Number(estimateForm.measure_value || 0),
                complexity: estimateForm.complexity,
                materials: estimateState.result.materials || [],
                labor: estimateState.result.labor || {},
                equipment: estimateState.result.equipment || {},
                timing: estimateState.result.timing || {},
                totals: estimateState.result.totals || {},
            },
        });

        estimateState.success = response.data?.message || 'Deviz salvat cu succes.';
        estimateState.quoteId = response.data?.quote_id || null;

        router.reload({ preserveScroll: true });
    } catch (error) {
        estimateState.error = error?.response?.data?.message || 'Nu am putut salva devizul.';
    } finally {
        estimateState.saving = false;
    }
}

function downloadEstimatePdf() {
    if (!estimateState.quoteId) {
        return;
    }

    window.open(route('quotes.pdf', estimateState.quoteId), '_blank');
}

function closeBudgetAlertFlow() {
    showBudgetAlertFlow.value = false;
    budgetAlertState.error = '';
    budgetAlertState.success = '';
    budgetAlertState.loading = false;
    budgetAlertState.hasResult = false;
    budgetAlertState.result = null;

    budgetAlertForm.stage_id = '';
    budgetAlertForm.purchase_amount = '';
    budgetAlertForm.purchase_source = 'other';
}

async function runBudgetAlert() {
    budgetAlertState.error = '';
    budgetAlertState.success = '';

    if (!budgetAlertForm.stage_id) {
        budgetAlertState.error = 'Selecteaza etapa pentru evaluarea impactului.';
        return;
    }

    if (!budgetAlertForm.purchase_amount || Number(budgetAlertForm.purchase_amount) <= 0) {
        budgetAlertState.error = 'Introdu o valoare valida pentru achizitie.';
        return;
    }

    budgetAlertState.loading = true;

    try {
        const response = await axios.post(route('projects.ai.budget-alert', props.project.id), {
            stage_id: budgetAlertForm.stage_id,
            purchase_amount: Number(budgetAlertForm.purchase_amount),
            purchase_source: budgetAlertForm.purchase_source,
        });

        budgetAlertState.result = response.data?.alert || null;
        budgetAlertState.hasResult = !!budgetAlertState.result;
        budgetAlertState.success = response.data?.message || 'Alerta buget a fost calculata.';
    } catch (error) {
        budgetAlertState.error = error?.response?.data?.message || 'Nu am putut calcula alerta de buget.';
    } finally {
        budgetAlertState.loading = false;
    }
}

function recommendationTone(alert) {
    if (!alert) {
        return 'bg-gray-50 border-gray-200 text-gray-700';
    }

    if ((alert.project_overrun_amount || 0) > 0 || (alert.stage_overrun_pct || 0) >= 10) {
        return 'bg-red-50 border-red-200 text-red-700';
    }

    if ((alert.stage_overrun_pct || 0) >= 5) {
        return 'bg-amber-50 border-amber-200 text-amber-700';
    }

    return 'bg-emerald-50 border-emerald-200 text-emerald-700';
}

function onAiInvoiceFileChange(event) {
    const file = event.target.files?.[0] || null;
    aiInvoiceForm.attachment = file;
    aiInvoiceState.error = '';
}

async function extractAiInvoiceDraft() {
    aiInvoiceState.error = '';
    aiInvoiceState.success = '';

    if (!aiInvoiceForm.stage_id) {
        aiInvoiceState.error = 'Selecteaza etapa asociata inainte de incarcare.';
        return;
    }

    if (!aiInvoiceForm.attachment) {
        aiInvoiceState.error = 'Incarca o poza/PDF de factura pentru extractie.';
        return;
    }

    aiInvoiceState.extracting = true;

    try {
        const payload = new FormData();
        payload.append('stage_id', String(aiInvoiceForm.stage_id));
        payload.append('attachment', aiInvoiceForm.attachment);

        const response = await axios.post(route('projects.ai.invoice.extract', props.project.id), payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        const draft = response.data?.draft || {};

        aiInvoiceForm.temp_file_path = draft.temp_file_path || '';
        aiInvoiceForm.file_name = draft.file_name || '';
        aiInvoiceForm.supplier_name = draft.supplier_name || '';
        aiInvoiceForm.invoice_number = draft.invoice_number || '';
        aiInvoiceForm.amount = draft.amount || '';
        aiInvoiceForm.vat_amount = draft.vat_amount || '';
        aiInvoiceForm.issued_at = draft.issued_at || new Date().toISOString().slice(0, 10);
        aiInvoiceForm.payment_status = draft.payment_status || 'unpaid';
        aiInvoiceForm.title = draft.title || '';
        aiInvoiceForm.notes = draft.notes || '';

        aiInvoiceState.draftLoaded = true;
        aiInvoiceState.success = response.data?.message || 'Draft extras cu succes. Verifica datele.';
    } catch (error) {
        aiInvoiceState.error = error?.response?.data?.message || 'Nu am putut extrage datele facturii.';
    } finally {
        aiInvoiceState.extracting = false;
    }
}

async function commitAiInvoiceDraft() {
    aiInvoiceState.error = '';
    aiInvoiceState.success = '';

    if (!aiInvoiceState.draftLoaded || !aiInvoiceForm.temp_file_path) {
        aiInvoiceState.error = 'Extrage mai intai draft-ul facturii.';
        return;
    }

    aiInvoiceState.saving = true;

    try {
        const response = await axios.post(route('projects.ai.invoice.commit', props.project.id), {
            stage_id: aiInvoiceForm.stage_id,
            temp_file_path: aiInvoiceForm.temp_file_path,
            supplier_name: aiInvoiceForm.supplier_name,
            invoice_number: aiInvoiceForm.invoice_number || null,
            amount: aiInvoiceForm.amount,
            vat_amount: aiInvoiceForm.vat_amount || null,
            issued_at: aiInvoiceForm.issued_at,
            payment_status: aiInvoiceForm.payment_status,
            title: aiInvoiceForm.title,
            notes: aiInvoiceForm.notes,
        });

        aiInvoiceState.success = response.data?.message || 'Factura a fost inregistrata.';

        closeAiInvoiceFlow();
        router.reload({ preserveScroll: true });
    } catch (error) {
        aiInvoiceState.error = error?.response?.data?.message || 'Nu am putut salva factura.';
    } finally {
        aiInvoiceState.saving = false;
    }
}

function submitPhaseForm() {
    const onSuccess = () => {
        showAddPhase.value = false;
        editingPhaseId.value = null;
        phaseForm.reset();
    };

    if (editingPhaseId.value) {
        phaseForm.put(route('phases.update', [props.project.id, editingPhaseId.value]), { onSuccess });
    } else {
        phaseForm.post(route('phases.store', props.project.id), { onSuccess });
    }
}

function editPhase(phase) {
    editingPhaseId.value = phase.id;
    phaseForm.name = phase.name;
    phaseForm.type = phase.type;
    phaseForm.status = phase.status;
    phaseForm.start_date = phase.start_date ?? '';
    phaseForm.end_date = phase.end_date ?? '';
    phaseForm.duration_days = phase.duration_days ?? null;
    phaseForm.progress_pct = phase.progress_pct ?? 0;
    phaseForm.contractor_id = phase.contractor_id ?? '';
    phaseForm.parent_id = phase.parent_id ?? '';
    phaseForm.notes = phase.notes ?? '';
    showAddPhase.value = true;
}

function cancelPhaseForm() {
    showAddPhase.value = false;
    editingPhaseId.value = null;
    phaseForm.reset();
}

function toggleAddPhase() {
    if (showAddPhase.value) {
        cancelPhaseForm();
    } else {
        editingPhaseId.value = null;
        phaseForm.reset();
        showAddPhase.value = true;
    }
}

function deletePhase(phase) {
    if (confirm('Stergi etapa "' + phase.name + '"?')) {
        router.delete(route('phases.destroy', [props.project.id, phase.id]));
    }
}

function addAssignment(phase) {
    const draft = getAssignmentDraft(phase.id);

    router.post(route('phase-assignments.store', [props.project.id, phase.id]), {
        team_id: draft.team_id,
        workers_needed: draft.workers_needed,
        workers_assigned: draft.workers_assigned,
        start_date: draft.start_date || null,
        end_date: draft.end_date || null,
        notes: draft.notes || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            assignmentDrafts[phase.id] = {
                team_id: '',
                workers_needed: 1,
                workers_assigned: 0,
                start_date: '',
                end_date: '',
                notes: '',
            };
        },
    });
}

function removeAssignment(phase, assignment) {
    if (confirm('Elimini alocarea pentru echipa "' + (assignment.team?.name || '') + '"?')) {
        router.delete(route('phase-assignments.destroy', [props.project.id, phase.id, assignment.id]), {
            preserveScroll: true,
        });
    }
}

function addEquipmentReservation(phase) {
    const draft = getEquipmentDraft(phase.id);

    router.post(route('stage-equipment.store', [props.project.id, phase.id]), {
        equipment_id: draft.equipment_id,
        quantity: draft.quantity,
        usage_start: draft.usage_start || null,
        usage_end: draft.usage_end || null,
        notes: draft.notes || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            equipmentDrafts[phase.id] = {
                equipment_id: '',
                quantity: 1,
                usage_start: '',
                usage_end: '',
                notes: '',
            };
        },
    });
}

function assignProjectRole() {
    projectRoleForm.post(route('projects.roles.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            projectRoleForm.reset();
        },
    });
}

function updateProjectRole(assignment) {
    const roleKey = projectRoleDrafts[assignment.id];
    if (!roleKey) {
        return;
    }

    router.patch(route('projects.roles.update', [props.project.id, assignment.id]), {
        role_key: roleKey,
    }, {
        preserveScroll: true,
    });
}

function assignProjectRoleBulk() {
    projectRoleBulkForm.post(route('projects.roles.bulk.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            projectRoleBulkForm.reset();
            projectRoleBulkForm.user_ids = [];
        },
    });
}

function removeProjectRole(assignment) {
    if (confirm('Revoci rolul pe proiect pentru ' + (assignment.user_name || 'acest utilizator') + '?')) {
        router.delete(route('projects.roles.destroy', [props.project.id, assignment.id]), {
            preserveScroll: true,
        });
    }
}

function removeEquipmentReservation(phase, reservation) {
    if (confirm('Elimini rezervarea utilajului "' + (reservation.equipment?.name || '') + '"?')) {
        router.delete(route('stage-equipment.destroy', [props.project.id, phase.id, reservation.id]), {
            preserveScroll: true,
        });
    }
}

function estimateReservationCost(draft) {
    const selected = props.equipment.find((item) => item.id === Number(draft.equipment_id));
    if (!selected || !draft.usage_start || !draft.usage_end) {
        return null;
    }

    const start = new Date(draft.usage_start);
    const end = new Date(draft.usage_end);
    const diffMs = end.getTime() - start.getTime();
    if (Number.isNaN(diffMs) || diffMs < 0) {
        return null;
    }

    const days = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;
    const hours = days * 8;

    return Number(selected.cost_per_hour || 0) * Number(draft.quantity || 1) * hours;
}

function fmt(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtCur(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(value);
}

function changeCalendarWindow(window) {
    router.get(
        route('projects.show', props.project.id),
        { calendar_window: window },
        { preserveState: true, preserveScroll: true, only: ['todayCalendar'] },
    );
}

function riskCardClass(level) {
    if (level === 'high') return 'bg-red-50 border-red-200 text-red-700';
    if (level === 'medium') return 'bg-orange-50 border-orange-200 text-orange-700';
    return 'bg-emerald-50 border-emerald-200 text-emerald-700';
}

function itemRowClass(category, criticality) {
    if (criticality === 'high') return 'text-red-700 bg-red-50 border border-red-100';
    if (criticality === 'medium') return 'text-orange-700 bg-orange-50 border border-orange-100';

    if (category === 'equipment') return 'text-blue-700 bg-blue-50 border border-blue-100';
    if (category === 'subcontractor') return 'text-purple-700 bg-purple-50 border border-purple-100';
    if (category === 'document') return 'text-orange-700 bg-orange-50 border border-orange-100';
    if (category === 'stage' || category === 'task') return 'text-red-700 bg-red-50 border border-red-100';
    return 'text-emerald-700 bg-emerald-50 border border-emerald-100';
}
</script>