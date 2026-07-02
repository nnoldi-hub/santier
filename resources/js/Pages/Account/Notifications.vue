<template>
    <AppLayout title="Centru Notificari">
        <div class="max-w-7xl mx-auto space-y-6">
            <section class="rounded-2xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Centru notificari</h2>
                <p class="text-sm text-gray-500 mt-1">Istoric complet notificari, cu filtre pe status si eveniment.</p>

                <form class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3" @submit.prevent="applyFilters">
                    <select v-model="form.status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="all">Toate</option>
                        <option value="unread">Necitite</option>
                        <option value="read">Citite</option>
                    </select>

                    <select v-model="form.event" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Toate evenimentele</option>
                        <option v-for="event in eventOptions" :key="event" :value="event">{{ event }}</option>
                    </select>

                    <input v-model="form.search" type="text" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Cauta in payload (project/user/actor)" />

                    <div class="flex items-center gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-white text-sm font-medium hover:bg-slate-800">Aplica</button>
                        <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-50" @click="resetFilters">Reset</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Notificari</h3>
                    <button
                        type="button"
                        class="rounded-lg border border-orange-300 bg-orange-50 px-3 py-1.5 text-xs text-orange-700 hover:bg-orange-100"
                        @click="markAllRead"
                    >
                        Marcheaza toate ca citite
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs text-gray-600 uppercase tracking-wide">
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">Eveniment</th>
                                <th class="px-4 py-3">Mesaj</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Actiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in notifications.data" :key="item.id" class="border-t border-gray-100 align-top">
                                <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ formatDate(item.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                        {{ item.data?.event || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700">
                                    <div class="font-medium text-gray-800">{{ item.data?.project_name || 'Notificare' }}</div>
                                    <div>Rol: {{ (item.data?.role_key || 'N/A').toUpperCase() }}</div>
                                    <div>Operat de: {{ item.data?.actor_name || 'Sistem' }}</div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 font-medium"
                                        :class="item.read_at ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                    >
                                        {{ item.read_at ? 'Citita' : 'Necitita' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="flex items-center gap-2">
                                        <Link
                                            v-if="item.data?.project_id"
                                            :href="route('projects.show', item.data.project_id)"
                                            class="text-blue-600 hover:text-blue-700"
                                        >
                                            Proiect
                                        </Link>
                                        <button
                                            v-if="!item.read_at"
                                            type="button"
                                            class="text-gray-600 hover:text-gray-800"
                                            @click="markRead(item.id)"
                                        >
                                            Marcheaza citita
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="notifications.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Nu exista notificari pentru filtrele curente.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-gray-200 flex items-center justify-between text-sm">
                    <div class="text-gray-500">Pagina {{ notifications.current_page }} din {{ notifications.last_page }}</div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-50"
                            :disabled="!notifications.prev_page_url"
                            @click="goTo(notifications.prev_page_url)"
                        >
                            Inapoi
                        </button>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50 disabled:opacity-50"
                            :disabled="!notifications.next_page_url"
                            @click="goTo(notifications.next_page_url)"
                        >
                            Inainte
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    notifications: { type: Object, required: true },
    filters: { type: Object, required: true },
    eventOptions: { type: Array, default: () => [] },
});

const form = useForm({
    status: props.filters.status || 'all',
    event: props.filters.event || '',
    search: props.filters.search || '',
});

function applyFilters() {
    form.get(route('account.notifications.index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    form.status = 'all';
    form.event = '';
    form.search = '';
    applyFilters();
}

function goTo(url) {
    if (!url) return;

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
}

function markRead(id) {
    router.patch(route('notifications.read', id), {}, {
        preserveScroll: true,
        onSuccess: () => applyFilters(),
    });
}

function markAllRead() {
    router.patch(route('notifications.read-all'), {}, {
        preserveScroll: true,
        onSuccess: () => applyFilters(),
    });
}

function formatDate(value) {
    if (!value) return '-';

    const parsed = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return parsed.toLocaleString('ro-RO');
}
</script>
