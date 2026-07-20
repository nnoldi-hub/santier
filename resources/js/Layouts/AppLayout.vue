<template>
    <div class="min-h-screen bg-gray-100 flex">
        <div
            v-if="isMobileMenuOpen"
            class="fixed inset-0 bg-black/40 z-40 md:hidden"
            @click="isMobileMenuOpen = false"
        />

        <aside
            class="w-64 bg-[#1E1E1E] text-white flex flex-col fixed inset-y-0 z-50 transition-transform duration-200 ease-out"
            :class="isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        >
            <div class="h-24 flex items-center px-4 border-b border-gray-700 gap-3">
                <img src="/brand/logo_icon.png" alt="Modulia" class="h-10 w-10 shrink-0" />
                <div class="min-w-0">
                    <div class="text-sm font-extrabold tracking-wide text-white truncate">{{ platformAppName }}</div>
                    <div class="text-[11px] text-gray-300 truncate">Șantierul devine clar.</div>
                </div>
            </div>

            <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
                <div class="mb-1 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">
                    Acces rapid
                </div>
                <NavItem :href="routeOrFallback('dashboard')" :disabled="routeMissing('dashboard')" :icon="Squares2X2Icon" label="Dashboard" />
                <NavItem :href="routeOrFallback('help.index')" :disabled="routeMissing('help.index')" :icon="QuestionMarkCircleIcon" label="Ajutor" />

                <div v-if="isPlatformAdmin" class="pt-3">
                    <div class="mb-2 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-orange-300">
                        Superadmin / Platforma
                    </div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('platform')" @click="toggleSection('platform')">
                        <span>Control platforma</span>
                        <span :class="['transition-transform', sections.platform ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.platform" class="mt-1 space-y-1 pl-2 border-l border-orange-500/30">
                        <NavItem :href="routeOrFallback('admin.index')" :disabled="routeMissing('admin.index')" :icon="Cog6ToothIcon" label="Administrare" />
                        <NavItem :href="routeOrFallback('admin.commercial-dashboard.index')" :disabled="routeMissing('admin.commercial-dashboard.index')" :icon="PresentationChartLineIcon" label="Dashboard Comercial" />
                        <NavItem :href="routeOrFallback('admin.tenants.index')" :disabled="routeMissing('admin.tenants.index')" :icon="BuildingOffice2Icon" label="Firme & Abonamente" />
                        <NavItem :href="routeOrFallback('pilot-invites.index')" :disabled="routeMissing('pilot-invites.index')" :icon="RocketLaunchIcon" label="Firme pilot" />
                    </div>
                </div>

                <div class="pt-3">
                    <div class="px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">
                        Operare firma
                    </div>
                </div>

                <div class="pt-3">
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('projects')" @click="toggleSection('projects')">
                        <span>Proiecte</span>
                        <span :class="['transition-transform', sections.projects ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.projects" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('projects.index')" :disabled="routeMissing('projects.index')" :icon="FolderIcon" label="Toate proiectele" />
                        <NavItem :href="routeOrFallback('wbs.index')" :disabled="routeMissing('wbs.index')" :icon="PuzzlePieceIcon" label="Etape de lucru (WBS)" />
                        <NavItem :href="routeOrFallback('stage-reports.index')" :disabled="routeMissing('stage-reports.index')" :icon="PresentationChartBarIcon" label="Rapoarte de progres" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('planning')" @click="toggleSection('planning')">
                        <span>Planificare</span>
                        <span :class="['transition-transform', sections.planning ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.planning" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('gantt.index')" :disabled="routeMissing('gantt.index')" :icon="ViewColumnsIcon" label="Gantt" />
                        <NavItem :href="routeOrFallback('tasks.index')" :disabled="routeMissing('tasks.index')" :icon="CheckCircleIcon" label="Taskuri generale" />
                        <NavItem :href="routeOrFallback('stage-tasks.index')" :disabled="routeMissing('stage-tasks.index')" :icon="ListBulletIcon" label="Taskuri pe etapa" />
                        <NavItem :href="routeOrFallback('team-calendar.index')" :disabled="routeMissing('team-calendar.index')" :icon="CalendarDaysIcon" label="Calendar echipe" />
                        <NavItem :href="routeOrFallback('equipment-calendar.index')" :disabled="routeMissing('equipment-calendar.index')" :icon="CalendarDateRangeIcon" label="Calendar utilaje" />
                        <NavItem :href="routeOrFallback('resource-calendar.index')" :disabled="routeMissing('resource-calendar.index')" :icon="CalendarIcon" label="Calendar resurse" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('resources')" @click="toggleSection('resources')">
                        <span>Resurse</span>
                        <span :class="['transition-transform', sections.resources ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.resources" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <div class="px-3 pt-1 pb-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500">Retetar (calcul consum)</div>
                        <NavItem :href="routeOrFallback('recipes.index')" :disabled="routeMissing('recipes.index')" :icon="BeakerIcon" label="Retete" />
                        <div class="px-3 pt-2 pb-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-gray-500">Catalog resurse</div>
                        <NavItem :href="routeOrFallback('teams.index')" :disabled="routeMissing('teams.index')" :icon="UsersIcon" label="Echipe interne" />
                        <NavItem :href="routeOrFallback('contractors.index')" :disabled="routeMissing('contractors.index')" :icon="HandRaisedIcon" label="Subcontractori" />
                        <NavItem :href="routeOrFallback('equipment.index')" :disabled="routeMissing('equipment.index')" :icon="TruckIcon" label="Utilaje" />
                        <NavItem :href="routeOrFallback('trasabilitate-utilaje.index')" :disabled="routeMissing('trasabilitate-utilaje.index')" :icon="ScaleIcon" label="Trasabilitate utilaje" />
                        <NavItem :href="routeOrFallback('materials.index')" :disabled="routeMissing('materials.index')" :icon="CubeIcon" label="Materiale" />
                        <NavItem :href="routeOrFallback('suppliers.index')" :disabled="routeMissing('suppliers.index')" :icon="BuildingStorefrontIcon" label="Furnizori materiale" />
                        <NavItem :href="routeOrFallback('resource-orders.index')" :disabled="routeMissing('resource-orders.index')" :icon="ClipboardDocumentListIcon" label="Documente resurse" />
                        <NavItem :href="routeOrFallback('trasabilitate-materiale.index')" :disabled="routeMissing('trasabilitate-materiale.index')" :icon="ArrowsRightLeftIcon" label="Trasabilitate materiale" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('financial')" @click="toggleSection('financial')">
                        <span>Financiar</span>
                        <span :class="['transition-transform', sections.financial ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.financial" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('quotes.index')" :disabled="routeMissing('quotes.index')" :icon="ClipboardDocumentCheckIcon" label="Oferte / Devize" />
                        <NavItem :href="routeOrFallback('material-invoices.index')" :disabled="routeMissing('material-invoices.index')" :icon="ReceiptPercentIcon" label="Facturi materiale" />
                        <NavItem :href="routeOrFallback('situatii-lucrari.index')" :disabled="routeMissing('situatii-lucrari.index')" :icon="ChartBarSquareIcon" label="Situatii de lucrari" />
                        <NavItem :href="routeOrFallback('cost-tracking.index')" :disabled="routeMissing('cost-tracking.index')" :icon="BanknotesIcon" label="Cost tracking" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('quality')" @click="toggleSection('quality')">
                        <span>Calitate</span>
                        <span :class="['transition-transform', sections.quality ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.quality" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('defects.index')" :disabled="routeMissing('defects.index')" :icon="WrenchScrewdriverIcon" label="Defecte / Snag list" />
                        <NavItem :href="routeOrFallback('quality-checks.index')" :disabled="routeMissing('quality-checks.index')" :icon="CheckBadgeIcon" label="Verificari" />
                        <NavItem :href="routeOrFallback('rapoarte-calitate.index')" :disabled="routeMissing('rapoarte-calitate.index')" :icon="DocumentMagnifyingGlassIcon" label="Rapoarte calitate" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('documents')" @click="toggleSection('documents')">
                        <span>Documente</span>
                        <span :class="['transition-transform', sections.documents ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.documents" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('documents.index')" :disabled="routeMissing('documents.index')" :icon="ArchiveBoxIcon" label="Toate documentele" />
                        <NavItem :href="routeOrFallback('procese-verbale.index')" :disabled="routeMissing('procese-verbale.index')" :icon="DocumentCheckIcon" label="Procese verbale" />
                        <NavItem v-if="canManageDocumentBranding" :href="routeOrFallback('documents.branding.index')" :disabled="routeMissing('documents.branding.index')" :icon="PaintBrushIcon" label="Configurare documente" />
                        <NavItem :href="routeOrFallback('documente-subcontractori.index')" :disabled="routeMissing('documente-subcontractori.index')" :icon="FolderOpenIcon" label="Documente subcontractori" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('reporting')" @click="toggleSection('reporting')">
                        <span>Raportare</span>
                        <span :class="['transition-transform', sections.reporting ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.reporting" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('exports.index')" :disabled="routeMissing('exports.index')" :icon="ArrowDownTrayIcon" label="Exporturi" />
                        <NavItem :href="routeOrFallback('analytics.index')" :disabled="routeMissing('analytics.index')" :icon="FunnelIcon" label="Analytics Funnel" />
                        <NavItem :href="routeOrFallback('stage-progress.index')" :disabled="routeMissing('stage-progress.index')" :icon="PresentationChartBarIcon" label="Progres etape" />
                    </div>
                </div>

                <div>
                    <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold transition-colors" :class="sectionButtonClass('account')" @click="toggleSection('account')">
                        <span>Cont si organizatie</span>
                        <span :class="['transition-transform', sections.account ? 'rotate-0' : '-rotate-90']">▾</span>
                    </button>
                    <div v-show="sections.account" class="mt-1 space-y-1 pl-2 border-l border-gray-800">
                        <NavItem :href="routeOrFallback('account.users.index')" :disabled="routeMissing('account.users.index')" :icon="UsersIcon" label="Utilizatori" />
                        <NavItem :href="routeOrFallback('account.roles.index')" :disabled="routeMissing('account.roles.index')" :icon="ShieldCheckIcon" label="Roluri si permisiuni" />
                        <NavItem :href="routeOrFallback('account.audit.index')" :disabled="routeMissing('account.audit.index')" :icon="MagnifyingGlassIcon" label="Audit acces" />
                        <NavItem :href="routeOrFallback('account.notifications.index')" :disabled="routeMissing('account.notifications.index')" :icon="BellIcon" label="Notificari" />
                        <NavItem :href="routeOrFallback('profile.edit')" :disabled="routeMissing('profile.edit')" :icon="UserCircleIcon" label="Profil" />
                        <NavItem :href="routeOrFallback('billing.index')" :disabled="routeMissing('billing.index')" :icon="CreditCardIcon" label="Plan & Billing" />
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
                    class="mr-3 md:hidden h-9 w-9 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50 flex items-center justify-center"
                    @click="isMobileMenuOpen = !isMobileMenuOpen"
                >
                    <Icon :icon="Bars3Icon" size="h-5 w-5" />
                </button>
                <h1 class="text-lg font-semibold text-gray-800">{{ title }}</h1>
                <div class="ml-4 hidden lg:flex items-center rounded-full bg-[#0057FF]/10 px-3 py-1 text-xs font-semibold text-[#0057FF]">
                    Claritate in fiecare proiect.
                </div>
                <div
                    v-if="isDemoMode"
                    class="ml-4 hidden md:flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs text-amber-800"
                    :title="demoModeDescription"
                >
                    <span class="font-semibold">{{ demoModeLabel }}</span>
                    <span class="text-amber-700">Scenariu evaluare activ</span>
                </div>
                <div class="ml-auto flex items-center gap-3">
                    <div class="relative">
                        <button
                            class="relative text-gray-500 hover:text-gray-700"
                            @click="notificationsOpen = !notificationsOpen"
                            type="button"
                        >
                            <Icon :icon="BellIcon" size="h-6 w-6" />
                            <span
                                v-if="unreadNotificationCount > 0"
                                class="absolute -top-1.5 -right-2 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"
                            >
                                {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
                            </span>
                        </button>

                        <div
                            v-if="notificationsOpen"
                            class="absolute right-0 mt-2 w-96 max-w-[calc(100vw-2rem)] bg-white border border-gray-200 rounded-xl shadow-lg z-50"
                        >
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <div class="text-sm font-semibold text-gray-700">Notificari</div>
                                <div class="flex items-center gap-3">
                                    <Link
                                        v-if="hasRoute('account.notifications.index')"
                                        :href="route('account.notifications.index')"
                                        class="text-xs text-blue-600 hover:text-blue-700"
                                        @click="notificationsOpen = false"
                                    >
                                        Vezi toate
                                    </Link>
                                    <button
                                        v-if="unreadNotificationCount > 0"
                                        class="text-xs text-orange-600 hover:text-orange-700"
                                        @click="markAllNotificationsRead"
                                        type="button"
                                    >
                                        Marcheaza toate
                                    </button>
                                </div>
                            </div>

                            <div v-if="unreadNotifications.length === 0" class="px-4 py-6 text-sm text-gray-500 text-center">
                                Nu ai notificari necitite.
                            </div>

                            <div v-else class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                                <div v-for="notification in unreadNotifications" :key="notification.id" class="px-4 py-3 hover:bg-gray-50">
                                    <div class="text-xs text-gray-500 mb-1">{{ notificationTitle(notification) }}</div>
                                    <div class="text-sm text-gray-700">
                                        {{ notificationMessage(notification) }}
                                    </div>
                                    <div class="mt-2 flex items-center justify-between">
                                        <Link
                                            v-if="notificationLink(notification)"
                                            :href="notificationLink(notification)"
                                            class="text-xs text-blue-600 hover:text-blue-700"
                                            @click="notificationsOpen = false"
                                        >
                                            Deschide
                                        </Link>
                                        <span v-else class="text-xs text-gray-400">{{ notificationTimestamp(notification) }}</span>

                                        <button
                                            class="text-xs text-gray-500 hover:text-gray-700"
                                            @click="markNotificationRead(notification.id)"
                                            type="button"
                                        >
                                            Marcheaza citita
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">
                <div v-if="isDemoMode" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 md:hidden">
                    <div class="font-semibold">{{ demoModeLabel }}</div>
                    <div class="text-amber-800">{{ demoModeDescription }}</div>
                </div>
                <div v-if="$page.props.flash?.success" class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <Icon :icon="CheckCircleIcon" size="h-5 w-5 shrink-0" />
                    <span>{{ $page.props.flash.success }}</span>
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
                    <Icon :icon="ExclamationTriangleIcon" size="h-5 w-5 shrink-0" />
                    <span>{{ $page.props.flash.error }}</span>
                </div>
                <slot />
            </main>

            <footer class="border-t border-gray-200 bg-white/90 px-6 py-4">
                <div class="flex flex-col gap-2 text-sm text-gray-600 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-2">
                        <img src="/brand/logo_modulia.png" alt="Modulia" class="h-6 w-6 object-contain" />
                        <span>Modulia - Șantierul devine clar.</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="https://modulia.ro" target="_blank" rel="noopener" class="text-[#0057FF] hover:underline">modulia.ro</a>
                        <a href="mailto:suport@modulia.ro" class="text-[#0057FF] hover:underline">Suport</a>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import NavItem from '@/Components/NavItem.vue';
import Icon from '@/Components/Icon.vue';
import {
    ArchiveBoxIcon,
    ArrowDownTrayIcon,
    ArrowsRightLeftIcon,
    BanknotesIcon,
    Bars3Icon,
    BeakerIcon,
    BellIcon,
    BuildingOffice2Icon,
    BuildingStorefrontIcon,
    CalendarDateRangeIcon,
    CalendarDaysIcon,
    CalendarIcon,
    ChartBarSquareIcon,
    CheckBadgeIcon,
    CheckCircleIcon,
    ClipboardDocumentCheckIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
    CreditCardIcon,
    CubeIcon,
    DocumentCheckIcon,
    DocumentMagnifyingGlassIcon,
    ExclamationTriangleIcon,
    FolderIcon,
    FolderOpenIcon,
    FunnelIcon,
    HandRaisedIcon,
    ListBulletIcon,
    MagnifyingGlassIcon,
    PaintBrushIcon,
    PresentationChartBarIcon,
    PresentationChartLineIcon,
    PuzzlePieceIcon,
    QuestionMarkCircleIcon,
    ReceiptPercentIcon,
    RocketLaunchIcon,
    ScaleIcon,
    ShieldCheckIcon,
    Squares2X2Icon,
    TruckIcon,
    UserCircleIcon,
    UsersIcon,
    ViewColumnsIcon,
    WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    title: { type: String, default: '' },
});

const page = usePage();
const isMobileMenuOpen = ref(false);
const notificationsOpen = ref(false);

const SECTION_STATE_KEY = 'santier.sidebar.sections';
const defaultSections = {
    platform: true,
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
    platform: ['admin.index', 'admin.commercial-dashboard.index', 'admin.tenants.index', 'pilot-invites.index'],
    projects: ['projects.index', 'wbs.index', 'stage-reports.index'],
    planning: ['gantt.index', 'tasks.index', 'stage-tasks.index', 'team-calendar.index', 'equipment-calendar.index', 'resource-calendar.index'],
    resources: ['teams.index', 'contractors.index', 'equipment.index', 'materials.index', 'suppliers.index', 'recipes.index', 'resource-orders.index', 'trasabilitate-materiale.index', 'trasabilitate-utilaje.index'],
    financial: ['quotes.index', 'material-invoices.index', 'situatii-lucrari.index', 'cost-tracking.index'],
    quality: ['defects.index', 'quality-checks.index', 'rapoarte-calitate.index'],
    documents: ['documents.index', 'procese-verbale.index', 'documents.branding.index', 'documente-subcontractori.index'],
    reporting: ['exports.index', 'analytics.index', 'stage-progress.index'],
    account: ['account.users.index', 'account.roles.index', 'account.audit.index', 'account.notifications.index', 'profile.edit', 'billing.index'],
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

const platformAppName = computed(() => page.props.platform?.appName || 'Modulia');
const isPlatformAdmin = computed(() => Boolean(page.props.platform?.isAdmin));
const canManageDocumentBranding = computed(() => ['starter', 'pro', 'enterprise'].includes(page.props.billing?.plan || 'free'));

const userInitials = computed(() => {
    const name = page.props.auth?.user?.name || '';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const isDemoMode = computed(() => page.props.demoMode?.enabled === true);
const demoModeLabel = computed(() => page.props.demoMode?.label || 'Mod demo');
const demoModeDescription = computed(() => page.props.demoMode?.description || 'Vezi doar datele din scenariul demo.');
const unreadNotificationCount = computed(() => Number(page.props.notifications?.unreadCount || 0));
const unreadNotifications = computed(() => page.props.notifications?.items || []);

const notificationTitle = (notification) => {
    if (notification?.data?.title) {
        return notification.data.title;
    }

    const event = notification?.data?.event;
    return event ? `Eveniment: ${event}` : 'Notificare';
};

const notificationMessage = (notification) => {
    if (notification?.data?.message) {
        return notification.data.message;
    }

    const projectName = notification?.data?.project_name || 'Proiect';
    const roleKey = notification?.data?.role_key || 'N/A';
    const actorName = notification?.data?.actor_name || 'Sistem';

    return `${projectName} · Rol: ${roleKey.toUpperCase()} · Operat de: ${actorName}`;
};

const notificationProjectId = (notification) => Number(notification?.data?.project_id || 0) || null;

const notificationLink = (notification) => notification?.data?.url || (notificationProjectId(notification) ? route('projects.show', notificationProjectId(notification)) : null);

const notificationTimestamp = (notification) => {
    const value = notification?.created_at;
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleString('ro-RO');
};

const markNotificationRead = (notificationId) => {
    router.patch(route('notifications.read', notificationId), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['notifications', 'flash'],
    });
};

const markAllNotificationsRead = () => {
    router.patch(route('notifications.read-all'), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['notifications', 'flash'],
    });
};

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
        notificationsOpen.value = false;
    },
);
</script>