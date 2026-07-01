<template>
    <div class="min-h-screen bg-gray-100 flex">
        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 bg-black/40 z-40 md:hidden"
            @click="isMobileMenuOpen = false"
        />

        <aside
            class="w-64 bg-gray-900 text-white flex flex-col fixed inset-y-0 z-50 transition-transform duration-200 ease-out"
            :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <div class="h-16 flex items-center px-6 border-b border-gray-700">
                <span class="text-2xl mr-2">🏗️</span>
                <span class="text-xl font-bold text-orange-400">Santier</span>
            </div>

            <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
                <NavItem :href="routeOrFallback('dashboard')" :disabled="routeMissing('dashboard')" icon="📊" label="Dashboard" />

                <div class="pt-3">
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('projects')" @click="toggleSection('projects')">
                        <span>Proiecte</span>
                        <span :class="['transition-transform', sections.projects ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.projects" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('projects.index')" :disabled="routeMissing('projects.index')" icon="🏠" label="Toate proiectele" />
                        <NavItem :href="routeOrFallback('wbs.index')" :disabled="routeMissing('wbs.index')" icon="🧩" label="Etape de lucru (WBS)" />
                        <NavItem :href="routeOrFallback('contractors.index')" :disabled="routeMissing('contractors.index')" icon="🤝" label="Subcontractori alocati" />
                        <NavItem :href="routeOrFallback('equipment.index')" :disabled="routeMissing('equipment.index')" icon="🚜" label="Utilaje rezervate" />
                        <NavItem :href="routeOrFallback('documents.index')" :disabled="routeMissing('documents.index')" icon="📁" label="Documente proiect" />
                        <NavItem :href="routeOrFallback('stage-reports.index')" :disabled="routeMissing('stage-reports.index')" icon="📌" label="Rapoarte de progres" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('planning')" @click="toggleSection('planning')">
                        <span>Planificare</span>
                        <span :class="['transition-transform', sections.planning ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.planning" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('gantt.index')" :disabled="routeMissing('gantt.index')" icon="📅" label="Gantt" />
                        <NavItem :href="routeOrFallback('tasks.index')" :disabled="routeMissing('tasks.index')" icon="✅" label="Taskuri generale" />
                        <NavItem :href="routeOrFallback('stage-tasks.index')" :disabled="routeMissing('stage-tasks.index')" icon="🧱" label="Taskuri pe etapa" />
                        <NavItem :href="routeOrFallback('team-calendar.index')" :disabled="routeMissing('team-calendar.index')" icon="🗓️" label="Calendar echipe" />
                        <NavItem :href="routeOrFallback('equipment-calendar.index')" :disabled="routeMissing('equipment-calendar.index')" icon="🛠️" label="Calendar utilaje" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('resources')" @click="toggleSection('resources')">
                        <span>Resurse</span>
                        <span :class="['transition-transform', sections.resources ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.resources" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('teams.index')" :disabled="routeMissing('teams.index')" icon="👷" label="Echipe interne" />
                        <NavItem :href="routeOrFallback('contractors.index')" :disabled="routeMissing('contractors.index')" icon="🤝" label="Subcontractori" />
                        <NavItem :href="routeOrFallback('equipment.index')" :disabled="routeMissing('equipment.index')" icon="🚜" label="Utilaje" />
                        <NavItem :href="routeOrFallback('materials.index')" :disabled="routeMissing('materials.index')" icon="📦" label="Materiale" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('financial')" @click="toggleSection('financial')">
                        <span>Financiar</span>
                        <span :class="['transition-transform', sections.financial ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.financial" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('quotes.index')" :disabled="routeMissing('quotes.index')" icon="📋" label="Oferte / Devize" />
                        <NavItem :href="routeOrFallback('documents.index')" :disabled="routeMissing('documents.index')" icon="🧾" label="Documente financiare" />
                        <NavItem :href="routeOrFallback('material-invoices.index')" :disabled="routeMissing('material-invoices.index')" icon="🧱" label="Facturi materiale" />
                        <NavItem :href="routeOrFallback('situatii-lucrari.index')" :disabled="routeMissing('situatii-lucrari.index')" icon="📊" label="Situatii de lucrari" />
                        <NavItem :href="routeOrFallback('cost-tracking.index')" :disabled="routeMissing('cost-tracking.index')" icon="💸" label="Cost tracking" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('quality')" @click="toggleSection('quality')">
                        <span>Calitate</span>
                        <span :class="['transition-transform', sections.quality ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.quality" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('defects.index')" :disabled="routeMissing('defects.index')" icon="🔧" label="Defecte / Snag list" />
                        <NavItem :href="routeOrFallback('quality-checks.index')" :disabled="routeMissing('quality-checks.index')" icon="✅" label="Verificari" />
                        <NavItem :href="routeOrFallback('rapoarte-calitate.index')" :disabled="routeMissing('rapoarte-calitate.index')" icon="📑" label="Rapoarte calitate" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('documents')" @click="toggleSection('documents')">
                        <span>Documente</span>
                        <span :class="['transition-transform', sections.documents ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.documents" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('documents.index')" :disabled="routeMissing('documents.index')" icon="🗂️" label="Registru documente" />
                        <NavItem :href="routeOrFallback('procese-verbale.index')" :disabled="routeMissing('procese-verbale.index')" icon="📄" label="Procese verbale" />
                        <NavItem :href="routeOrFallback('documente-subcontractori.index')" :disabled="routeMissing('documente-subcontractori.index')" icon="🤝" label="Documente subcontractori" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('reporting')" @click="toggleSection('reporting')">
                        <span>Raportare</span>
                        <span :class="['transition-transform', sections.reporting ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.reporting" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('exports.index')" :disabled="routeMissing('exports.index')" icon="📤" label="Exporturi" />
                        <NavItem :href="routeOrFallback('analytics.index')" :disabled="routeMissing('analytics.index')" icon="📈" label="Analytics Funnel" />
                        <NavItem :href="routeOrFallback('stage-progress.index')" :disabled="routeMissing('stage-progress.index')" icon="📌" label="Progres etape" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('account')" @click="toggleSection('account')">
                        <span>Cont / Setari</span>
                        <span :class="['transition-transform', sections.account ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.account" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('profile.edit')" :disabled="routeMissing('profile.edit')" icon="👤" label="Profil" />
                        <NavItem :href="routeOrFallback('pilot-invites.index')" :disabled="routeMissing('pilot-invites.index')" icon="🤝" label="Firme pilot" />
                        <NavItem :href="routeOrFallback('billing.index')" :disabled="routeMissing('billing.index')" icon="💳" label="Plan & Billing" />
                    </div>
                </div>
            </nav>

            <div class="border-t border-gray-700 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-sm font-bold">
                        {{ userInitials }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium truncate">{{ $page.props.auth.user.name }}</div>
                        <div class="text-xs text-gray-400 truncate">{{ $page.props.auth.user.email }}</div>
                        <div v-if="$page.props.billing?.planLabel" class="text-[11px] text-orange-300 mt-0.5 truncate">Plan: {{ $page.props.billing.planLabel }}</div>
                    </div>
                    <Link :href="route('logout')" method="post" as="button" class="text-gray-400 hover:text-white text-xs">
                        Iesi
                    </Link>
                </div>
            </div>
        </aside>

        <div class="flex-1 md:ml-64 flex flex-col min-h-screen">
            <header class="h-16 bg-white border-b border-gray-200 flex items-center px-6 sticky top-0 z-40 shadow-sm">
                <button
                    type="button"
                    class="mr-3 md:hidden h-9 w-9 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                >
                    ☰
                </button>
                <h1 class="text-lg font-semibold text-gray-800">{{ title }}</h1>
                <div
                    v-if="isDemoMode"
                    class="ml-4 hidden md:flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs text-amber-800"
                    :title="demoModeDescription"
                >
                    <span class="font-semibold">{{ demoModeLabel }}</span>
                    <span class="text-amber-700">Scenariu evaluare activ</span>
                </div>
                <div class="ml-auto flex items-center gap-3">
                    <button class="relative text-gray-400 hover:text-gray-600">
                        <span class="text-xl">🔔</span>
                    </button>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div v-if="isDemoMode" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 md:hidden">
                    <div class="font-semibold">{{ demoModeLabel }}</div>
                    <div class="text-amber-800">{{ demoModeDescription }}</div>
                </div>
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
                    <span>✅ {{ $page.props.flash.success }}</span>
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center justify-between">
                    <span>⚠️ {{ $page.props.flash.error }}</span>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavItem from '@/Components/NavItem.vue';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const isMobileMenuOpen = ref(false);

const SECTION_STATE_KEY = 'santier.sidebar.sections';
const defaultSections = {
    projects: true,
    planning: true,
    resources: true,
    financial: true,
    quality: true,
    documents: false,
    reporting: true,
    account: true,
};

const sections = reactive({ ...defaultSections });

const sectionRoutes = {
    projects: ['projects.index', 'wbs.index', 'contractors.index', 'equipment.index', 'documents.index', 'stage-reports.index'],
    planning: ['gantt.index', 'tasks.index', 'stage-tasks.index', 'team-calendar.index', 'equipment-calendar.index'],
    resources: ['teams.index', 'contractors.index', 'equipment.index', 'materials.index'],
    financial: ['quotes.index', 'documents.index', 'material-invoices.index', 'situatii-lucrari.index', 'cost-tracking.index'],
    quality: ['defects.index', 'quality-checks.index', 'rapoarte-calitate.index'],
    documents: ['documents.index', 'procese-verbale.index', 'documente-subcontractori.index'],
    reporting: ['exports.index', 'analytics.index', 'stage-progress.index'],
    account: ['profile.edit', 'pilot-invites.index', 'billing.index'],
};

const toggleSection = (name) => {
    sections[name] = !sections[name];
};

const sectionHasActiveRoute = (name) => {
    const routes = sectionRoutes[name] ?? [];

    return routes.some((routeName) => {
        if (!hasRoute(routeName)) {
            return false;
        }

        return page.url.startsWith(route(routeName));
    });
};

const sectionButtonClass = (name) => (
    sectionHasActiveRoute(name)
        ? 'bg-gray-800 text-white'
        : 'text-gray-300 hover:bg-gray-800'
);

const hasRoute = (name) => {
    if (typeof route !== 'function') {
        return false;
    }

    const routeFn = route();
    return typeof routeFn.has === 'function' && routeFn.has(name);
};

const routeOrFallback = (name) => (hasRoute(name) ? route(name) : '#');
const routeMissing = (name) => !hasRoute(name);

const userInitials = computed(() => {
    const name = page.props.auth?.user?.name || '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const isDemoMode = computed(() => page.props.demoMode?.enabled === true);
const demoModeLabel = computed(() => page.props.demoMode?.label || 'Mod demo');
const demoModeDescription = computed(() => page.props.demoMode?.description || 'Vezi doar datele din scenariul demo.');

onMounted(() => {
    try {
        const saved = localStorage.getItem(SECTION_STATE_KEY);
        if (!saved) {
            return;
        }

        const parsed = JSON.parse(saved);
        Object.keys(defaultSections).forEach((key) => {
            if (typeof parsed[key] === 'boolean') {
                sections[key] = parsed[key];
            }
        });
    } catch {
        // Ignore corrupted local storage values.
    }
});

watch(
    sections,
    (current) => {
        localStorage.setItem(SECTION_STATE_KEY, JSON.stringify(current));
    },
    { deep: true },
);

watch(
    () => page.url,
    () => {
        isMobileMenuOpen.value = false;
    },
);
</script>