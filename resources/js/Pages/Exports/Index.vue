<template>
    <AppLayout title="Exporturi">
        <div class="max-w-6xl mx-auto space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Exporturi si rapoarte</h2>
                <p class="text-sm text-gray-500 mt-1">Descarca datele operationale in format CSV sau pachet complet pe proiect.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Filtre avansate export</h3>
                <div class="mb-4">
                    <label class="block text-xs text-gray-600 mb-1">Interval rapid</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="option in quickRangeOptions"
                            :key="option.value"
                            type="button"
                            class="text-xs rounded-full border px-3 py-1.5"
                            :class="filters.quick_range === option.value ? 'border-orange-300 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                            @click="setQuickRange(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Interval de la</label>
                        <input v-model="filters.from" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Interval pana la</label>
                        <input v-model="filters.to" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                        <select v-model="filters.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Toate</option>
                            <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Echipa</label>
                        <select v-model="filters.team_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Toate</option>
                            <option v-for="team in teams" :key="team.id" :value="String(team.id)">{{ team.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Statusuri</label>
                        <input v-model="filters.status" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: open,in_progress" />
                        <p class="mt-1 text-[11px] text-gray-400">Mai multe valori se separa prin virgula.</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Prioritati</label>
                        <input v-model="filters.priority" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: high,medium" />
                        <p class="mt-1 text-[11px] text-gray-400">Util pentru taskuri, defecte si fluxuri operationale.</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Responsabili</label>
                        <input v-model="filters.assignee_ids" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="ex: 12,18" />
                        <p class="mt-1 text-[11px] text-gray-400">Camp optional pentru filtre tehnice pe utilizator.</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Cautare globala</label>
                        <input v-model="filters.global_search" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="proiect X, etapa finisaje, defecte high, utilaje pompa" />
                    </div>
                </div>
                <div class="mt-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" v-model="filters.include_inactive" class="rounded border-gray-300 text-orange-500" />
                        Include elemente inactive
                    </label>
                </div>

                <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50/60 p-4 md:p-5">
                    <h3 class="font-semibold text-gray-800 mb-1">Rapoarte predefinite (one-click)</h3>
                    <p class="text-xs text-gray-500 mb-4">Template-uri profesionale pentru manageri. Se aplica automat filtrele active de mai sus.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3">
                        <div
                            v-for="template in reportTemplates"
                            :key="template.key"
                            class="rounded-lg border border-gray-200 bg-white p-3 flex flex-col"
                        >
                            <div class="text-sm font-semibold text-gray-800">{{ template.label }}</div>
                            <div class="text-xs text-gray-500 mt-1 flex-1">{{ template.description }}</div>
                            <div class="flex flex-wrap gap-2 mt-3">
                                <a :href="templateWorkbookUrl(template)" class="text-xs border border-green-200 text-green-700 rounded px-2 py-1 hover:bg-green-50">XLSX</a>
                                <a :href="templatePdfUrl(template)" class="text-xs border border-red-200 text-red-700 rounded px-2 py-1 hover:bg-red-50">PDF</a>
                                <a v-if="template.primaryCsvRoute" :href="routeWithFilters(template.primaryCsvRoute)" class="text-xs border border-sky-200 text-sky-700 rounded px-2 py-1 hover:bg-sky-50">CSV</a>
                                <button
                                    type="button"
                                    class="text-xs border border-gray-300 text-gray-700 rounded px-2 py-1 hover:bg-gray-50"
                                    @click="previewTemplate(template)"
                                >
                                    Preview
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4 md:p-5 space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="domain in exportDomains"
                            :key="domain.key"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-xs transition"
                            :class="activeExportDomain === domain.key ? 'border-orange-300 bg-orange-50 text-orange-700' : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                            @click="activeExportDomain = domain.key"
                        >
                            <span>{{ domain.label }}</span>
                            <span class="ml-2 rounded-full bg-white/80 px-2 py-0.5 text-[10px] font-semibold" :class="activeExportDomain === domain.key ? 'text-orange-700' : 'text-gray-500'">
                                {{ domainCounts[domain.key] ?? 0 }}
                            </span>
                        </button>
                    </div>

                    <div v-if="activeDomainInfo" class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-gray-800">{{ activeDomainInfo.label }}</div>
                                <div class="text-xs text-gray-500">{{ activeDomainInfo.description }}</div>
                            </div>
                            <div class="text-xs font-medium text-gray-600">{{ domainCounts[activeExportDomain] ?? 0 }} rapoarte</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <a
                            v-for="card in filteredExportCards"
                            :key="card.route"
                            :href="routeWithFilters(card.route)"
                            class="bg-white border border-gray-200 rounded-xl p-5 hover:border-orange-300 hover:shadow-sm transition block"
                        >
                            <div class="text-sm font-semibold text-gray-800">{{ card.title }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ card.description }}</div>
                        </a>
                    </div>
                </div>

                <div v-if="previewState.loading || previewState.error" class="mt-6 rounded-xl border border-gray-200 bg-white p-4 md:p-5">
                    <div v-if="previewState.loading" class="flex items-center gap-3 text-sm text-gray-600">
                        <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-orange-500"></span>
                        Se genereaza preview-ul raportului...
                    </div>
                    <div v-else class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ previewState.error }}
                    </div>
                </div>
            </div>

                <div v-if="previewState.result" class="mt-4 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 bg-gradient-to-r from-slate-50 via-white to-orange-50 px-4 py-4 md:px-5">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-orange-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-orange-700">Preview export</span>
                                    <span v-if="isResourceComparisonPreview" class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-violet-700">Raport comparativ</span>
                                </div>
                                <div class="mt-2 text-base font-semibold text-gray-900 md:text-lg">{{ previewState.result.title }}</div>
                                <div class="mt-1 text-xs text-gray-500">
                                    Randuri estimate: {{ previewState.result.rows_count }} · Generat: {{ formatDateTime(previewState.result.generated_at) }}
                                </div>
                            </div>

                            <div class="max-w-2xl rounded-xl border border-gray-200 bg-white/80 p-3 text-xs text-gray-600 shadow-sm">
                                <div class="font-semibold text-gray-700">Filtre active</div>
                                <div class="mt-1 leading-5">
                                    {{ formatActiveFilters(previewState.result.active_filters) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 md:p-5">
                        <div v-if="isResourceComparisonPreview" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                                <div class="rounded-xl border border-orange-100 bg-orange-50/70 p-4">
                                    <div class="text-[11px] uppercase tracking-wide text-orange-700">Comenzi analizate</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ formatNumber(resourceComparisonSummary.orders_count) }}</div>
                                </div>
                                <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-4">
                                    <div class="text-[11px] uppercase tracking-wide text-emerald-700">Comandat total</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ formatNumber(resourceComparisonSummary.ordered_quantity_total) }}</div>
                                </div>
                                <div class="rounded-xl border border-sky-100 bg-sky-50/70 p-4">
                                    <div class="text-[11px] uppercase tracking-wide text-sky-700">Receptionat total</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ formatNumber(resourceComparisonSummary.received_quantity_total) }}</div>
                                </div>
                                <div class="rounded-xl border border-violet-100 bg-violet-50/70 p-4">
                                    <div class="text-[11px] uppercase tracking-wide text-violet-700">Diferenta receptionare</div>
                                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ formatNumber(resourceComparisonSummary.received_delta_total) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="rounded-xl border border-gray-200 bg-white p-4">
                                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Consum total</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-900">{{ formatNumber(resourceComparisonSummary.consumed_quantity_total) }}</div>
                                </div>
                                <div class="rounded-xl border border-gray-200 bg-white p-4">
                                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Returnat total</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-900">{{ formatNumber(resourceComparisonSummary.returned_quantity_total) }}</div>
                                </div>
                                <div class="rounded-xl border border-gray-200 bg-white p-4">
                                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Linkuri documente</div>
                                    <div class="mt-1 text-lg font-semibold text-gray-900">{{ formatNumber(resourceComparisonSummary.document_links_total) }}</div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white">
                                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                                    <div class="text-sm font-semibold text-gray-800">Mostre comparative</div>
                                    <div class="text-xs text-gray-500">Rezumat rapid al comenzilor cu avize si diferente</div>
                                </div>

                                <div class="divide-y divide-gray-100">
                                    <div v-for="(row, index) in previewComparisonRows" :key="index" class="grid grid-cols-1 gap-3 px-4 py-4 md:grid-cols-12 md:items-center">
                                        <div class="md:col-span-3">
                                            <div class="text-sm font-semibold text-gray-900">{{ row.project || '-' }}</div>
                                            <div class="text-[11px] text-gray-500">{{ row.phase || '-' }}</div>
                                        </div>
                                        <div class="md:col-span-3">
                                            <div class="text-sm font-medium text-gray-800">{{ row.material || '-' }}</div>
                                            <div class="text-[11px] text-gray-500">{{ row.resource_label || row.resource_type || '-' }}</div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 md:col-span-4 md:grid-cols-4">
                                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                                <div class="text-[10px] uppercase tracking-wide text-gray-500">Comandat</div>
                                                <div class="text-sm font-semibold text-gray-900">{{ formatNumber(row.ordered_quantity) }} {{ row.ordered_unit || '' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                                <div class="text-[10px] uppercase tracking-wide text-gray-500">Receptionat</div>
                                                <div class="text-sm font-semibold text-gray-900">{{ formatNumber(row.received_quantity) }} {{ row.ordered_unit || '' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                                <div class="text-[10px] uppercase tracking-wide text-gray-500">Consum</div>
                                                <div class="text-sm font-semibold text-gray-900">{{ formatNumber(row.consumed_quantity) }} {{ row.ordered_unit || '' }}</div>
                                            </div>
                                            <div class="rounded-lg bg-gray-50 px-3 py-2">
                                                <div class="text-[10px] uppercase tracking-wide text-gray-500">Returnat</div>
                                                <div class="text-sm font-semibold text-gray-900">{{ formatNumber(row.returned_quantity) }} {{ row.ordered_unit || '' }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between gap-3 md:col-span-2 md:justify-end md:text-right">
                                            <div>
                                                <div class="text-[10px] uppercase tracking-wide text-gray-500">Avize</div>
                                                <div class="text-sm font-semibold text-gray-900">{{ formatNumber(row.document_links_count) }}</div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] uppercase tracking-wide text-gray-500">Dif. doc</div>
                                                <div class="text-sm font-semibold text-gray-900">{{ formatNumber(row.document_difference_quantity) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="mt-2">
                            <div v-if="(previewState.result.sample || []).length > 0" class="space-y-3">
                                <div class="text-xs font-semibold text-gray-700">Sample (primele randuri)</div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div
                                        v-for="(sample, index) in previewState.result.sample"
                                        :key="index"
                                        class="rounded-xl border border-gray-200 bg-gray-50/60 p-4"
                                    >
                                        <div class="mb-3 flex items-center justify-between">
                                            <div class="text-sm font-semibold text-gray-800">Rand {{ index + 1 }}</div>
                                            <span class="rounded-full bg-white px-2 py-1 text-[11px] font-medium text-gray-500">preview</span>
                                        </div>
                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <div
                                                v-for="entry in previewSampleEntries(sample)"
                                                :key="entry.key"
                                                class="rounded-lg border border-white bg-white px-3 py-2 shadow-sm"
                                            >
                                                <div class="text-[10px] uppercase tracking-wide text-gray-500">{{ entry.key }}</div>
                                                <div class="mt-1 text-sm font-medium text-gray-800 break-words">{{ entry.value }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Pachet complet pe proiect</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Proiect</label>
                        <select v-model="projectId" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">— Selecteaza proiect —</option>
                            <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                        </select>
                    </div>
                    <div>
                        <a
                            :href="projectId ? route('exports.project.package', projectId) : '#'
                            "
                            :class="projectId ? 'bg-orange-500 hover:bg-orange-600 text-white' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                            class="inline-flex items-center justify-center w-full px-4 py-2 rounded-lg text-sm font-medium transition"
                            @click.prevent="downloadProjectPackage"
                        >
                            Descarca pachet proiect
                        </a>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Include proiect, etape, taskuri, defecte, oferte si sumar KPI.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Distribuire automata pe email</h3>
                <form @submit.prevent="createSubscription" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Nume flux *</label>
                        <input v-model="subscriptionForm.name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Raport saptamanal PM" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Tip export *</label>
                        <select v-model="subscriptionForm.export_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option v-for="option in exportTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Format *</label>
                        <select v-model="subscriptionForm.format" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="xlsx">xlsx</option>
                            <option value="pdf">pdf</option>
                            <option value="csv">csv</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Frecventa *</label>
                        <select v-model="subscriptionForm.frequency" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="daily">daily</option>
                            <option value="weekly">weekly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Ora executie</label>
                        <input v-model="subscriptionForm.schedule_time" type="time" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1">Zi saptamana (0-6)</label>
                        <input v-model.number="subscriptionForm.schedule_weekday" type="number" min="0" max="6" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-600 mb-1">Destinatari (email-uri, separate prin virgula) *</label>
                        <input v-model="subscriptionForm.recipientsText" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="pm@firma.ro,owner@firma.ro" />
                    </div>
                    <div class="md:col-span-4">
                        <button type="submit" :disabled="subscriptionForm.processing" class="bg-orange-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50">
                            {{ subscriptionForm.processing ? 'Se salveaza...' : 'Creeaza abonare' }}
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <h4 class="text-sm font-semibold text-gray-800 mb-3">Abonari existente</h4>
                    <div v-if="subscriptions.length === 0" class="text-sm text-gray-400">Nu exista abonari.</div>
                    <div v-else class="space-y-2">
                        <div v-for="subscription in subscriptions" :key="subscription.id" class="border border-gray-100 rounded-lg p-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-medium text-gray-800">{{ subscription.name }}</div>
                                <div class="text-xs text-gray-500">{{ formatExportType(subscription.export_type) }} · {{ subscription.format.toUpperCase() }} · {{ subscription.frequency }} · next {{ formatDateTime(subscription.next_run_at) }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="runSubscription(subscription)" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">Ruleaza acum</button>
                                <button @click="toggleSubscription(subscription)" class="text-xs border rounded px-2 py-1" :class="subscription.active ? 'border-red-200 text-red-600 hover:bg-red-50' : 'border-green-200 text-green-600 hover:bg-green-50'">
                                    {{ subscription.active ? 'Dezactiveaza' : 'Activeaza' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Audit exporturi</h3>
                <div v-if="recentLogs.length === 0" class="text-sm text-gray-400">Nu exista inregistrari.</div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pr-3">Data</th>
                                <th class="py-2 pr-3">Tip</th>
                                <th class="py-2 pr-3">Format</th>
                                <th class="py-2 pr-3">Status</th>
                                <th class="py-2 pr-3">Fisier</th>
                                <th class="py-2 pr-3">Livrare</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in recentLogs" :key="log.id" class="border-b last:border-0">
                                <td class="py-2 pr-3 text-gray-600">{{ formatDateTime(log.created_at) }}</td>
                                <td class="py-2 pr-3">{{ formatExportType(log.export_type) }}</td>
                                <td class="py-2 pr-3">{{ log.format?.toUpperCase?.() ?? log.format }}</td>
                                <td class="py-2 pr-3">{{ log.status }}</td>
                                <td class="py-2 pr-3">{{ log.file_name || '-' }}</td>
                                <td class="py-2 pr-3">{{ log.delivery_channel || '-' }} {{ log.delivery_target ? '→ ' + log.delivery_target : '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    projects: { type: Array, default: () => [] },
    teams: { type: Array, default: () => [] },
    users: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    recentLogs: { type: Array, default: () => [] },
    branding: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const projectId = ref('');

const exportTypeOptions = [
    { value: 'projects', label: 'Proiecte' },
    { value: 'quotes', label: 'Oferte si devize' },
    { value: 'materials', label: 'Materiale' },
    { value: 'resource-comparison', label: 'Materiale & Avize comparative' },
    { value: 'costs', label: 'Costuri' },
    { value: 'teams', label: 'Echipe si responsabilitati' },
    { value: 'tasks', label: 'Taskuri generale' },
    { value: 'defects', label: 'Defecte' },
    { value: 'wbs', label: 'WBS etape' },
    { value: 'equipment', label: 'Utilaje pe etape' },
    { value: 'documents', label: 'Documente financiare' },
    { value: 'stage-reports', label: 'Rapoarte etapa' },
    { value: 'stage-tasks', label: 'Taskuri etapa' },
    { value: 'stage-progress', label: 'Progres etape' },
];

const exportTypeLabels = Object.fromEntries(exportTypeOptions.map((option) => [option.value, option.label]));

const exportDomains = [
    { key: 'all', label: 'Toate rapoartele', description: 'Toate exporturile enterprise disponibile' },
    { key: 'project', label: 'Proiect & Etape', description: 'WBS, progres si rapoarte de etapa' },
    { key: 'operational', label: 'Operare & Echipe', description: 'Taskuri, rapoarte si responsabilitati' },
    { key: 'resources', label: 'Resurse & Utilaje', description: 'Materiale, utilaje si comparatii de resurse' },
    { key: 'quality', label: 'Calitate', description: 'Defecte si fluxuri de verificare' },
    { key: 'financial', label: 'Financiar', description: 'Oferte, costuri si documente financiare' },
];

const exportCards = [
    {
        route: 'exports.projects',
        title: 'Proiecte',
        description: 'Lista proiecte cu status si buget',
        domain: 'project',
    },
    {
        route: 'exports.wbs',
        title: 'WBS Etape',
        description: 'Ierarhie etape cu nivel, parinte, contractor si progres',
        domain: 'project',
    },
    {
        route: 'exports.stage-progress',
        title: 'Progres etape',
        description: 'Progres, status, contractori si ordonare pe etape',
        domain: 'project',
    },
    {
        route: 'exports.tasks',
        title: 'Taskuri',
        description: 'Taskuri cu status, prioritate si responsabil',
        domain: 'operational',
    },
    {
        route: 'exports.stage-tasks',
        title: 'Taskuri etapa',
        description: 'Taskuri operationale pe etape cu responsabili',
        domain: 'operational',
    },
    {
        route: 'exports.stage-reports',
        title: 'Rapoarte etapa',
        description: 'Progres raportat, activitati si probleme pe etape',
        domain: 'operational',
    },
    {
        route: 'exports.teams',
        title: 'Echipe si responsabilitati',
        description: 'Lideri, membri, roluri, alocari',
        domain: 'operational',
    },
    {
        route: 'exports.materials',
        title: 'Materiale',
        description: 'Catalog complet cu preturi si furnizori',
        domain: 'resources',
    },
    {
        route: 'exports.equipment',
        title: 'Utilaje pe etape',
        description: 'Rezervari utilaje cu interval, cantitate si cost estimat',
        domain: 'resources',
    },
    {
        route: 'exports.defects',
        title: 'Defecte',
        description: 'Snag list cu prioritati si termene',
        domain: 'quality',
    },
    {
        route: 'exports.quotes',
        title: 'Oferte si Devize',
        description: 'Versiuni, status, totaluri financiare',
        domain: 'financial',
    },
    {
        route: 'exports.costs',
        title: 'Costuri',
        description: 'Comparativ buget proiect vs oferte',
        domain: 'financial',
    },
    {
        route: 'exports.documents',
        title: 'Documente financiare',
        description: 'Contracte, facturi, devize si oferte pe proiect/etapa',
        domain: 'financial',
    },
];

const activeExportDomain = ref('all');

const domainCounts = computed(() => exportCards.reduce((accumulator, card) => {
    accumulator[card.domain] = (accumulator[card.domain] || 0) + 1;
    accumulator.all = (accumulator.all || 0) + 1;
    return accumulator;
}, {}));

const activeDomainInfo = computed(() => exportDomains.find((domain) => domain.key === activeExportDomain.value) ?? null);

const filteredExportCards = computed(() => {
    if (activeExportDomain.value === 'all') {
        return exportCards;
    }

    return exportCards.filter((card) => card.domain === activeExportDomain.value);
});

const reportTemplates = [
    {
        key: 'project-complete',
        label: 'Proiect complet',
        description: 'Imagine completa proiect: WBS, taskuri, defecte, progres, documente.',
        types: ['projects', 'wbs', 'tasks', 'defects', 'stage-progress', 'documents'],
        previewType: 'projects',
        primaryCsvRoute: 'exports.projects',
    },
    {
        key: 'financial-complete',
        label: 'Financiar complet',
        description: 'Costuri, documente financiare, devize si comparativ buget.',
        types: ['costs', 'documents', 'quotes', 'stage-progress'],
        previewType: 'costs',
        primaryCsvRoute: 'exports.costs',
    },
    {
        key: 'quality-defects',
        label: 'Calitate & Defecte',
        description: 'Defecte, taskuri corective si progres operational pe etape.',
        types: ['defects', 'tasks', 'stage-tasks', 'stage-reports'],
        previewType: 'defects',
        primaryCsvRoute: 'exports.defects',
    },
    {
        key: 'equipment-resources',
        label: 'Utilaje & Resurse',
        description: 'Rezervari utilaje, materiale si stadiu pe etape.',
        types: ['equipment', 'materials', 'stage-progress'],
        previewType: 'equipment',
        primaryCsvRoute: 'exports.equipment',
    },
    {
        key: 'tasks-progress',
        label: 'Taskuri & Progres',
        description: 'Taskuri generale + taskuri etapa + progres executie.',
        types: ['tasks', 'stage-tasks', 'stage-progress'],
        previewType: 'tasks',
        primaryCsvRoute: 'exports.tasks',
    },
    {
        key: 'cost-vs-budget',
        label: 'Cost vs Buget',
        description: 'Comparativ costuri reale/estimate, devieri si risc bugetar.',
        types: ['costs', 'quotes', 'documents'],
        previewType: 'costs',
        primaryCsvRoute: 'exports.costs',
    },
    {
        key: 'materials-notes',
        label: 'Materiale & Avize',
        description: 'Materiale, documente financiare si trasabilitate avize.',
        types: ['materials', 'documents', 'stage-reports'],
        previewType: 'materials',
        primaryCsvRoute: 'exports.materials',
    },
    {
        key: 'materials-comparison',
        label: 'Materiale & Avize comparative',
        description: 'Comandat, livrat, receptionat, consumat si diferente din avize.',
        types: ['resource-comparison'],
        previewType: 'resource-comparison',
    },
];

const quickRangeOptions = [
    { value: 'today', label: 'Today' },
    { value: 'last_7d', label: 'Last 7 days' },
    { value: 'last_30d', label: 'Last 30 days' },
    { value: 'last_90d', label: 'Last 90 days' },
    { value: 'this_year', label: 'This year' },
];

const filters = reactive({
    from: props.filters?.from || '',
    to: props.filters?.to || '',
    quick_range: props.filters?.quick_range || '',
    project_id: props.filters?.project_id ? String(props.filters.project_id) : '',
    team_id: props.filters?.team_id ? String(props.filters.team_id) : '',
    status: Array.isArray(props.filters?.status) ? props.filters.status.join(',') : (props.filters?.status || ''),
    priority: Array.isArray(props.filters?.priority) ? props.filters.priority.join(',') : (props.filters?.priority || ''),
    assignee_ids: Array.isArray(props.filters?.assignee_ids) ? props.filters.assignee_ids.join(',') : (props.filters?.assignee_ids || ''),
    global_search: props.filters?.global_search || props.filters?.q || '',
    include_inactive: Boolean(props.filters?.include_inactive),
});

function setQuickRange(value) {
    filters.quick_range = filters.quick_range === value ? '' : value;
}

const subscriptionForm = useForm({
    name: '',
    export_type: 'projects',
    format: 'xlsx',
    frequency: 'weekly',
    schedule_time: '08:00',
    schedule_weekday: 1,
    recipientsText: '',
});

const previewState = reactive({
    export_type: 'projects',
    loading: false,
    error: '',
    result: null,
});

const resourceComparisonSummary = computed(() => previewState.result?.summary ?? {
    orders_count: 0,
    ordered_quantity_total: 0,
    received_quantity_total: 0,
    consumed_quantity_total: 0,
    returned_quantity_total: 0,
    document_links_total: 0,
    document_difference_total: 0,
    received_delta_total: 0,
});

const isResourceComparisonPreview = computed(() => previewState.result?.export_type === 'resource-comparison');

const previewComparisonRows = computed(() => (isResourceComparisonPreview.value ? (previewState.result?.sample ?? []) : []));

function routeWithFilters(routeName) {
    return route(routeName, {
        ...filters,
        q: filters.global_search || undefined,
    });
}

function templateWorkbookUrl(template) {
    return route('exports.workbook', {
        ...filters,
        q: filters.global_search || undefined,
        types: template.types.join(','),
    });
}

function templatePdfUrl(template) {
    return route('exports.managerial-pdf', {
        ...filters,
        q: filters.global_search || undefined,
        types: template.types.join(','),
    });
}

function previewTemplate(template) {
    previewState.export_type = template.previewType;
    generatePreview();
}

async function generatePreview() {
    previewState.loading = true;
    previewState.error = '';

    try {
        const url = route('exports.preview', {
            export_type: previewState.export_type,
            ...filters,
            q: filters.global_search || undefined,
        });

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Preview indisponibil pentru filtrele curente.');
        }

        previewState.result = await response.json();
    } catch (error) {
        previewState.error = error instanceof Error ? error.message : 'Nu am putut genera preview-ul.';
        previewState.result = null;
    } finally {
        previewState.loading = false;
    }
}

function downloadProjectPackage() {
    if (!projectId.value) return;
    window.location.href = route('exports.project.package', projectId.value);
}

function createSubscription() {
    const recipients = subscriptionForm.recipientsText
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item.length > 0);

    subscriptionForm.transform(() => ({
        name: subscriptionForm.name,
        export_type: subscriptionForm.export_type,
        format: subscriptionForm.format,
        frequency: subscriptionForm.frequency,
        schedule_time: subscriptionForm.schedule_time,
        schedule_weekday: subscriptionForm.frequency === 'weekly' ? subscriptionForm.schedule_weekday : null,
        recipients,
        filters: { ...filters },
    })).post(route('exports.subscriptions.store'), {
        onSuccess: () => {
            subscriptionForm.reset();
            subscriptionForm.schedule_time = '08:00';
            subscriptionForm.frequency = 'weekly';
            subscriptionForm.format = 'xlsx';
            subscriptionForm.export_type = 'projects';
            subscriptionForm.schedule_weekday = 1;
            router.reload({ only: ['subscriptions', 'recentLogs'] });
        },
    });
}

function runSubscription(subscription) {
    router.post(route('exports.subscriptions.run', subscription.id), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['subscriptions', 'recentLogs'] }),
    });
}

function toggleSubscription(subscription) {
    router.patch(route('exports.subscriptions.toggle', subscription.id), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['subscriptions', 'recentLogs'] }),
    });
}

function formatDateTime(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('ro-RO');
}

function formatNumber(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    const numeric = Number(value);
    if (Number.isNaN(numeric)) {
        return String(value);
    }

    return new Intl.NumberFormat('ro-RO', {
        maximumFractionDigits: 2,
    }).format(numeric);
}

function previewSampleEntries(sample) {
    if (!sample || typeof sample !== 'object') {
        return [];
    }

    return Object.entries(sample)
        .slice(0, 6)
        .map(([key, value]) => ({
            key,
            value: Array.isArray(value)
                ? value.join(', ')
                : value === null || value === undefined || value === ''
                    ? '-'
                    : typeof value === 'object'
                        ? JSON.stringify(value)
                        : String(value),
        }));
}

function formatExportType(value) {
    return exportTypeLabels[value] ?? value;
}

function formatActiveFilters(filtersPayload) {
    if (!filtersPayload || typeof filtersPayload !== 'object') {
        return '-';
    }

    const parts = Object.entries(filtersPayload)
        .map(([key, value]) => {
            if (Array.isArray(value)) {
                return `${key}: ${value.join(',')}`;
            }

            if (typeof value === 'object' && value !== null) {
                return `${key}: ${JSON.stringify(value)}`;
            }

            return `${key}: ${value}`;
        });

    return parts.length > 0 ? parts.join(' | ') : '-';
}
</script>
