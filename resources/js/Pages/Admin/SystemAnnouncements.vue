<template>
    <AppLayout title="Anunturi globale">
        <div class="max-w-5xl mx-auto space-y-6">
            <section class="rounded-3xl border border-orange-200 bg-white p-6 shadow-sm">
                <div class="flex items-end justify-between gap-4">
                    <div><div class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-700">Comunicare platforma</div><h2 class="mt-2 text-3xl font-black text-slate-900">Anunturi globale</h2><p class="mt-2 text-sm text-slate-600">Publica mesaje vizibile utilizatorilor autentificati si dezactiveaza-le cand nu mai sunt necesare.</p></div>
                    <Link :href="route('admin.index')" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Dashboard Global</Link>
                </div>
            </section>

            <form class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm space-y-4" @submit.prevent="publish">
                <h3 class="text-lg font-bold text-slate-900">Publica un anunt</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input v-model="form.title" type="text" required maxlength="160" class="rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Titlu anunt" />
                    <select v-model="form.level" class="rounded-xl border-slate-300 px-3 py-2 text-sm"><option value="info">Informativ</option><option value="warning">Atentionare</option><option value="critical">Critic</option></select>
                </div>
                <textarea v-model="form.message" required maxlength="2000" rows="3" class="w-full rounded-xl border-slate-300 px-3 py-2 text-sm" placeholder="Mesajul care va fi afisat"></textarea>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><input v-model="form.starts_at" type="datetime-local" class="rounded-xl border-slate-300 px-3 py-2 text-sm" /><input v-model="form.ends_at" type="datetime-local" class="rounded-xl border-slate-300 px-3 py-2 text-sm" /></div>
                <button type="submit" :disabled="form.processing" class="rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-600 disabled:opacity-60">{{ form.processing ? 'Se publica...' : 'Publica anuntul' }}</button>
            </form>

            <section class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4"><h3 class="text-lg font-bold text-slate-900">Istoric anunturi</h3></div>
                <div v-if="!announcements.length" class="px-5 py-10 text-center text-sm text-slate-500">Nu exista anunturi.</div>
                <div v-else class="divide-y divide-slate-100">
                    <article v-for="announcement in announcements" :key="announcement.id" class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-start md:justify-between">
                        <div><div class="flex items-center gap-2"><h4 class="font-semibold text-slate-900">{{ announcement.title }}</h4><span class="rounded-full px-2 py-1 text-[11px] font-semibold" :class="announcementTone(announcement.level)">{{ announcement.level }}</span></div><p class="mt-1 text-sm text-slate-600">{{ announcement.message }}</p><div class="mt-2 text-xs text-slate-500">{{ formatDate(announcement.starts_at) }} - {{ formatDate(announcement.ends_at) || 'fara expirare' }}</div></div>
                        <button v-if="announcement.is_active" type="button" class="rounded-lg border border-rose-300 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50" @click="deactivate(announcement.id)">Dezactiveaza</button><span v-else class="text-xs font-semibold text-slate-400">Dezactivat</span>
                    </article>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ announcements: { type: Array, required: true } });
const form = useForm({ title: '', message: '', level: 'info', starts_at: '', ends_at: '', is_active: true });

function publish() { form.post(route('admin.announcements.store'), { preserveScroll: true, onSuccess: () => form.reset('title', 'message', 'starts_at', 'ends_at') }); }
function deactivate(id) { useForm({}).delete(route('admin.announcements.destroy', id), { preserveScroll: true }); }
function formatDate(value) { return value ? new Date(value).toLocaleString('ro-RO') : ''; }
function announcementTone(level) { return level === 'critical' ? 'bg-rose-100 text-rose-700' : level === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-sky-100 text-sky-700'; }
</script>
