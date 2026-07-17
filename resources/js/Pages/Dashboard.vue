<template>
    <AppLayout title="Dashboard">
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <StatCard :icon="FolderIcon" label="Proiecte active" :value="stats.activeProjects" color="blue" />
            <StatCard :icon="UsersIcon" label="Echipe alocate" :value="stats.teams" color="green" />
            <StatCard :icon="ClipboardDocumentCheckIcon" label="Oferte trimise" :value="stats.quotes" color="orange" />
            <StatCard :icon="WrenchScrewdriverIcon" label="Defecte deschise" :value="stats.defects" color="red" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Taskuri restante</div>
                <div class="text-2xl font-semibold text-gray-800">{{ stats.overdueTasks }}</div>
                <div class="text-xs text-gray-500 mt-1">deadline depasit</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Etape intarziate</div>
                <div class="text-2xl font-semibold text-gray-800">{{ stats.delayedPhases }}</div>
                <div class="text-xs text-gray-500 mt-1">in executie / pending</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Progres mediu</div>
                <div class="text-2xl font-semibold text-gray-800">{{ stats.avgProgress }}%</div>
                <div class="text-xs text-gray-500 mt-1">pe etape active</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Dashboard resurse</h2>
                    <p class="text-sm text-gray-500 mt-1">Overview rapid pentru echipe, subcontractori, utilaje si materiale.</p>
                </div>
                <Link :href="route('resource-calendar.index')" class="text-xs text-orange-500 hover:underline">Calendar combinat echipe + utilaje →</Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-amber-700">Echipe supraincarcate</div>
                    <div class="text-2xl font-semibold text-amber-900 mt-1">{{ stats.overloadedTeamsCount || 0 }}</div>
                </div>
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-purple-700">Subcontractori in paralel</div>
                    <div class="text-2xl font-semibold text-purple-900 mt-1">{{ stats.parallelSubcontractorsCount || 0 }}</div>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-red-700">Utilaje indisponibile</div>
                    <div class="text-2xl font-semibold text-red-900 mt-1">{{ stats.unavailableEquipmentCount || 0 }}</div>
                </div>
                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-orange-700">Materiale cu stoc scazut</div>
                    <div class="text-2xl font-semibold text-orange-900 mt-1">{{ stats.lowStockMaterialsCount || 0 }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Echipe supraincarcate</div>
                    <div v-if="resourceDashboard.overloadedTeams?.length" class="space-y-1.5">
                        <div v-for="item in resourceDashboard.overloadedTeams" :key="`rt-${item.team_id}`" class="text-xs rounded border border-amber-100 bg-amber-50 px-2.5 py-2 text-amber-900">
                            {{ item.name }} · necesar {{ item.workers_needed }} / alocati {{ item.workers_assigned }} · {{ item.parallel_assignments }} alocari
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Nicio echipa supraincarcata azi.</div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Subcontractori in paralel</div>
                    <div v-if="resourceDashboard.parallelSubcontractors?.length" class="space-y-1.5">
                        <div v-for="item in resourceDashboard.parallelSubcontractors" :key="`rs-${item.contractor_id}`" class="text-xs rounded border border-purple-100 bg-purple-50 px-2.5 py-2 text-purple-900">
                            {{ item.name }} · {{ pluralize(item.parallel_projects, 'proiect', 'proiecte') }} · {{ pluralize(item.parallel_phases, 'etapa', 'etape') }}
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Niciun subcontractor in paralel azi.</div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Utilaje indisponibile</div>
                    <div v-if="resourceDashboard.unavailableEquipment?.length" class="space-y-1.5">
                        <div v-for="item in resourceDashboard.unavailableEquipment" :key="`re-${item.id}`" class="text-xs rounded border border-red-100 bg-red-50 px-2.5 py-2 text-red-900">
                            {{ item.name }} · {{ item.availability_status }}
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Toate utilajele active sunt disponibile.</div>
                </div>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Materiale cu stoc scazut</div>
                    <div v-if="resourceDashboard.lowStockMaterials?.length" class="space-y-1.5">
                        <div v-for="item in resourceDashboard.lowStockMaterials" :key="`rm-${item.id}`" class="text-xs rounded border border-orange-100 bg-orange-50 px-2.5 py-2 text-orange-900">
                            {{ item.name }} · stoc {{ Number(item.stock_quantity || 0).toFixed(2) }} / minim {{ Number(item.min_stock_quantity || 0).toFixed(2) }} {{ item.unit }}
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-400">Niciun material sub pragul minim.</div>
                </div>
            </div>
        </div>

        <div v-if="dailyBriefingSummary.length" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Memento zilnic</h2>
                    <p class="text-sm text-gray-500 mt-1">Proiecte cu blocaje astazi.</p>
                </div>
            </div>
            <div class="space-y-1.5">
                <Link
                    v-for="item in dailyBriefingSummary"
                    :key="`db-${item.project_id}`"
                    :href="route('daily-briefing.show', item.project_id)"
                    class="flex items-center justify-between text-xs rounded border border-red-100 bg-red-50 px-2.5 py-2 text-red-900 hover:bg-red-100"
                >
                    <span>{{ item.project_name }}</span>
                    <span class="font-semibold">{{ pluralize(item.blockers_count, 'blocaj', 'blocaje') }}</span>
                </Link>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-base font-semibold text-gray-800 mb-4">Costuri resurse in timp real</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-blue-700">Cost utilaje / zi</div>
                    <div class="text-2xl font-semibold text-blue-900 mt-1">{{ fmtCur(realtimeCosts.equipment_daily_cost || 0) }}</div>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-emerald-700">Cost echipe / zi</div>
                    <div class="text-2xl font-semibold text-emerald-900 mt-1">{{ fmtCur(realtimeCosts.team_daily_cost || 0) }}</div>
                </div>
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <div class="text-xs uppercase tracking-wider text-purple-700">Cost subcontractori / etape</div>
                    <div class="text-2xl font-semibold text-purple-900 mt-1">{{ fmtCur(stats.subcontractorDailyCost || 0) }}</div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 p-4">
                <div class="text-sm font-semibold text-gray-800 mb-2">Top etape dupa cost subcontractori</div>
                <div v-if="realtimeCosts.subcontractor_cost_by_phase?.length" class="space-y-1.5">
                    <div v-for="item in realtimeCosts.subcontractor_cost_by_phase" :key="`scp-${item.stage_id}`" class="text-xs rounded border border-gray-100 px-2.5 py-2 text-gray-700">
                        {{ item.project_name || '-' }} · {{ item.stage_name }} · {{ fmtCur(item.total_cost || 0) }}
                    </div>
                </div>
                <div v-else class="text-xs text-gray-400">Nu exista costuri de subcontractori pe etape inca.</div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-base font-semibold text-gray-800 mb-3">Alerte automate</h2>
            <div v-if="resourceAlerts.length === 0" class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Nu exista alerte noi generate automat pentru ziua curenta.
            </div>
            <div v-else class="space-y-2">
                <Link
                    v-for="alert in resourceAlerts"
                    :key="`${alert.event}-${alert.entity_id}`"
                    :href="alert.url"
                    class="block rounded-lg border px-3 py-2 text-sm"
                    :class="alert.severity === 'high' ? 'border-red-200 bg-red-50 text-red-900' : 'border-amber-200 bg-amber-50 text-amber-900'"
                >
                    <div class="font-semibold">{{ alert.title }}</div>
                    <div class="text-xs mt-0.5">{{ alert.message }}</div>
                </Link>
            </div>
        </div>

        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Cost estimat utilaje (toate rezervarile)</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ fmtCur(stats.estimatedEquipmentCost || 0) }}</div>
                    <div class="text-xs text-gray-500 mt-1">calculat pe baza cost/ora, cantitate si interval rezervare</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Documente neplatite / partial</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ stats.documentsUnpaidCount || 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">facturi si documente cu plata incompleta</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Expunere financiara documente</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ fmtCur(stats.documentsUnpaidAmount || 0) }}</div>
                    <div class="text-xs mt-1" :class="(stats.documentsOverdueInvoices || 0) > 0 ? 'text-red-600' : 'text-gray-500'">
                        restante >30 zile: {{ stats.documentsOverdueInvoices || 0 }}
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Taskuri etapa deschise</div>
                    <div class="text-2xl font-semibold text-gray-800">{{ stats.stageTasksOpen || 0 }}</div>
                    <div class="text-xs text-gray-500 mt-1">todo + in_progress + blocked</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Calendar operational AI</h2>
                    <p class="text-sm text-gray-500 mt-1">Planificare predictiva pe etape, taskuri, utilaje, subcontractori, documente si calitate.</p>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase tracking-wider text-gray-400">{{ todayCalendar.date }}</div>
                    <div class="text-sm font-semibold text-gray-800 mt-1">{{ todayCalendar.total_events || 0 }} evenimente</div>
                    <div class="text-xs mt-1" :class="(todayCalendar.risk_events || 0) > 0 ? 'text-red-600' : 'text-emerald-600'">
                        {{ todayCalendar.risk_events || 0 }} riscuri identificate
                    </div>
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <select v-model="calendarWindow" @change="applyCalendarControls" class="text-xs border border-gray-300 rounded-lg px-2 py-1 text-gray-600">
                    <option value="today">Azi</option>
                    <option value="7d">7 zile</option>
                    <option value="30d">30 zile</option>
                </select>

                <button
                    v-for="(label, key) in categoryLabels"
                    :key="key"
                    type="button"
                    @click="toggleCategory(key)"
                    class="text-xs px-2.5 py-1 rounded-full border transition"
                    :class="selectedCategories.includes(key) ? 'border-gray-800 bg-gray-800 text-white' : 'border-gray-300 bg-white text-gray-600 hover:bg-gray-50'"
                >
                    {{ label }}
                </button>
            </div>

            <div class="mb-4 rounded-xl border px-4 py-3" :class="riskCardClass(todayCalendar.risk?.level)">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-wider">Indicator AI</div>
                        <div class="text-sm font-semibold">Risc intarziere azi: {{ todayCalendar.risk?.score ?? 0 }}%</div>
                    </div>
                    <div class="text-xs">
                        etape risc: {{ todayCalendar.risk?.risky_stages ?? 0 }} ·
                        taskuri blocate: {{ todayCalendar.risk?.blocked_tasks ?? 0 }} ·
                        documente neplatite: {{ todayCalendar.risk?.unpaid_documents ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="mb-4 rounded-xl border border-gray-200 px-4 py-3 bg-gray-50">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Incarcare pe zi</div>
                        <div class="text-sm font-semibold" :class="loadClass(todayCalendar.load?.level)">
                            {{ todayCalendar.load?.label || 'Zi lejera' }}
                        </div>
                    </div>
                    <div class="text-xs text-gray-600">{{ todayCalendar.load?.value || 0 }} / {{ todayCalendar.load?.max || 12 }}</div>
                </div>
                <div class="mt-2 h-2.5 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full transition-all" :class="todayCalendar.load?.level === 'critical' ? 'bg-red-500' : (todayCalendar.load?.level === 'normal' ? 'bg-amber-500' : 'bg-emerald-500')" :style="{ width: loadBarWidth }"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3 mb-4">
                <div class="rounded-lg border border-red-200 bg-red-50 p-3">
                    <div class="text-xs uppercase tracking-wider text-red-700 mb-2">Risc intarziere pe etape</div>
                    <div v-if="todayCalendar.risk?.predictive?.stage_delay?.length" class="space-y-1.5">
                        <div v-for="item in todayCalendar.risk.predictive.stage_delay" :key="`pred-stage-${item.id}`" class="relative flex items-start gap-2 text-xs text-red-800">
                            <a :href="item.url" class="flex-1 min-w-0 hover:underline">{{ item.title }} · risc {{ item.risk_pct }}% ({{ item.reason }})</a>
                            <button type="button" @click="toggleRiskPopover(`stage-${item.id}`)" class="shrink-0 text-[11px] px-1.5 py-0.5 rounded border border-red-200 bg-white text-red-700 hover:bg-red-100">detalii</button>
                            <div v-if="isRiskPopoverOpen(`stage-${item.id}`)" class="absolute z-20 right-0 top-6 w-72 rounded-lg border border-red-200 bg-white shadow-lg p-3">
                                <div class="text-[11px] font-semibold text-red-700 mb-2">Factori risc</div>
                                <div class="space-y-1.5">
                                    <div v-for="(factor, index) in item.factors || []" :key="`factor-stage-${item.id}-${index}`" class="text-[11px] border border-gray-100 rounded px-2 py-1">
                                        <div class="flex items-center justify-between gap-2 mb-0.5">
                                            <span class="font-medium text-gray-700">{{ factor.label }}</span>
                                            <span class="px-1.5 py-0.5 rounded" :class="factorBadgeClass(factor)">{{ factor.impact }}</span>
                                        </div>
                                        <div class="text-gray-500">{{ factor.detail }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-red-700/70">Fara semnale relevante.</div>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <div class="text-xs uppercase tracking-wider text-amber-700 mb-2">Risc depasire buget</div>
                    <div v-if="todayCalendar.risk?.predictive?.budget_overrun?.length" class="space-y-1.5">
                        <div v-for="item in todayCalendar.risk.predictive.budget_overrun" :key="`pred-budget-${item.id}`" class="relative flex items-start gap-2 text-xs text-amber-800">
                            <a :href="item.url" class="flex-1 min-w-0 hover:underline">{{ item.title }} · risc {{ item.risk_pct }}% ({{ item.reason }})</a>
                            <button type="button" @click="toggleRiskPopover(`budget-${item.id}`)" class="shrink-0 text-[11px] px-1.5 py-0.5 rounded border border-amber-200 bg-white text-amber-700 hover:bg-amber-100">detalii</button>
                            <div v-if="isRiskPopoverOpen(`budget-${item.id}`)" class="absolute z-20 right-0 top-6 w-72 rounded-lg border border-amber-200 bg-white shadow-lg p-3">
                                <div class="text-[11px] font-semibold text-amber-700 mb-2">Factori risc</div>
                                <div class="space-y-1.5">
                                    <div v-for="(factor, index) in item.factors || []" :key="`factor-budget-${item.id}-${index}`" class="text-[11px] border border-gray-100 rounded px-2 py-1">
                                        <div class="flex items-center justify-between gap-2 mb-0.5">
                                            <span class="font-medium text-gray-700">{{ factor.label }}</span>
                                            <span class="px-1.5 py-0.5 rounded" :class="factorBadgeClass(factor)">{{ factor.impact }}</span>
                                        </div>
                                        <div class="text-gray-500">{{ factor.detail }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-amber-700/70">Fara semnale relevante.</div>
                </div>

                <div class="rounded-lg border border-purple-200 bg-purple-50 p-3">
                    <div class="text-xs uppercase tracking-wider text-purple-700 mb-2">Risc subcontractor</div>
                    <div v-if="todayCalendar.risk?.predictive?.subcontractor?.length" class="space-y-1.5">
                        <div v-for="item in todayCalendar.risk.predictive.subcontractor" :key="`pred-sub-${item.id}`" class="relative flex items-start gap-2 text-xs text-purple-800">
                            <a :href="item.url" class="flex-1 min-w-0 hover:underline">{{ item.title }} · {{ item.parallel_projects }} proiecte paralele → risc {{ item.risk_pct }}%</a>
                            <button type="button" @click="toggleRiskPopover(`sub-${item.id}`)" class="shrink-0 text-[11px] px-1.5 py-0.5 rounded border border-purple-200 bg-white text-purple-700 hover:bg-purple-100">detalii</button>
                            <div v-if="isRiskPopoverOpen(`sub-${item.id}`)" class="absolute z-20 right-0 top-6 w-72 rounded-lg border border-purple-200 bg-white shadow-lg p-3">
                                <div class="text-[11px] font-semibold text-purple-700 mb-2">Factori risc</div>
                                <div class="space-y-1.5">
                                    <div v-for="(factor, index) in item.factors || []" :key="`factor-sub-${item.id}-${index}`" class="text-[11px] border border-gray-100 rounded px-2 py-1">
                                        <div class="flex items-center justify-between gap-2 mb-0.5">
                                            <span class="font-medium text-gray-700">{{ factor.label }}</span>
                                            <span class="px-1.5 py-0.5 rounded" :class="factorBadgeClass(factor)">{{ factor.impact }}</span>
                                        </div>
                                        <div class="text-gray-500">{{ factor.detail }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-xs text-purple-700/70">Fara semnale relevante.</div>
                </div>
            </div>

            <div v-if="(todayCalendar.total_events || 0) === 0" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                Nu exista evenimente operationale planificate pentru azi.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Etape programate azi</div>
                    <div v-if="todayCalendar.stages?.length" class="space-y-2">
                        <Link v-for="item in todayCalendar.stages" :key="`stage-${item.id}`" :href="item.url" class="block text-xs border rounded-lg px-3 py-2" :class="itemRowClass('stage', item.criticality)">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.project_name || '-' }} · {{ item.status }}</div>
                        </Link>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara etape azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Taskuri cu deadline azi</div>
                    <div v-if="todayCalendar.tasks?.length" class="space-y-2">
                        <Link v-for="item in todayCalendar.tasks" :key="`task-${item.id}`" :href="item.url" class="block text-xs border rounded-lg px-3 py-2" :class="itemRowClass('task', item.criticality)">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.status }}</div>
                        </Link>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara taskuri azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Utilaje rezervate azi</div>
                    <div v-if="todayCalendar.equipment?.length" class="space-y-2">
                        <Link v-for="item in todayCalendar.equipment" :key="`equipment-${item.id}`" :href="item.url" class="block text-xs border rounded-lg px-3 py-2" :class="itemRowClass('equipment', item.criticality)">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.time_range }}</div>
                        </Link>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara utilaje programate azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Subcontractori programati azi</div>
                    <div v-if="todayCalendar.subcontractors?.length" class="space-y-2">
                        <Link v-for="item in todayCalendar.subcontractors" :key="`sub-${item.id}`" :href="item.url" class="block text-xs border rounded-lg px-3 py-2" :class="itemRowClass('subcontractor', item.criticality)">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.window }}</div>
                        </Link>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara subcontractori programati azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Documente cu termen azi</div>
                    <div v-if="todayCalendar.documents?.length" class="space-y-2">
                        <Link v-for="item in todayCalendar.documents" :key="`doc-${item.id}`" :href="item.url" class="block text-xs border rounded-lg px-3 py-2" :class="itemRowClass('document', item.criticality)">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.project_name || '-' }} · {{ fmtCur(item.amount || 0) }}</div>
                        </Link>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara documente scadente azi.</div>
                </div>

                <div class="rounded-xl border border-gray-200 p-4">
                    <div class="text-sm font-semibold text-gray-800 mb-2">Verificari / calitate azi</div>
                    <div v-if="todayCalendar.quality_checks?.length" class="space-y-2">
                        <Link v-for="item in todayCalendar.quality_checks" :key="`qc-${item.id}`" :href="item.url" class="block text-xs border rounded-lg px-3 py-2" :class="itemRowClass('quality', item.criticality)">
                            <div class="font-medium">{{ item.title }}</div>
                            <div>{{ item.stage_name || '-' }} · {{ item.planned_at || '-' }}</div>
                        </Link>
                    </div>
                    <div v-else class="text-xs text-gray-400">Fara verificari programate azi.</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-base font-semibold text-gray-800">Atentie azi</h2>
                    <p class="text-sm text-gray-500 mt-1">Elementele care cer actiune rapida din executie, financiar si calitate.</p>
                </div>
                <div v-if="attentionItems.length === 0" class="text-sm font-medium text-green-600">
                    Nicio alerta critica in acest moment.
                </div>
            </div>

            <div v-if="attentionItems.length === 0" class="rounded-xl border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-700">
                Dashboard-ul nu a identificat intarzieri, restante sau blocaje prioritare pentru azi.
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <Link
                    v-for="item in attentionItems"
                    :key="item.title"
                    :href="item.href"
                    class="rounded-xl border px-4 py-4 transition hover:shadow-sm"
                    :class="item.tone"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold">{{ item.title }}</div>
                            <div class="text-2xl font-bold mt-2">{{ item.value }}</div>
                        </div>
                        <Icon :icon="item.icon" size="h-6 w-6 shrink-0" />
                    </div>
                    <div class="text-sm mt-2 opacity-90">{{ item.description }}</div>
                    <div class="text-xs mt-3 font-medium opacity-80">{{ item.cta }}</div>
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-gray-800"><Icon :icon="FolderIcon" size="h-5 w-5 text-gray-400" /> Proiecte recente</h2>
                    <Link :href="route('projects.index')" class="text-xs text-orange-500 hover:underline">Vezi toate →</Link>
                </div>
                <div v-if="recentProjects.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    <Icon :icon="BuildingOffice2Icon" size="h-8 w-8 mx-auto mb-2 text-gray-300" />
                    Niciun proiect creat inca.<br />
                    <Link :href="route('projects.create')" class="text-orange-500 hover:underline mt-2 inline-block font-medium">
                        + Creeaza primul proiect
                    </Link>
                </div>
                <div v-else class="space-y-3">
                    <Link
                        v-for="p in recentProjects"
                        :key="p.id"
                        :href="route('projects.show', p.id)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ p.name }}</div>
                            <div class="text-xs text-gray-400">{{ p.client?.name ?? 'Fara client' }}</div>
                        </div>
                        <StatusBadge :status="p.status" />
                    </Link>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-gray-800"><Icon :icon="CheckCircleIcon" size="h-5 w-5 text-gray-400" /> Taskuri pentru azi</h2>
                    <Link :href="route('tasks.index')" class="text-xs text-orange-500 hover:underline">Vezi toate →</Link>
                </div>
                <div v-if="todayTasks.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    <Icon :icon="CheckCircleIcon" size="h-8 w-8 mx-auto mb-2 text-gray-300" />
                    Niciun task pentru azi.
                    <Link :href="route('tasks.create')" class="text-orange-500 hover:underline mt-2 inline-block font-medium">
                        + Creeaza task
                    </Link>
                </div>
                <div v-else class="space-y-3">
                    <Link
                        v-for="task in todayTasks"
                        :key="task.id"
                        :href="route('tasks.edit', task.id)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ task.title }}</div>
                            <div class="text-xs text-gray-400">{{ task.project?.name ?? 'Fara proiect' }}</div>
                        </div>
                        <StatusBadge :status="task.status === 'in_progress' ? 'active' : task.status === 'done' ? 'completed' : task.status === 'cancelled' ? 'cancelled' : 'draft'" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="flex items-center gap-2 text-base font-semibold text-gray-800 mb-4"><Icon :icon="ClockIcon" size="h-5 w-5 text-gray-400" /> Etape intarziate</h2>
                <div v-if="delayedPhases.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Nicio etapa intarziata.
                </div>
                <div v-else class="space-y-3">
                    <div v-for="phase in delayedPhases" :key="phase.id" class="p-3 rounded-lg border border-gray-100">
                        <div class="text-sm font-medium text-gray-800">{{ phase.name }}</div>
                        <div class="text-xs text-gray-500">{{ phase.project?.name ?? 'Fara proiect' }} · termen {{ fmt(phase.end_date) }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-gray-800"><Icon :icon="WrenchScrewdriverIcon" size="h-5 w-5 text-gray-400" /> Defecte deschise</h2>
                    <Link :href="route('defects.index')" class="text-xs text-orange-500 hover:underline">Vezi toate →</Link>
                </div>
                <div v-if="openDefects.length === 0" class="text-center py-8 text-gray-400 text-sm">
                    Nu exista defecte deschise.
                </div>
                <div v-else class="space-y-3">
                    <Link
                        v-for="defect in openDefects"
                        :key="defect.id"
                        :href="route('defects.edit', defect.id)"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition border border-gray-100"
                    >
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ defect.title }}</div>
                            <div class="text-xs text-gray-500">{{ defect.project?.name ?? 'Fara proiect' }}</div>
                        </div>
                        <StatusBadge :status="defect.status === 'in_progress' ? 'active' : 'draft'" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
            <h2 class="flex items-center gap-2 text-base font-semibold text-gray-800 mb-4"><Icon :icon="PresentationChartBarIcon" size="h-5 w-5 text-gray-400" /> Plan vs Real pe etape</h2>
            <div v-if="stagePlanVsReal.length === 0" class="text-center py-8 text-gray-400 text-sm">
                Nu exista date de comparatie pentru etape.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-2 pr-3">Proiect</th>
                            <th class="py-2 pr-3">Etapa</th>
                            <th class="py-2 pr-3">Plan %</th>
                            <th class="py-2 pr-3">Real %</th>
                            <th class="py-2 pr-3">Delta</th>
                            <th class="py-2 pr-3">Cost documentat</th>
                            <th class="py-2 pr-3">Ultim raport</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in stagePlanVsReal" :key="item.id" class="border-b last:border-0">
                            <td class="py-2 pr-3">{{ item.project_name || '-' }}</td>
                            <td class="py-2 pr-3">{{ item.stage_name }}</td>
                            <td class="py-2 pr-3">{{ item.planned_progress }}%</td>
                            <td class="py-2 pr-3">{{ item.actual_progress }}%</td>
                            <td class="py-2 pr-3" :class="item.progress_delta >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ item.progress_delta >= 0 ? '+' : '' }}{{ item.progress_delta }}%
                            </td>
                            <td class="py-2 pr-3 font-medium">{{ fmtCur(item.documented_cost) }}</td>
                            <td class="py-2 pr-3 text-gray-500">{{ item.latest_report_date || '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Icon from '@/Components/Icon.vue';
import { pluralize } from '@/utils/pluralize';
import {
    BanknotesIcon,
    BuildingOffice2Icon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    ClockIcon,
    FolderIcon,
    ListBulletIcon,
    PresentationChartBarIcon,
    UsersIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    stats:          { type: Object, default: () => ({ activeProjects: 0, teams: 0, quotes: 0, defects: 0, overdueTasks: 0, delayedPhases: 0, avgProgress: 0, estimatedEquipmentCost: 0, documentsUnpaidCount: 0, documentsUnpaidAmount: 0, documentsOverdueInvoices: 0, stageTasksOpen: 0, overloadedTeamsCount: 0, parallelSubcontractorsCount: 0, unavailableEquipmentCount: 0, lowStockMaterialsCount: 0, equipmentDailyCost: 0, teamDailyCost: 0, subcontractorDailyCost: 0 }) },
    resourceDashboard: { type: Object, default: () => ({ overloadedTeams: [], parallelSubcontractors: [], unavailableEquipment: [], lowStockMaterials: [] }) },
    realtimeCosts: { type: Object, default: () => ({ equipment_daily_cost: 0, team_daily_cost: 0, subcontractor_cost_by_phase: [] }) },
    resourceAlerts: { type: Array, default: () => [] },
    dailyBriefingSummary: { type: Array, default: () => [] },
    recentProjects: { type: Array,  default: () => [] },
    todayTasks:     { type: Array,  default: () => [] },
    todayCalendar:  { type: Object, default: () => ({ date: '', window: 'today', categories: ['stages', 'tasks', 'subcontractors', 'equipment', 'documents', 'quality_checks'], total_events: 0, risk_events: 0, stages: [], tasks: [], equipment: [], subcontractors: [], documents: [], quality_checks: [], load: { level: 'light', label: 'Zi lejera', max: 12, value: 0 }, risk: { score: 0, level: 'low', blocked_tasks: 0, risky_stages: 0, unpaid_documents: 0, predictive: { stage_delay: [], budget_overrun: [], subcontractor: [] } } }) },
    delayedPhases:  { type: Array,  default: () => [] },
    openDefects:    { type: Array,  default: () => [] },
    stagePlanVsReal:{ type: Array,  default: () => [] },
});

const attentionItems = computed(() => {
    const items = [];

    if ((props.stats.overdueTasks || 0) > 0) {
        items.push({
            title: 'Taskuri depasite',
            value: props.stats.overdueTasks,
            description: 'Exista taskuri generale care au depasit termenul si trebuie replanificate.',
            cta: 'Deschide taskurile restante',
            href: route('tasks.index', { status: 'todo,in_progress' }),
            icon: ClockIcon,
            tone: 'border-amber-200 bg-amber-50 text-amber-900',
        });
    }

    if ((props.stats.delayedPhases || 0) > 0) {
        items.push({
            title: 'Etape intarziate',
            value: props.stats.delayedPhases,
            description: props.delayedPhases[0]?.name
                ? `Prima etapa cu risc: ${props.delayedPhases[0].name}.`
                : 'Exista etape in executie sau pending care au depasit termenul.',
            cta: 'Verifica WBS si replanificarea',
            href: route('wbs.index', { status: 'delayed' }),
            icon: PresentationChartBarIcon,
            tone: 'border-red-200 bg-red-50 text-red-900',
        });
    }

    if ((props.stats.documentsOverdueInvoices || 0) > 0) {
        items.push({
            title: 'Restante financiare',
            value: props.stats.documentsOverdueInvoices,
            description: 'Exista facturi sau documente neachitate de peste 30 de zile.',
            cta: 'Deschide documentele financiare',
            href: route('documents.index', { payment_status: 'unpaid' }),
            icon: BanknotesIcon,
            tone: 'border-rose-200 bg-rose-50 text-rose-900',
        });
    }

    if ((props.stats.stageTasksOpen || 0) > 0) {
        items.push({
            title: 'Taskuri de etapa deschise',
            value: props.stats.stageTasksOpen,
            description: 'Etapele active au taskuri operationale care cer urmarire zilnica.',
            cta: 'Deschide taskurile pe etapa',
            href: route('stage-tasks.index', { status: 'todo,in_progress,blocked' }),
            icon: ListBulletIcon,
            tone: 'border-blue-200 bg-blue-50 text-blue-900',
        });
    }

    return items.slice(0, 4);
});

const calendarWindow = ref(props.todayCalendar?.window || 'today');
const selectedCategories = ref([...(props.todayCalendar?.categories || ['stages', 'tasks', 'subcontractors', 'equipment', 'documents', 'quality_checks'])]);
const riskPopoverState = ref({ activeKey: null });

const categoryLabels = {
    stages: 'Etape',
    tasks: 'Taskuri',
    subcontractors: 'Subcontractori',
    equipment: 'Utilaje',
    documents: 'Documente',
    quality_checks: 'Calitate',
};

const loadBarWidth = computed(() => {
    const max = Number(props.todayCalendar?.load?.max || 12);
    const value = Number(props.todayCalendar?.load?.value || 0);
    return `${Math.min(100, Math.round((value / max) * 100))}%`;
});

function applyCalendarControls() {
    riskPopoverState.value.activeKey = null;

    router.get(route('dashboard'), {
        calendar_window: calendarWindow.value,
        calendar_categories: selectedCategories.value.join(','),
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['todayCalendar'],
    });
}

function toggleCategory(category) {
    if (selectedCategories.value.includes(category)) {
        if (selectedCategories.value.length === 1) {
            return;
        }
        selectedCategories.value = selectedCategories.value.filter((item) => item !== category);
    } else {
        selectedCategories.value = [...selectedCategories.value, category];
    }

    applyCalendarControls();
}

function toggleRiskPopover(key) {
    riskPopoverState.value.activeKey = riskPopoverState.value.activeKey === key ? null : key;
}

function isRiskPopoverOpen(key) {
    return riskPopoverState.value.activeKey === key;
}

function fmt(date) {
    return new Date(date).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function fmtCur(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 0 }).format(value || 0);
}

function riskCardClass(level) {
    if (level === 'high') return 'border-red-200 bg-red-50 text-red-800';
    if (level === 'medium') return 'border-orange-200 bg-orange-50 text-orange-800';
    return 'border-emerald-200 bg-emerald-50 text-emerald-800';
}

function itemRowClass(category, criticality) {
    if (criticality === 'high') return 'border-red-200 bg-red-50 text-red-800';
    if (criticality === 'medium') return 'border-orange-200 bg-orange-50 text-orange-800';

    if (category === 'equipment') return 'border-blue-200 bg-blue-50 text-blue-800';
    if (category === 'subcontractor') return 'border-purple-200 bg-purple-50 text-purple-800';
    if (category === 'document') return 'border-orange-200 bg-orange-50 text-orange-900';
    if (category === 'stage' || category === 'task') return 'border-red-200 bg-red-50 text-red-800';
    return 'border-emerald-200 bg-emerald-50 text-emerald-800';
}

function loadClass(level) {
    if (level === 'critical') return 'text-red-700';
    if (level === 'normal') return 'text-amber-700';
    return 'text-emerald-700';
}

function factorBadgeClass(factor) {
    const impact = String(factor?.impact || '').toLowerCase();

    if (impact.startsWith('+')) {
        return 'bg-red-100 text-red-700';
    }

    if (impact.includes('%')) {
        return 'bg-amber-100 text-amber-700';
    }

    if (impact.includes('x')) {
        return 'bg-purple-100 text-purple-700';
    }

    return 'bg-gray-100 text-gray-700';
}
</script>