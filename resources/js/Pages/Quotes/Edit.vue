<template>
    <AppLayout :title="'Editeaza oferta: ' + quote.title">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link :href="route('quotes.index')" class="text-gray-400 hover:text-gray-600 text-sm">← Inapoi</Link>
                <h2 class="text-xl font-semibold text-gray-800">Editeaza oferta</h2>
            </div>

            <section class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Ajutor contextual</div>
                        <div class="text-sm font-semibold text-amber-900">Checklist rapid inainte de salvare/trimitere</div>
                    </div>
                    <button type="button" @click="showQuoteHelp = !showQuoteHelp" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">
                        {{ showQuoteHelp ? 'Ascunde ghid' : 'Arata ghid' }}
                    </button>
                </div>

                <div v-if="showQuoteHelp" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div v-for="guide in quoteHelpGuides" :key="guide.title" class="rounded-lg border border-amber-200 bg-white p-3">
                        <div class="text-sm font-semibold text-slate-900">{{ guide.title }}</div>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="item in guide.items" :key="item" class="flex gap-2 text-xs text-slate-700">
                                <span class="mt-1 h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                        <Link :href="guide.href" class="mt-3 inline-flex rounded-md border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                            {{ guide.cta }}
                        </Link>
                    </div>
                </div>
            </section>

            <form @submit.prevent="submit" class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
                <section class="rounded-xl border border-sky-200 bg-sky-50 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-sky-900">Onboarding ofertare: progres completare</h3>
                        <span class="text-xs font-semibold" :class="onboardingPercent === 100 ? 'text-emerald-700' : 'text-sky-800'">
                            {{ onboardingCompletedCount }} / {{ onboardingChecks.length }}
                        </span>
                    </div>
                    <div class="h-2 rounded-full bg-sky-100 overflow-hidden">
                        <div class="h-full transition-all" :class="onboardingPercent === 100 ? 'bg-emerald-500' : 'bg-sky-500'" :style="{ width: `${onboardingPercent}%` }"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div v-for="check in onboardingChecks" :key="check.label" class="flex items-center gap-2 text-xs">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full" :class="check.done ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                {{ check.done ? '✓' : '•' }}
                            </span>
                            <span :class="check.done ? 'text-emerald-800' : 'text-slate-700'">{{ check.label }}</span>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">A. Informatii generale</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titlu oferta *</label>
                        <input v-model="form.title" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        <p v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Proiect *</label>
                            <select v-model="form.project_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">— Selecteaza proiect —</option>
                                <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                            </select>
                            <p v-if="form.errors.project_id" class="text-red-500 text-xs mt-1">{{ form.errors.project_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                            <input v-model="quoteMeta.client_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Nume client / beneficiar" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                            <select v-model="form.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="draft">Ciorna</option>
                                <option value="sent">Trimisa</option>
                                <option value="accepted">Acceptata</option>
                                <option value="rejected">Respinsa</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total net (RON) *</label>
                            <input v-model="form.total_net" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50" readonly />
                            <p class="text-[11px] text-gray-500 mt-1">Calculat automat.</p>
                            <p v-if="form.errors.total_net" class="text-red-500 text-xs mt-1">{{ form.errors.total_net }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Discount %</label>
                            <input v-model="form.discount_pct" type="number" min="0" max="100" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">TVA %</label>
                            <input v-model="form.tva_pct" type="number" min="0" max="100" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marja minima %</label>
                            <input v-model="form.min_margin_pct" type="number" min="0" max="100" step="0.01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Versiune oferta</label>
                            <select v-model="quoteMeta.package_tier" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="standard">Standard</option>
                                <option value="premium">Premium</option>
                                <option value="luxury">Luxury</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Valabil pana la</label>
                        <input v-model="form.valid_until" type="date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" />
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">B. Etape oferta (WBS)</h3>
                        <button type="button" @click="addStage()" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">+ Adauga etapa</button>
                    </div>

                    <div v-for="(stage, stageIndex) in stages" :key="stage.uid" class="rounded-lg border border-gray-200 p-3 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div class="md:col-span-2">
                                <label class="block text-[11px] text-gray-600 mb-1">Denumire etapa</label>
                                <input v-model="stage.name" type="text" class="w-full border border-gray-300 rounded px-2 py-2 text-sm" />
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-600 mb-1">Zile estimate</label>
                                <input v-model="stage.duration_days" type="number" min="0" step="1" class="w-full border border-gray-300 rounded px-2 py-2 text-sm" />
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" @click="addStageItem(stage, 'material')" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">+ Material</button>
                            <button type="button" @click="addStageItem(stage, 'labor')" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">+ Manopera</button>
                            <button type="button" @click="addStageItem(stage, 'equipment')" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">+ Utilaj</button>
                            <button type="button" @click="addStageItem(stage, 'custom')" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">+ Custom</button>
                            <button type="button" @click="removeStage(stageIndex)" class="text-xs text-red-600 hover:text-red-700 ml-auto">Sterge etapa</button>
                        </div>

                        <div v-if="stage.items.length === 0" class="text-xs text-gray-500">Fara articole in etapa.</div>

                        <div v-for="(item, itemIndex) in stage.items" :key="item.uid" class="grid grid-cols-1 lg:grid-cols-12 gap-2 border border-gray-100 rounded-lg p-3 bg-gray-50">
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] text-gray-600 mb-1">Tip</label>
                                <select v-model="item.item_type" class="w-full border border-gray-300 rounded px-2 py-2 text-xs" @change="onItemTypeChange(item)">
                                    <option value="material">Material</option>
                                    <option value="equipment">Utilaj</option>
                                    <option value="labor">Manopera</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div v-if="item.item_type === 'material' || item.item_type === 'equipment'" class="lg:col-span-3">
                                <label class="block text-[11px] text-gray-600 mb-1">Catalog</label>
                                <select v-model="item.reference_id" class="w-full border border-gray-300 rounded px-2 py-2 text-xs" @change="onReferenceChange(item)">
                                    <option :value="null">— Selecteaza —</option>
                                    <option v-for="option in catalogOptions(item.item_type)" :key="option.id" :value="option.id">{{ option.name }}</option>
                                </select>
                            </div>
                            <div class="lg:col-span-3">
                                <label class="block text-[11px] text-gray-600 mb-1">Denumire</label>
                                <input v-model="item.name" type="text" class="w-full border border-gray-300 rounded px-2 py-2 text-xs" />
                            </div>
                            <div class="lg:col-span-1">
                                <label class="block text-[11px] text-gray-600 mb-1">UM</label>
                                <input v-model="item.unit" type="text" class="w-full border border-gray-300 rounded px-2 py-2 text-xs" />
                            </div>
                            <div class="lg:col-span-1">
                                <label class="block text-[11px] text-gray-600 mb-1">Cant</label>
                                <input v-model="item.quantity" type="number" min="0.001" step="0.001" class="w-full border border-gray-300 rounded px-2 py-2 text-xs" />
                            </div>
                            <div class="lg:col-span-1">
                                <label class="block text-[11px] text-gray-600 mb-1">Cost UM</label>
                                <input v-model="item.cost_unit_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-2 py-2 text-xs" />
                            </div>
                            <div class="lg:col-span-1">
                                <label class="block text-[11px] text-gray-600 mb-1">Vanzare UM</label>
                                <input v-model="item.sell_unit_price" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-2 py-2 text-xs" />
                            </div>
                            <div class="lg:col-span-12 flex items-center justify-between text-xs text-gray-600 pt-1">
                                <div>Linie: cost {{ formatMoney(lineCost(item)) }} / vanzare {{ formatMoney(lineSell(item)) }}</div>
                                <button type="button" @click="stage.items.splice(itemIndex, 1)" class="text-red-600 hover:text-red-700">Sterge</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">C. Cantitati inteligente</h3>
                        <button type="button" @click="generateSmartItems" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">Genereaza articole</button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div><label class="block text-xs text-gray-600 mb-1">Suprafata pereti (mp)</label><input v-model="smartInputs.walls_area" type="number" min="0" step="0.1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" /></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Suprafata pardoseala (mp)</label><input v-model="smartInputs.floor_area" type="number" min="0" step="0.1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" /></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Suprafata faianta (mp)</label><input v-model="smartInputs.tile_area" type="number" min="0" step="0.1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" /></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Numar prize</label><input v-model="smartInputs.outlets_count" type="number" min="0" step="1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" /></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Numar corpuri iluminat</label><input v-model="smartInputs.lights_count" type="number" min="0" step="1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" /></div>
                        <div><label class="block text-xs text-gray-600 mb-1">Numar usi</label><input v-model="smartInputs.doors_count" type="number" min="0" step="1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" /></div>
                    </div>

                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-amber-800 mb-1">Strategie materiale</label>
                                <select v-model="quoteMeta.material_mode" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white">
                                    <option value="capped_allowance">Cu materiale plafonate (incluse in oferta)</option>
                                    <option value="client_supplied">Fara materiale (cumparate de client)</option>
                                </select>
                            </div>
                            <div class="text-xs text-amber-900 flex items-end">
                                Prize/intrerupatoare si corpuri premium raman la selectie ulterioara, nu sunt plafonate automat.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div><label class="block text-xs text-amber-800 mb-1">Plafon parchet (RON/mp)</label><input v-model="quoteMeta.material_caps.parquet_max_per_mp" type="number" min="0" step="0.01" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" /></div>
                            <div><label class="block text-xs text-amber-800 mb-1">Plafon gresie/faianta (RON/mp)</label><input v-model="quoteMeta.material_caps.tile_max_per_mp" type="number" min="0" step="0.01" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" /></div>
                            <div><label class="block text-xs text-amber-800 mb-1">Plafon vopsea + glet (RON/mp)</label><input v-model="quoteMeta.material_caps.paint_max_per_mp" type="number" min="0" step="0.01" class="w-full border border-amber-300 rounded px-3 py-2 text-sm bg-white" /></div>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">D. Costuri indirecte</h3>
                    <p class="text-xs text-gray-500">Markup automat pe vanzare: {{ formatNumber(overheadMarkupPct) }}%</p>
                    <div class="space-y-2">
                        <div v-for="(cost, index) in indirectCosts" :key="cost.uid" class="grid grid-cols-1 md:grid-cols-8 gap-2 items-end">
                            <div class="md:col-span-5">
                                <label class="block text-xs text-gray-600 mb-1">Categorie</label>
                                <input v-model="cost.label" type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs text-gray-600 mb-1">Valoare (RON)</label>
                                <input v-model="cost.amount" type="number" min="0" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                            </div>
                            <div>
                                <button type="button" @click="indirectCosts.splice(index, 1)" class="text-xs text-red-600 hover:text-red-700">Sterge</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="addIndirectCost()" class="text-xs border border-gray-300 rounded px-2 py-1 hover:bg-gray-50">+ Adauga cost indirect</button>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">E. Optiuni suplimentare</h3>
                    <div class="space-y-2">
                        <label v-for="option in extraOptions" :key="option.key" class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                            <span class="text-sm text-gray-700">{{ option.label }}</span>
                            <div class="flex items-center gap-2">
                                <input v-model="option.enabled" type="checkbox" class="rounded border-gray-300" />
                                <input v-model="option.amount" type="number" min="0" step="0.01" class="w-32 border border-gray-300 rounded px-2 py-1 text-xs" />
                            </div>
                        </label>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">F. Timeline estimat</h3>
                    <div class="text-sm text-gray-700">Total estimat proiect: <strong>{{ totalTimelineDays }} zile</strong></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <div v-for="stage in stages" :key="stage.uid" class="text-sm text-gray-600">{{ stage.name || 'Etapa fara nume' }}: {{ Number(stage.duration_days || 0) }} zile</div>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">G. Rezumat costuri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div class="rounded-lg border border-gray-200 p-3"><div class="text-xs text-gray-500">Materiale</div><div class="text-sm font-semibold text-gray-800">{{ formatMoney(summaryByType.material) }}</div></div>
                        <div class="rounded-lg border border-gray-200 p-3"><div class="text-xs text-gray-500">Manopera</div><div class="text-sm font-semibold text-gray-800">{{ formatMoney(summaryByType.labor) }}</div></div>
                        <div class="rounded-lg border border-gray-200 p-3"><div class="text-xs text-gray-500">Utilaje</div><div class="text-sm font-semibold text-gray-800">{{ formatMoney(summaryByType.equipment) }}</div></div>
                        <div class="rounded-lg border border-gray-200 p-3"><div class="text-xs text-gray-500">Indirecte + optiuni</div><div class="text-sm font-semibold text-gray-800">{{ formatMoney(indirectAndOptionsTotal) }}</div></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="rounded-lg border border-gray-200 p-3"><div class="text-xs text-gray-500">Cost total</div><div class="text-sm font-semibold text-gray-800">{{ formatMoney(totalCost) }}</div></div>
                        <div class="rounded-lg border border-gray-200 p-3"><div class="text-xs text-gray-500">Profit total</div><div class="text-sm font-semibold text-gray-800">{{ formatMoney(totalProfit) }}</div></div>
                        <div class="rounded-lg border p-3" :class="marginOk ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                            <div class="text-xs" :class="marginOk ? 'text-emerald-700' : 'text-red-700'">Marja totala</div>
                            <div class="text-sm font-semibold" :class="marginOk ? 'text-emerald-800' : 'text-red-800'">{{ formatNumber(totalMarginPct) }}%</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs text-slate-600">Deviz 1 (fara materiale, client achizitioneaza)</div>
                            <div class="text-sm font-semibold text-slate-900">{{ formatMoney(estimateWithoutMaterialsTotal) }}</div>
                        </div>
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3">
                            <div class="text-xs text-indigo-700">Deviz 2 (cu materiale plafonate)</div>
                            <div class="text-sm font-semibold text-indigo-900">{{ formatMoney(estimateWithCappedMaterialsTotal) }}</div>
                            <div class="text-[11px] text-indigo-700 mt-1">Materiale plafonate estimate: {{ formatMoney(cappedMaterialsEstimateTotal) }}</div>
                        </div>
                    </div>

                    <div class="overflow-auto">
                        <table class="min-w-full text-xs border border-gray-200">
                            <thead class="bg-gray-50"><tr><th class="px-2 py-1 text-left">Etapa</th><th class="px-2 py-1 text-right">Cost</th><th class="px-2 py-1 text-right">Vanzare</th><th class="px-2 py-1 text-right">Profit</th><th class="px-2 py-1 text-right">Marja</th></tr></thead>
                            <tbody>
                                <tr v-for="row in stageSummaryRows" :key="row.stage" class="border-t border-gray-100">
                                    <td class="px-2 py-1">{{ row.stage }}</td>
                                    <td class="px-2 py-1 text-right">{{ formatMoney(row.cost) }}</td>
                                    <td class="px-2 py-1 text-right">{{ formatMoney(row.sell) }}</td>
                                    <td class="px-2 py-1 text-right">{{ formatMoney(row.profit) }}</td>
                                    <td class="px-2 py-1 text-right" :class="row.margin_pct >= Number(form.min_margin_pct || 0) ? 'text-emerald-700' : 'text-red-700'">{{ formatNumber(row.margin_pct) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 p-4 space-y-4">
                    <h3 class="text-sm font-semibold text-gray-800">H. Note & conditii</h3>
                    <textarea v-model="form.notes" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                </section>

                <section class="rounded-xl border border-gray-200 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">I. Actiuni</h3>
                    <div v-if="documentApprovalsEnabled" class="mb-3 rounded-lg border px-3 py-2 text-xs flex flex-wrap items-center justify-between gap-2" :class="needsInternalApproval ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800'">
                        <span v-if="needsInternalApproval">Oferta necesita aprobare interna inainte de trimitere.</span>
                        <span v-else>Aprobata intern{{ internalApprovedByName ? ' de ' + internalApprovedByName : '' }}.</span>
                        <button
                            v-if="canApproveInternally && needsInternalApproval"
                            type="button"
                            @click="approveInternally"
                            class="rounded-lg bg-amber-600 text-white px-3 py-1.5 text-xs font-medium hover:bg-amber-700"
                        >
                            Aproba oferta
                        </button>
                        <button
                            v-else-if="canApproveInternally"
                            type="button"
                            @click="unapproveInternally"
                            class="rounded-lg border border-emerald-300 text-emerald-700 px-3 py-1.5 text-xs font-medium hover:bg-emerald-100"
                        >
                            Anuleaza aprobarea
                        </button>
                    </div>
                    <div v-if="onboardingPercent < 100" class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        Oferta nu este complet pregatita pentru trimitere ({{ onboardingCompletedCount }}/{{ onboardingChecks.length }}).
                        Lipsesc: {{ onboardingIncompleteLabels.join(', ') }}.
                    </div>
                    <div class="flex flex-wrap gap-3 items-center justify-between">
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-orange-600 disabled:opacity-50 transition">
                                {{ form.processing ? 'Se salveaza...' : 'Salveaza modificarile' }}
                            </button>
                            <a :href="route('quotes.pdf', quote.id)" target="_blank" class="border border-indigo-200 text-indigo-700 px-6 py-2 rounded-lg text-sm hover:bg-indigo-50">Export PDF</a>
                            <button
                                type="button"
                                @click="sendToClient"
                                :disabled="needsInternalApproval"
                                :title="needsInternalApproval ? 'Oferta trebuie aprobata intern inainte de trimitere.' : ''"
                                class="px-6 py-2 rounded-lg text-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="onboardingPercent === 100
                                    ? 'border border-blue-200 text-blue-700 hover:bg-blue-50'
                                    : 'border border-amber-300 text-amber-800 bg-amber-50 hover:bg-amber-100'"
                            >
                                Trimite clientului
                            </button>
                            <button type="button" @click="convertToProject" class="border border-emerald-200 text-emerald-700 px-6 py-2 rounded-lg text-sm hover:bg-emerald-50">Conversie in proiect</button>
                            <button type="button" @click="saveAsTemplate" class="border border-violet-200 text-violet-700 px-6 py-2 rounded-lg text-sm hover:bg-violet-50">Salveaza ca sablon</button>
                            <Link :href="route('quotes.index')" class="border border-gray-300 text-gray-600 px-6 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Anuleaza</Link>
                        </div>
                        <button type="button" @click="remove" class="text-red-500 hover:text-red-700 text-sm px-3 py-2 rounded-lg hover:bg-red-50 transition">Sterge oferta</button>
                    </div>
                </section>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    quote: Object,
    projects: Array,
    materials: { type: Array, default: () => [] },
    equipment: { type: Array, default: () => [] },
    documentApprovalsEnabled: { type: Boolean, default: false },
    canApproveInternally: { type: Boolean, default: false },
    internalApprovedByName: { type: String, default: null },
});

const needsInternalApproval = computed(() => props.documentApprovalsEnabled && !props.quote.internal_approved_at);

function approveInternally() {
    if (confirm(`Aprobi intern oferta "${props.quote.title}"? Dupa aprobare va putea fi trimisa clientului.`)) {
        router.patch(route('quotes.approve-internally', props.quote.id), {}, { preserveScroll: true });
    }
}

function unapproveInternally() {
    if (confirm(`Anulezi aprobarea interna a ofertei "${props.quote.title}"? Trimiterea catre client va fi blocata din nou.`)) {
        router.patch(route('quotes.unapprove-internally', props.quote.id), {}, { preserveScroll: true });
    }
}

const showQuoteHelp = ref(true);

const quoteHelpGuides = [
    {
        title: '1) Verificari de baza',
        items: [
            'Titlul, proiectul si statusul ofertei sunt corecte.',
            'Data de valabilitate este actualizata.',
            'Cantitatile smart sunt conforme cu masuratorile curente.',
        ],
        href: route('help.index'),
        cta: 'Vezi ghid complet',
    },
    {
        title: '2) Pret si marja',
        items: [
            'Compara cele doua scenarii: fara materiale vs plafonat.',
            'Confirma marja minima pe etape si total.',
            'Verifica indirectele/optiunile inainte de PDF.',
        ],
        href: route('quotes.index'),
        cta: 'Vezi toate ofertele',
    },
    {
        title: '3) Livrare catre client',
        items: [
            'Genereaza PDF si verifica brandingul documentului.',
            'Dupa verificare, foloseste Trimite clientului.',
            'Daca oferta este aprobata, converteste in proiect.',
        ],
        href: route('documents.branding.index'),
        cta: 'Configurare documente',
    },
];

const metadata = typeof props.quote?.meta === 'object' && props.quote?.meta !== null ? props.quote.meta : {};
const persistedStages = Array.isArray(metadata.stages) ? metadata.stages : [];

const groupedFromItems = new Map();

(Array.isArray(props.quote?.items) ? props.quote.items : []).forEach((item, index) => {
    const stageOrder = Number(item.stage_order ?? 0);
    const stageName = item.stage_name || `Etapa ${stageOrder + 1}`;
    const key = `${stageOrder}::${stageName}`;

    if (!groupedFromItems.has(key)) {
        groupedFromItems.set(key, {
            stage_order: stageOrder,
            name: stageName,
            duration_days: 1,
            items: [],
        });
    }

    groupedFromItems.get(key).items.push({
        uid: `item-${index}-${Date.now()}`,
        item_type: item.item_type || 'custom',
        reference_id: item.reference_id || null,
        name: item.name || '',
        unit: item.unit || 'buc',
        quantity: Number(item.quantity || 0),
        cost_unit_price: Number(item.cost_unit_price || 0),
        sell_unit_price: Number(item.sell_unit_price || 0),
    });
});

const stages = reactive(Array.from(groupedFromItems.values())
    .sort((a, b) => a.stage_order - b.stage_order)
    .map((stage, index) => {
        const fallback = persistedStages.find((row) => Number(row.stage_order ?? -1) === stage.stage_order);
        return {
            uid: `stage-${index}-${Date.now()}`,
            name: fallback?.name || stage.name,
            duration_days: Number(fallback?.duration_days ?? 1),
            items: stage.items,
        };
    }));

if (stages.length === 0) {
    stages.push({ uid: `stage-empty-${Date.now()}`, name: 'Etapa generala', duration_days: 1, items: [] });
}

const quoteMeta = reactive({
    package_tier: metadata.package_tier || 'standard',
    client_name: metadata.client_name || '',
    material_mode: String(metadata.material_mode || 'capped_allowance'),
    material_caps: {
        parquet_max_per_mp: Number(metadata.material_caps?.parquet_max_per_mp || 100),
        tile_max_per_mp: Number(metadata.material_caps?.tile_max_per_mp || 80),
        paint_max_per_mp: Number(metadata.material_caps?.paint_max_per_mp || 18),
    },
});

const smartInputs = reactive({
    walls_area: Number(metadata.smart_inputs?.walls_area || 0),
    floor_area: Number(metadata.smart_inputs?.floor_area || 0),
    tile_area: Number(metadata.smart_inputs?.tile_area || 0),
    outlets_count: Number(metadata.smart_inputs?.outlets_count || 0),
    lights_count: Number(metadata.smart_inputs?.lights_count || 0),
    doors_count: Number(metadata.smart_inputs?.doors_count || 0),
});

const indirectCosts = reactive(Array.isArray(metadata.indirect_costs) && metadata.indirect_costs.length > 0
    ? metadata.indirect_costs.map((cost, index) => ({
        uid: `ind-${index}-${Date.now()}`,
        label: cost.label || 'Cost indirect',
        amount: Number(cost.amount || 0),
    }))
    : [
        { uid: `ind-1-${Date.now()}`, label: 'Transport', amount: 0 },
        { uid: `ind-2-${Date.now()}`, label: 'Consumabile', amount: 0 },
        { uid: `ind-3-${Date.now()}`, label: 'Protectii', amount: 0 },
    ]);

const defaultOptions = [
    { key: 'parchet_premium', label: 'Parchet premium' },
    { key: 'sanitare_premium', label: 'Obiecte sanitare premium' },
    { key: 'spoturi_led', label: 'Spoturi LED' },
    { key: 'usi_mdf_lemn', label: 'Usi MDF vs lemn' },
    { key: 'mobilier_bucatarie', label: 'Mobilier bucatarie' },
];

const savedOptions = Array.isArray(metadata.optional_features) ? metadata.optional_features : [];
const extraOptions = reactive(defaultOptions.map((base) => {
    const saved = savedOptions.find((row) => row.key === base.key);
    return {
        key: base.key,
        label: base.label,
        enabled: Boolean(saved?.enabled),
        amount: Number(saved?.amount || 0),
    };
}));

const form = useForm({
    project_id: props.quote.project_id ? String(props.quote.project_id) : '',
    title: props.quote.title || '',
    status: props.quote.status || 'draft',
    valid_until: props.quote.valid_until || '',
    discount_pct: props.quote.discount_pct ?? 0,
    tva_pct: props.quote.tva_pct ?? 21,
    min_margin_pct: Number(metadata.min_margin_pct || 12),
    notes: props.quote.notes || '',
    total_net: props.quote.total_net ?? 0,
    items: [],
    quote_meta: {},
});

const materialsMap = computed(() => new Map((props.materials || []).map((item) => [Number(item.id), item])));
const equipmentMap = computed(() => new Map((props.equipment || []).map((item) => [Number(item.id), item])));

const flattenedStageItems = computed(() => {
    const rows = [];

    stages.forEach((stage, stageIndex) => {
        stage.items.forEach((item, itemIndex) => {
            rows.push({
                ...item,
                stage_name: stage.name || `Etapa ${stageIndex + 1}`,
                stage_order: stageIndex,
                sort_order: stageIndex * 1000 + itemIndex,
            });
        });
    });

    return rows;
});

const indirectAndOptionsRows = computed(() => {
    const rows = [];
    const safeMarginPct = Math.min(Math.max(Number(overheadMarkupPct.value || 0), 1), 95);
    const markupFactor = 1 / (1 - (safeMarginPct / 100));

    indirectCosts.forEach((cost, index) => {
        const amount = Number(cost.amount || 0);
        if (amount <= 0) return;
        rows.push({
            item_type: 'custom',
            reference_id: null,
            name: cost.label || 'Cost indirect',
            stage_name: 'Costuri indirecte',
            stage_order: 900,
            unit: 'set',
            quantity: 1,
            cost_unit_price: amount,
            sell_unit_price: round2(amount * markupFactor),
            sort_order: 900000 + index,
        });
    });

    extraOptions.forEach((option, index) => {
        const amount = Number(option.amount || 0);
        if (!option.enabled || amount <= 0) return;
        rows.push({
            item_type: 'custom',
            reference_id: null,
            name: option.label,
            stage_name: 'Optiuni suplimentare',
            stage_order: 901,
            unit: 'set',
            quantity: 1,
            cost_unit_price: amount,
            sell_unit_price: round2(amount * markupFactor),
            sort_order: 901000 + index,
        });
    });

    return rows;
});

const allItems = computed(() => [...flattenedStageItems.value, ...indirectAndOptionsRows.value]);

const onboardingChecks = computed(() => {
    const hasProject = String(form.project_id || '').trim() !== '';
    const hasTitle = String(form.title || '').trim().length >= 6;
    const hasOperationalItems = flattenedStageItems.value.length > 0;
    const hasSmartInput = [
        Number(smartInputs.walls_area || 0),
        Number(smartInputs.floor_area || 0),
        Number(smartInputs.tile_area || 0),
        Number(smartInputs.outlets_count || 0),
        Number(smartInputs.lights_count || 0),
        Number(smartInputs.doors_count || 0),
    ].some((value) => value > 0);
    const hasMarginConfigured = Number(form.min_margin_pct || 0) > 0;
    const hasMaterialStrategy = ['capped_allowance', 'client_supplied'].includes(String(quoteMeta.material_mode || ''));

    return [
        { label: 'Proiect selectat', done: hasProject },
        { label: 'Titlu oferta completat', done: hasTitle },
        { label: 'Articole adaugate in etape', done: hasOperationalItems },
        { label: 'Cantitati smart introduse', done: hasSmartInput },
        { label: 'Marja minima configurata', done: hasMarginConfigured },
        { label: 'Strategie materiale aleasa', done: hasMaterialStrategy },
    ];
});

const onboardingCompletedCount = computed(() => onboardingChecks.value.filter((check) => check.done).length);
const onboardingPercent = computed(() => {
    if (onboardingChecks.value.length === 0) return 0;
    return Math.round((onboardingCompletedCount.value / onboardingChecks.value.length) * 100);
});
const onboardingIncompleteLabels = computed(() => onboardingChecks.value.filter((check) => !check.done).map((check) => check.label));

const totalCost = computed(() => allItems.value.reduce((sum, item) => sum + lineCost(item), 0));
const totalNetBeforeDiscount = computed(() => allItems.value.reduce((sum, item) => sum + lineSell(item), 0));
const effectiveNet = computed(() => {
    const discount = Number(form.discount_pct || 0);
    return Math.max(totalNetBeforeDiscount.value - totalNetBeforeDiscount.value * (discount / 100), 0);
});
const totalMarginPct = computed(() => {
    if (effectiveNet.value <= 0) return 0;
    return ((effectiveNet.value - totalCost.value) / effectiveNet.value) * 100;
});
const totalProfit = computed(() => Math.max(effectiveNet.value - totalCost.value, 0));
const marginOk = computed(() => totalMarginPct.value >= Number(form.min_margin_pct || 0));
const overheadMarkupPct = computed(() => Math.max(Number(form.min_margin_pct || 12), 10));

const totalTimelineDays = computed(() => stages.reduce((sum, stage) => sum + Number(stage.duration_days || 0), 0));

const summaryByType = computed(() => {
    const result = { material: 0, labor: 0, equipment: 0 };

    flattenedStageItems.value.forEach((item) => {
        if (item.item_type === 'material') result.material += lineSell(item);
        if (item.item_type === 'labor') result.labor += lineSell(item);
        if (item.item_type === 'equipment') result.equipment += lineSell(item);
    });

    return result;
});

const indirectAndOptionsTotal = computed(() => indirectAndOptionsRows.value.reduce((sum, row) => sum + lineSell(row), 0));

const cappedMaterialsEstimateTotal = computed(() => {
    const walls = Number(smartInputs.walls_area || 0);
    const floor = Number(smartInputs.floor_area || 0);
    const tile = Number(smartInputs.tile_area || 0);
    const paintCap = Number(quoteMeta.material_caps.paint_max_per_mp || 0);
    const parquetCap = Number(quoteMeta.material_caps.parquet_max_per_mp || 0);
    const tileCap = Number(quoteMeta.material_caps.tile_max_per_mp || 0);

    return round2((walls * paintCap) + (floor * parquetCap) + (tile * tileCap));
});

const estimateWithoutMaterialsTotal = computed(() => {
    const nonMaterialItemsSell = allItems.value
        .filter((item) => item.item_type !== 'material')
        .reduce((sum, item) => sum + lineSell(item), 0);

    return round2(nonMaterialItemsSell);
});

const estimateWithCappedMaterialsTotal = computed(() => round2(estimateWithoutMaterialsTotal.value + cappedMaterialsEstimateTotal.value));

const stageSummaryRows = computed(() => {
    const map = new Map();

    flattenedStageItems.value.forEach((item) => {
        const stage = item.stage_name || 'General';
        if (!map.has(stage)) {
            map.set(stage, { stage, cost: 0, sell: 0, profit: 0, margin_pct: 0 });
        }

        const row = map.get(stage);
        row.cost += lineCost(item);
        row.sell += lineSell(item);
        row.profit = row.sell - row.cost;
        row.margin_pct = row.sell > 0 ? (row.profit / row.sell) * 100 : 0;
    });

    return Array.from(map.values());
});

function addStage() {
    stages.push({
        uid: `stage-${Date.now()}-${Math.random()}`,
        name: 'Etapa noua',
        duration_days: 1,
        items: [],
    });
}

function removeStage(stageIndex) {
    if (stages.length <= 1) {
        stages[0].items = [];
        stages[0].name = 'Etapa generala';
        stages[0].duration_days = 1;
        return;
    }

    stages.splice(stageIndex, 1);
}

function addStageItem(stage, type = 'custom') {
    stage.items.push({
        uid: `item-${Date.now()}-${Math.random()}`,
        item_type: type,
        reference_id: null,
        name: '',
        unit: type === 'equipment' ? 'ora' : 'buc',
        quantity: 1,
        cost_unit_price: 0,
        sell_unit_price: 0,
    });
}

function addIndirectCost() {
    indirectCosts.push({ uid: `ind-${Date.now()}-${Math.random()}`, label: 'Alt cost indirect', amount: 0 });
}

function onItemTypeChange(item) {
    item.reference_id = null;
    item.name = '';
    item.unit = item.item_type === 'equipment' ? 'ora' : 'buc';
    item.cost_unit_price = 0;
    item.sell_unit_price = 0;
}

function catalogOptions(type) {
    if (type === 'material') return props.materials || [];
    if (type === 'equipment') return props.equipment || [];
    return [];
}

function onReferenceChange(item) {
    const referenceId = Number(item.reference_id || 0);

    if (item.item_type === 'material') {
        const material = materialsMap.value.get(referenceId);
        if (!material) return;
        item.name = material.name;
        item.unit = material.unit || 'buc';
        item.cost_unit_price = Number(material.unit_price || 0);
        item.sell_unit_price = Number(material.unit_price || 0);
        return;
    }

    if (item.item_type === 'equipment') {
        const equipment = equipmentMap.value.get(referenceId);
        if (!equipment) return;
        item.name = equipment.name;
        item.unit = 'ora';
        item.cost_unit_price = Number(equipment.cost_per_hour || 0);
        item.sell_unit_price = Number(equipment.cost_per_hour || 0);
    }
}

function findStageByKeywords(keywords) {
    const normalized = Array.isArray(keywords) ? keywords.map((word) => String(word || '').toLowerCase()) : [];
    const stage = stages.find((entry) => {
        const stageName = String(entry.name || '').toLowerCase();
        return normalized.some((word) => stageName.includes(word));
    });

    return stage || stages[0];
}

function generateSmartItems() {
    if (stages.length === 0) {
        addStage();
    }

    const finishesStage = findStageByKeywords(['glet', 'vopsitor', 'tencuieli', 'finis']);
    const flooringStage = findStageByKeywords(['pardos', 'sapa']);
    const sanitaryStage = findStageByKeywords(['sanitar', 'gresie', 'faianta', 'placari']);
    const electricalStage = findStageByKeywords(['electr', 'prize', 'iluminat']);
    const carpentryStage = findStageByKeywords(['tamplar', 'usi']);

    const wallsArea = Number(smartInputs.walls_area || 0);
    const floorArea = Number(smartInputs.floor_area || 0);
    const tileArea = Number(smartInputs.tile_area || 0);
    const outlets = Number(smartInputs.outlets_count || 0);
    const lights = Number(smartInputs.lights_count || 0);
    const doors = Number(smartInputs.doors_count || 0);

    if (wallsArea > 0) {
        finishesStage.items.push(buildSmartItem('labor', 'Manopera glet + vopsitorie', 'mp', wallsArea, 45));
        if (quoteMeta.material_mode === 'capped_allowance') {
            finishesStage.items.push(buildSmartItem('material', 'Vopsea + glet (plafon)', 'mp', wallsArea, Number(quoteMeta.material_caps.paint_max_per_mp || 0)));
        }
    }

    if (floorArea > 0) {
        flooringStage.items.push(buildSmartItem('labor', 'Montaj pardoseala', 'mp', floorArea, 55));
        if (quoteMeta.material_mode === 'capped_allowance') {
            flooringStage.items.push(buildSmartItem('material', 'Parchet + accesorii (plafon)', 'mp', floorArea, Number(quoteMeta.material_caps.parquet_max_per_mp || 0)));
        }
    }

    if (tileArea > 0) {
        sanitaryStage.items.push(buildSmartItem('labor', 'Montaj gresie/faianta', 'mp', tileArea, 70));
        if (quoteMeta.material_mode === 'capped_allowance') {
            sanitaryStage.items.push(buildSmartItem('material', 'Gresie/faianta + adeziv (plafon)', 'mp', tileArea, Number(quoteMeta.material_caps.tile_max_per_mp || 0)));
        }
    }

    if (outlets > 0) {
        electricalStage.items.push(buildSmartItem('labor', 'Montaj prize', 'buc', outlets, 40));
    }

    if (lights > 0) {
        electricalStage.items.push(buildSmartItem('labor', 'Montaj corpuri iluminat', 'buc', lights, 45));
    }

    if (doors > 0) {
        carpentryStage.items.push(buildSmartItem('labor', 'Montaj usi', 'buc', doors, 180));
    }
}

function buildSmartItem(type, name, unit, quantity, sellPrice) {
    const q = Number(quantity || 0);
    const price = Number(sellPrice || 0);

    return {
        uid: `smart-${Date.now()}-${Math.random()}`,
        item_type: type,
        reference_id: null,
        name,
        unit,
        quantity: q,
        cost_unit_price: round2(price * 0.8),
        sell_unit_price: round2(price),
    };
}

function lineCost(item) {
    return Number(item.quantity || 0) * Number(item.cost_unit_price || 0);
}

function lineSell(item) {
    return Number(item.quantity || 0) * Number(item.sell_unit_price || 0);
}

function formatMoney(value) {
    return new Intl.NumberFormat('ro-RO', { style: 'currency', currency: 'RON', maximumFractionDigits: 2 }).format(Number(value || 0));
}

function formatNumber(value) {
    return new Intl.NumberFormat('ro-RO', { maximumFractionDigits: 2 }).format(Number(value || 0));
}

function round2(value) {
    return Math.round(Number(value || 0) * 100) / 100;
}

function submit() {
    form.total_net = Number(totalNetBeforeDiscount.value || 0);

    form.items = allItems.value.map((item) => ({
        item_type: item.item_type,
        reference_id: item.reference_id,
        name: item.name,
        stage_name: item.stage_name || 'General',
        stage_order: Number(item.stage_order || 0),
        unit: item.unit,
        quantity: Number(item.quantity || 0),
        cost_unit_price: round2(item.cost_unit_price),
        sell_unit_price: round2(item.sell_unit_price),
    }));

    form.quote_meta = {
        package_tier: quoteMeta.package_tier,
        client_name: quoteMeta.client_name,
        material_mode: quoteMeta.material_mode,
        material_caps: {
            parquet_max_per_mp: Number(quoteMeta.material_caps.parquet_max_per_mp || 0),
            tile_max_per_mp: Number(quoteMeta.material_caps.tile_max_per_mp || 0),
            paint_max_per_mp: Number(quoteMeta.material_caps.paint_max_per_mp || 0),
        },
        smart_inputs: {
            walls_area: Number(smartInputs.walls_area || 0),
            floor_area: Number(smartInputs.floor_area || 0),
            tile_area: Number(smartInputs.tile_area || 0),
            outlets_count: Number(smartInputs.outlets_count || 0),
            lights_count: Number(smartInputs.lights_count || 0),
            doors_count: Number(smartInputs.doors_count || 0),
        },
        indirect_costs: indirectCosts.map((cost) => ({ label: cost.label, amount: Number(cost.amount || 0) })),
        optional_features: extraOptions.map((option) => ({ key: option.key, label: option.label, enabled: Boolean(option.enabled), amount: Number(option.amount || 0) })),
        stages: stages.map((stage, stageIndex) => ({
            stage_order: stageIndex,
            name: stage.name,
            duration_days: Number(stage.duration_days || 0),
            items_count: stage.items.length,
        })),
        timeline_days_total: totalTimelineDays.value,
        stage_summary_preview: stageSummaryRows.value,
        pricing_scenarios: {
            labor_only_total: estimateWithoutMaterialsTotal.value,
            capped_materials_total: cappedMaterialsEstimateTotal.value,
            with_capped_materials_total: estimateWithCappedMaterialsTotal.value,
            active_quote_total: round2(totalNetBeforeDiscount.value || 0),
        },
    };

    form.patch(route('quotes.update', props.quote.id));
}

function remove() {
    if (confirm(`Stergi oferta "${props.quote.title}"?`)) {
        router.delete(route('quotes.destroy', props.quote.id));
    }
}

function sendToClient() {
    if (needsInternalApproval.value) {
        return;
    }

    if (onboardingPercent.value < 100) {
        const warningMessage = `Oferta este incompleta (${onboardingCompletedCount.value}/${onboardingChecks.value.length}).\n\nLipsesc:\n- ${onboardingIncompleteLabels.value.join('\n- ')}\n\nVrei sa trimiti totusi clientului?`;
        if (!confirm(warningMessage)) {
            return;
        }
    }

    if (confirm(`Marchezi oferta "${props.quote.title}" ca trimisa clientului?`)) {
        router.patch(route('quotes.send', props.quote.id));
    }
}

function convertToProject() {
    if (confirm(`Convertesti oferta "${props.quote.title}" in executie proiect?`)) {
        router.post(route('quotes.convert', props.quote.id));
    }
}

function saveAsTemplate() {
    if (confirm(`Salvezi oferta "${props.quote.title}" ca sablon reutilizabil?`)) {
        router.post(route('quotes.template.store', props.quote.id));
    }
}
</script>
